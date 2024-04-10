<?php

declare(strict_types=1);

namespace Tempest\Console\Widgets;

use Tempest\Console\ConsoleInput;
use Tempest\Console\ConsoleArgument;
use Tempest\Console\Exceptions\ConsoleExitException;

final class ForceWidget implements ConsoleWidget
{

    public function __construct(
        protected ConsoleInput $input,
    ) {}

    public function __invoke(
        #[ConsoleArgument(
            description: 'Force the operation to run when in production',
            aliases: ['f'],
        )]
        bool $force = false
    ): void
    {
        if (! $force && ! $this->input->confirm("This command is dangerous, are you sure?")) {
            throw ConsoleExitException::new();
        }
    }
}
