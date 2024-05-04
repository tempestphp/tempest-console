<?php

declare(strict_types=1);

namespace Tempest\Console\Exceptions;

use Exception;
use Tempest\Console\Actions\RenderConsoleCommand;
use Tempest\Console\Console;
use Tempest\Console\ConsoleArgumentBag;
use Tempest\Console\ConsoleArgumentDefinition;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\ConsoleInputArgument;
use Tempest\Validation\Rules\Length;

final class InvalidCommandException extends ConsoleException
{
    public function __construct(
        private readonly ConsoleCommand $consoleCommand,
        /** @var \Tempest\Console\ConsoleArgumentDefinition[] $invalidDefinitions */
        private readonly array $invalidDefinitions,
        private readonly ConsoleArgumentBag $bag,
    ) {
    }

    public function render(Console $console): void
    {
        $console->error("Invalid command usage:");

        (new RenderConsoleCommand($console))($this->consoleCommand);

        $missingArguments = implode(', ', array_map(
            fn (ConsoleArgumentDefinition $argumentDefinition) => $argumentDefinition->name,
            $this->invalidDefinitions,
        ));

        if ($missingArguments) {
            $console->writeln("Missing arguments: {$missingArguments}");
        }

        $fixedArguments = $this->promptForMissingArguments($console);

        foreach ($fixedArguments as $argument) {
            $this->bag->add($argument);
        }

        throw new MistypedCommandException($this->consoleCommand->getName());
    }

    /**
     * @param Console $console
     *
     * @return array
     */
    private function promptForMissingArguments(Console $console): array
    {
        $arguments = [];

        foreach ($this->invalidDefinitions as $definition) {
            if (! $definition->hasDefault) {
                $value = $this->resolveValue($definition, $console);

                $arguments[] = new ConsoleInputArgument(name: $definition->name, position: $definition->position, value: $value);
            }
        }

        return $arguments;
    }

    private function resolveValue(ConsoleArgumentDefinition $definition, Console $console): mixed
    {
        return match (true) {
            $definition->choices !== [] => $console->ask($definition->name, $definition->choices),
            $definition->type === 'bool' => $console->confirm($definition->name),
            default => $console->ask($definition->name, validation: [new Length(min: 1)]),
        };
    }
}
