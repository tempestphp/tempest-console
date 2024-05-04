<?php

declare(strict_types=1);

namespace App\Console;

use App\Enums\AuthenticationStrategy;
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
        name: 'enums:command',
        description: 'Command with enum arguments',
        help: 'Help text for command with enum arguments'
    )]
    public function command(
        #[ConsoleArgument(
            description: 'The authentication strategy',
            aliases: ['auth'],
        )]
        AuthenticationStrategy $strategy,
        #[ConsoleArgument(
            description: 'The bearer token for authentication',
            aliases: ['token'],
        )]
        string $bearerToken,
    ) {
        $this->console->writeln("First enum argument: {$strategy->value}");
        $this->console->writeln("Bearer token: {$bearerToken}");
    }
}
