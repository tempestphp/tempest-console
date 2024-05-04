<?php

namespace App\Console;

use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\ConsoleArgument;
use App\Enums\AuthenticationStrategy;

final readonly class CommandWithEnumArgs
{

    public function __construct(
        protected Console $console,
    )
    {

    }

    #[ConsoleCommand(
        name: 'enums:command',
        description: 'Command with enum arguments',
        help: 'Help text for command with enum arguments'
    )]
    public function command(
        #[ConsoleArgument(
            description: 'The first enum argument',
            help: 'Extended help text for the first enum argument',
            aliases: ['auth'],
        )]
        AuthenticationStrategy $strategy,
        #[ConsoleArgument(
            description: 'The bearer token for authentication',
            aliases: ['token'],
        )]
        string $bearerToken,
    )
    {
        $this->console->writeln("First enum argument: {$strategy->value}");
        $this->console->writeln("Bearer token: {$bearerToken}");
    }

}
