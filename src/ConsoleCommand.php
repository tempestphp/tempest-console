<?php

declare(strict_types=1);

namespace Tempest\Console;

use Attribute;
use Tempest\Console\Input\ConsoleArgumentDefinition;
use Tempest\Reflection\MethodReflector;

#[Attribute]
final class ConsoleCommand
{
    public MethodReflector $handler;

    public function __construct(
        private readonly ?string $name = null,
        public readonly ?string $description = null,

        /** @var string[] */
        public readonly array $aliases = [],
        public readonly ?string $help = null,

        /** @var array<array-key, class-string<\Tempest\Console\ConsoleMiddleware>> */
        public readonly array $middleware = [],
        public readonly bool $hidden = false,

        /** @var class-string<\Tempest\Console\CompletesConsoleCommand>|null */
        public readonly string|null $complete = null,
    ) {
    }

    public function setHandler(MethodReflector $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    public function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return $this->handler->getName() === '__invoke'
            ? strtolower($this->handler->getDeclaringClass()->getShortName())
            : strtolower($this->handler->getDeclaringClass()->getShortName() . ':' . $this->handler->getName());
    }

    /**
     * @return ConsoleArgumentDefinition[]
     */
    public function getArgumentDefinitions(): array
    {
        $arguments = [];

        foreach ($this->handler->getParameters() as $parameter) {
            $arguments[$parameter->getName()] = ConsoleArgumentDefinition::fromParameter($parameter);
        }

        return $arguments;
    }
}
