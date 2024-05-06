<?php

declare(strict_types=1);

namespace App\Console;

use Tempest\Console\Console;
use Tempest\Console\ConsoleArgument;
use Tempest\Console\ConsoleCommand;

final readonly class CommandWithEnumArgs
{
    public function __construct(
        protected Console $console,
    ) {

    }

    #[ConsoleCommand(
        name: 'auth:command',
        description: 'Command that',
    )]
    public function command(
        #[ConsoleArgument(
            description: 'The authentication strategy',
            aliases: ['auth'],
        )]
        string $strategy,
        #[ConsoleArgument(
            description: 'The bearer token for authentication',
            aliases: ['token'],
        )]
        string $bearerToken,
    ) {
        $this->console->writeln("Strategy: {$strategy}");
        $this->console->writeln("Bearer token: {$bearerToken}");
    }
}
