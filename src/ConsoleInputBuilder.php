<?php

declare(strict_types=1);

namespace Tempest\Console;

use ReflectionClass;
use ReflectionProperty;
use Tempest\Container\Container;
use Tempest\Console\Widgets\ConsoleWidget;
use Tempest\Console\Exceptions\InvalidCommandException;

final class ConsoleInputBuilder
{
    public function __construct(
        protected ConsoleCommand $command,
        protected ConsoleArgumentBag $argumentBag,
        protected Container $container,
    ) {
    }

    /**
     * @return array<ConsoleInputArgument>
     */
    public function build(): array
    {
        $validArguments = [];
        $invalidDefinitions = [];
        $validCommandArguments = [];

        $widgets = $this->buildWidgets();

        $commandArgumentDefinitions = $this->command->getArgumentDefinitions();
        /** @var ConsoleArgumentDefinition[] $widgetArgumentDefinitions */
        $widgetArgumentDefinitions = array_merge(...array_values($widgets));

        foreach ($commandArgumentDefinitions as $argumentDefinition) {
            $value = $this->argumentBag->findFor($argumentDefinition);

            if ($value === null) {
                $invalidDefinitions[] = $argumentDefinition;

                continue;
            }

            $validArguments[$argumentDefinition->name] = $value;
            $validCommandArguments[] = $value;
        }

        foreach ($widgetArgumentDefinitions as $argumentDefinition) {
            $value = $this->argumentBag->findFor($argumentDefinition);

            if ($value === null) {
                $invalidDefinitions[] = $argumentDefinition;

                continue;
            }

            $validArguments[$argumentDefinition->name] = $value;
        }

        if (count($invalidDefinitions)) {
            throw new InvalidCommandException(
                $this->command,
                $invalidDefinitions
            );
        }

        $this->executeWidgets($widgets, $validArguments);

        return array_map(
            callback: fn (ConsoleInputArgument $argument) => $argument->value,
            array: $validCommandArguments,
        );
    }

    /**
     * @return Array<string, ConsoleArgumentDefinition>[]
     * @throws \ReflectionException
     */
    private function buildWidgets(): array
    {
        $definitions = [];

        foreach ($this->widgetClassList() as $widgetClass) {
            $reflection = new ReflectionClass($widgetClass);

            if (! $reflection->implementsInterface(ConsoleWidget::class)) {
                continue;
            }

            $method = $reflection->getMethod('__invoke');

            foreach ($method->getParameters() as $parameter) {
                $definitions[$widgetClass][] = ConsoleArgumentDefinition::fromParameter($parameter, false);
            }
        }

        return $definitions;
    }

    /**
     * @return class-string<ConsoleWidget>[]
     */
    private function widgetClassList(): array
    {
        return [
            ...$this->container->get(ConsoleConfig::class)->widgets,
            ...$this->command->widgets,
        ];
    }

    private function executeWidgets(array $widgetDefinitionList, array $validArguments): void
    {
        foreach ($widgetDefinitionList as $widgetClass => $widgetProperties) {
            $mappedProperties = [];

            foreach ($widgetProperties as $property) {
                $mappedProperties[$property->name] = $validArguments[$property->name]->value;
            }

            ($this->container->get($widgetClass))->__invoke(...$mappedProperties);
        }
    }
}
