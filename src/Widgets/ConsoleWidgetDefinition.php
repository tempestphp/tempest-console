<?php

declare(strict_types=1);

namespace Tempest\Console\Widgets;

use ReflectionMethod;
use Tempest\Console\ConsoleArgumentDefinition;

final readonly class ConsoleWidgetDefinition
{

    public function __construct(
        public ReflectionMethod $handler,
        /** @var ConsoleArgumentDefinition[] */
        public array $attributes,
    )
    {

    }

}
