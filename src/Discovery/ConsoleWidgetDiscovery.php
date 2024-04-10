<?php

declare(strict_types=1);

namespace Tempest\Console\Discovery;

use ReflectionClass;
use Tempest\Console\ConsoleConfig;
use Tempest\Container\Container;
use Tempest\Discovery\Discovery;
use Tempest\Support\Reflection\Attributes;
use Tempest\Console\Widgets\GlobalConsoleWidget;

final readonly class ConsoleWidgetDiscovery implements Discovery
{
    private const CACHE_PATH = __DIR__ . '/console-widget-discovery.cache.php';

    public function __construct(private ConsoleConfig $consoleConfig)
    {
    }

    public function discover(ReflectionClass $class): void
    {
        if (! Attributes::find(GlobalConsoleWidget::class)->in($class)->first()) {
            return;
        }

        $this->consoleConfig->addWidget($class->getName());
    }

    public function hasCache(): bool
    {
        return file_exists(self::CACHE_PATH);
    }

    public function storeCache(): void
    {
        file_put_contents(self::CACHE_PATH, serialize($this->consoleConfig->widgets));
    }

    public function restoreCache(Container $container): void
    {
        $commands = unserialize(file_get_contents(self::CACHE_PATH));

        $this->consoleConfig->widgets = $commands;
    }

    public function destroyCache(): void
    {
        @unlink(self::CACHE_PATH);
    }
}
