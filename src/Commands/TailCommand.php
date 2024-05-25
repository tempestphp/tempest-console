<?php

declare(strict_types=1);

namespace Tempest\Console\Commands;

use Fiber;
use Tempest\Console\ConsoleArgument;
use Tempest\Console\ConsoleCommand;

final readonly class TailCommand
{
    public function __construct(
        private LogDebugCommand $logDebugCommand,
        private LogProjectCommand $logProjectCommand,
        private LogServerCommand $logServerCommand,
    ) {}

    #[ConsoleCommand(
        name: 'log:tail',
        description: 'Tail multiple logs',
        aliases: ['tail'],
    )]
    public function __invoke(
        #[ConsoleArgument(description: 'Include the debug log', aliases: ['-d'])]
        ?bool $debug = null,

        #[ConsoleArgument(description: 'Include the project log', aliases: ['-p'])]
        ?bool $project = null,

        #[ConsoleArgument(description: 'Include the server log', aliases: ['-s'])]
        ?bool $server = null
    ): void {
        $shouldFilter = $debug !== null
            || $project !== null
            || $server !== null;

        /** @var array<array-key, \Tempest\Console\Commands\LogDebugCommand|\Tempest\Console\Commands\LogProjectCommand> $loggers */
        $loggers = array_filter([
            ($shouldFilter === false || $debug) ? $this->logDebugCommand : null,
            ($shouldFilter === false || $project) ? $this->logProjectCommand : null,
            ($shouldFilter === false || $server) ? $this->logServerCommand : null,
        ]);

        /** @var Fiber[] $fibers */
        $fibers = [];

        foreach ($loggers as $key => $logger) {
            $fiber = new Fiber(fn () => ($logger)());
            $fibers[$key] = $fiber;
            $fiber->start();
        }

        while ($fibers !== []) {
            foreach ($fibers as $key => $fiber) {
                if ($fiber->isSuspended()) {
                    $fiber->resume();
                }

                if ($fiber->isTerminated()) {
                    unset($fibers[$key]);
                }
            }
        }
    }
}