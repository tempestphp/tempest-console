<?php

declare(strict_types=1);

namespace Tempest\Console\Scheduler;

use DateTime;
use Tempest\Console\Commands\SchedulerRunInvocationCommand;

final class GenericScheduler implements Scheduler
{
    public const string CACHE_PATH = __DIR__ . '/schedule.last-run.cache.php';
    public const string EXECUTION_LOCK = __DIR__ . '/schedule.execution-lock.cache.php';

    private DateTime $end;
    private DateTime $start;
    private int $pid;

    public function __construct(
        private SchedulerConfig $config,
        private ScheduledInvocationExecutor $executor,
        ?int $pid = null,
    ) {
        $this->pid = $pid ?? getmypid();
    }

    public function run(?DateTime $date = null): void
    {
        $this->start = $date ?? new DateTime();
        $secondsToNextMinute = 60 - (int) $this->start->format('s');

        // Calculate the end time when the scheduler should stop, since we want to preserve start-of-minute accuracy
        $this->end = (clone $this->start)->modify("+$secondsToNextMinute seconds");

        $this->obtainExecutionLock();

        // Current reference time, initially set to the start time
        $currentReferenceTime = clone $this->start;

        while ($currentReferenceTime < $this->end) {
            if ($this->shouldInterrupt($currentReferenceTime)) {
                break;
            }

            // Calculate the next second start time
            $nextSecondStart = (clone $currentReferenceTime)->modify('+1 second');

            $commands = $this->getInvocationsToRun($currentReferenceTime);

            foreach ($commands as $command) {
                $this->execute($command);
            }

            usleep(1_000_000);

            $currentReferenceTime = $nextSecondStart;
        }
    }

    protected function execute(ScheduledInvocation $invocation): void
    {
        $command = $this->compileInvocation($invocation);

        $this->executor->execute($command);
    }

    private function compileInvocation(ScheduledInvocation $invocation): string
    {
        $commandName = $invocation->invocation instanceof HandlerInvocation ?
            SchedulerRunInvocationCommand::NAME . ' ' . $invocation->invocation->getName()
            : $invocation->invocation->getName();

        return join(' ', [
            '(' . $this->config->path,
            $commandName . ')',
            $invocation->schedule->outputMode->value,
            $invocation->schedule->output,
            ($invocation->schedule->runInBackground ? '&' : ''),
        ]);
    }

    /**
     * @param DateTime $date
     *
     * @return array
     */
    private function getInvocationsToRun(DateTime $date): array
    {
        $previousRuns = $this->getPreviousRuns();

        $eligibleToRun = array_filter(
            $this->config->scheduledInvocations,
            fn (ScheduledInvocation $invocation) => $invocation->canRunAt(
                date: $date,
                lastRunTimestamp: $previousRuns[$invocation->invocation->getName()] ?? null,
            )
        );

        $this->markInvocationsAsRun($eligibleToRun, $date);

        return $eligibleToRun;
    }

    /**
     * Returns a key value array of the last run time of each invocation.
     * The key is the invocation name and the value is the last run time in unix timestamp.
     *
     * @return array<string, int>
     */
    private function getPreviousRuns(): array
    {
        if (! file_exists(self::CACHE_PATH)) {
            return [];
        }

        return unserialize(file_get_contents(self::CACHE_PATH));
    }

    /**
     * @param ScheduledInvocation[] $ranInvocations
     * @param DateTime $ranAt
     *
     * @return void
     */
    private function markInvocationsAsRun(array $ranInvocations, DateTime $ranAt): void
    {
        $lastRuns = $this->getPreviousRuns();

        foreach ($ranInvocations as $invocation) {
            $lastRuns[$invocation->invocation->getName()] = $ranAt->getTimestamp();
        }

        file_put_contents(self::CACHE_PATH, serialize($lastRuns));
    }

    private function shouldInterrupt(DateTime $currentReferenceTime): bool
    {
        if (! file_exists(self::EXECUTION_LOCK)) {
            return false;
        }

        $content = unserialize(file_get_contents(self::EXECUTION_LOCK));

        if ($content['pid'] !== $this->pid) {
            return true;
        }

        if ($currentReferenceTime > $content['time']) {
            return false;
        }

        return false;
    }

    private function obtainExecutionLock(): void
    {
        if (file_exists(self::EXECUTION_LOCK)) {
            @unlink(self::EXECUTION_LOCK);
        }

        file_put_contents(self::EXECUTION_LOCK, serialize([
            'pid' => $this->pid,
            'time' => $this->end,
        ]));
    }
}
