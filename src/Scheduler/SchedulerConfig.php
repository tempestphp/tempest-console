<?php

declare(strict_types=1);

namespace Tempest\Console\Scheduler;

use ReflectionMethod;
use Tempest\Console\ConsoleCommand;

final class SchedulerConfig
{
    public function __construct(
        public string $path = "php tempest",

        /** @var ScheduledInvocation[] $scheduledInvocations */
        public array $scheduledInvocations = [],
    ) {
    }

    public function addHandlerInvocation(ReflectionMethod $handler, Schedule $schedule): self
    {
        $this->scheduledInvocations[] = new ScheduledInvocation($schedule, new HandlerInvocation($handler));

        return $this;
    }

    public function addCommandInvocation(ReflectionMethod $handler, ConsoleCommand $command, Schedule $schedule): self
    {
        $command->setHandler($handler);

        $this->scheduledInvocations[] = new ScheduledInvocation($schedule, $command);

        return $this;
    }
}