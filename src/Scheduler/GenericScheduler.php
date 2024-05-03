<?php

declare(strict_types=1);

namespace Tempest\Console\Scheduler;

use DateTime;
use Tempest\Console\Commands\ScheduleTaskCommand;

final class GenericScheduler implements Scheduler
{
    public const string CACHE_PATH = __DIR__ . '/last-schedule-run.cache.php';

    public function __construct(
        private SchedulerConfig $config,
        private ScheduledInvocationExecutor $executor,
    ) {
    }

    public function run(?DateTime $date = null): void
    {
        $date ??= new DateTime();

        $commands = $this->getInvocationsToRun($date);

        foreach ($commands as $command) {
            $this->execute($command);
        }
    }

    private function execute(ScheduledInvocation $invocation): void
    {
        $command = $this->compileInvocation($invocation);

        $this->executor->execute($command);
    }

    private function compileInvocation(ScheduledInvocation $invocation): string
    {
        $commandName = $invocation->invocation instanceof HandlerInvocation ?
            ScheduleTaskCommand::NAME . ' ' . $invocation->invocation->getName()
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
}
