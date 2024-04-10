<?php

declare(strict_types=1);

namespace Tempest\Console\Widgets;

use Tempest\Console\Exceptions\ConsoleExitException;

interface ConsoleWidget
{

    /**
     * this could be a middleware - not sure what should be passed here though - maybe the argument bag with option to modify args?
     *
     * @throws ConsoleExitException
     */
    public function __invoke(): void;

}
