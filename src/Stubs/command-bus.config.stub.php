<?php

declare(strict_types=1);

use Tempest\CommandBus\CommandBusConfig;

return new CommandBusConfig(
    middleware: [
        // Add your command bus middleware here.
    ],
);
