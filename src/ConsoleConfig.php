<?php

declare(strict_types=1);

namespace Tempest\Console;

use ReflectionMethod;
use Tempest\Console\Widgets\ConsoleWidget;

final class ConsoleConfig
{
    public function __construct(
        public string $name = 'Tempest',

        /** @var \Tempest\Console\ConsoleCommand[] $commands */
        public array $commands = [],

        /** @var class-string<ConsoleWidget>[] $widgets */
        public array $widgets = [],
    ) {
    }

    public function addCommand(ReflectionMethod $handler, ConsoleCommand $consoleCommand): self
    {
        $consoleCommand->setHandler($handler);

        $this->commands[$consoleCommand->getName()] = $consoleCommand;

        foreach ($consoleCommand->aliases as $alias) {
            if (array_key_exists($alias, $this->commands)) {
                continue;
            }

            $this->commands[$alias] = $consoleCommand;
        }

        return $this;
    }

    /**
     * @param class-string<ConsoleWidget> $widget
     *
     * @return $this
     */
    public function addWidget(string $widget): self
    {
        $this->widgets[] = $widget;

        return $this;
    }
}
