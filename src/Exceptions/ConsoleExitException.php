<?php

declare(strict_types=1);

namespace Tempest\Console\Exceptions;

use Tempest\Console\ConsoleOutput;

final class ConsoleExitException extends ConsoleException
{

    public static function new(): self
    {
        return new self();
    }

    public function render(ConsoleOutput $output): void
    {
        exit;
    }
}
