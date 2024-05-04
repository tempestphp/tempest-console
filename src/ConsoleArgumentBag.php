<?php

declare(strict_types=1);

namespace Tempest\Console;

use BackedEnum;

final class ConsoleArgumentBag
{
    /** @var ConsoleInputArgument[] */
    protected array $arguments = [];

    /** @var string[] */
    protected array $path = [];

    /**
     * @param array<string|int, mixed> $arguments
     */
    public function __construct(array $arguments)
    {
        $this->path = array_filter([
            $arguments[0] ?? null,
            $arguments[1] ?? null,
        ]);

        unset($arguments[0], $arguments[1]);

        foreach (array_values($arguments) as $position => $argument) {
            $this->add(
                ConsoleInputArgument::fromString($argument, $position),
            );
        }
    }

    /**
     * @return ConsoleInputArgument[]
     */
    public function all(): array
    {
        return $this->arguments;
    }

    public function get(string $name): ?ConsoleInputArgument
    {
        foreach ($this->arguments as $argument) {
            if ($argument->name === $name) {
                return $argument;
            }
        }

        return null;
    }

    public function findFor(ConsoleArgumentDefinition $argumentDefinition): ?ConsoleInputArgument
    {
        foreach ($this->arguments as $argument) {
            if ($argumentDefinition->matchesArgument($argument)) {
                return $this->castArgument($argument, $argumentDefinition);
            }
        }

        if ($argumentDefinition->hasDefault) {
            return new ConsoleInputArgument(
                name: $argumentDefinition->name,
                position: $argumentDefinition->position,
                value: $argumentDefinition->default,
            );
        }

        return null;
    }

    public function add(ConsoleInputArgument $argument): self
    {
        $this->arguments[] = $argument;

        return $this;
    }

    public function getCommandName(): string
    {
        return $this->path[1] ?? '';
    }

    private function castArgument(ConsoleInputArgument $argument, ConsoleArgumentDefinition $argumentDefinition): ConsoleInputArgument
    {
        $value = match($argumentDefinition->type) {
            'bool' => filter_var($argument->value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $argument->value,
            'float' => (float) $argument->value,
            default => enum_exists($argumentDefinition->type) && is_subclass_of($argumentDefinition->type, BackedEnum::class)
                ? $argumentDefinition->type::from($argument->value)
                : $argument->value
        };

        return new ConsoleInputArgument(
            name: $argument->name,
            position: $argument->position,
            value: $value,
        );
    }
}
