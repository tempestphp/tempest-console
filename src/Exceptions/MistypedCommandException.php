<?php

declare(strict_types=1);

namespace Tempest\Console\Exceptions;

use Exception;
use Tempest\Console\ConsoleCommand;

final class MistypedCommandException extends Exception
{

    public readonly ConsoleCommand $intendedCommand;

    public static function for(ConsoleCommand $command): self
    {
        return (new self(
            message: 'Command not found',
        ))
            ->setIntendedCommand($command);
    }

    public function setIntendedCommand(ConsoleCommand $intendedCommand): self
    {
        $this->intendedCommand = $intendedCommand;

        return $this;
    }

}
