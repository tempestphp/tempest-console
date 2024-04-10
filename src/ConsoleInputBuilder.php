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

        $widgets = $this->buildWidgets();

        $commandArgumentDefinitions = $this->command->getArgumentDefinitions();
        $widgetArgumentDefinitions = array_merge(...array_values($widgets));

        foreach ([...$widgetArgumentDefinitions, ...$commandArgumentDefinitions] as $argumentDefinition) {
            $value = $this->argumentBag->findFor($argumentDefinition);

            if ($value === null) {
                $invalidDefinitions[] = $argumentDefinition;

                continue;
            }

            $validArguments[] = $value;
        }

        if (count($invalidDefinitions)) {
            throw new InvalidCommandException(
                $this->command,
                $invalidDefinitions
            );
        }

        foreach ($this->buildWidgetList($widgets) as $widget) {
            $widget->__invoke();
        }

        return array_map(
            callback: fn (ConsoleInputArgument $argument) => $argument->value,
            array: $validArguments,
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
                $definitions[$widgetClass][] = ConsoleArgumentDefinition::fromParameter($parameter);
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

    /**
     * @return ConsoleWidget[]
     */
    private function buildWidgetList(array $widgetDefinitionList): array
    {
        $widgets = [];

        foreach ($widgetDefinitionList as $widgetClass => $widgetProperties) {
            $mappedProperties = [];

            foreach ($widgetProperties as $property) {
                $argument = $this->argumentBag->findFor($property);

                if ($argument === null) {
                    continue;
                }

                $mappedProperties[$property->name] = $argument->value;
            }

            $widgets[] = $this->container->get($widgetClass);
        }

        return $widgets;
    }
}
