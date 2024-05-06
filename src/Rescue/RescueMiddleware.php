<?php

declare(strict_types=1);

namespace Tempest\Console\Rescue;

use Closure;
use Tempest\Console\Exceptions\ConsoleException;

interface RescueMiddleware
{

    public function __invoke(ConsoleException $throwable, Closure $next): bool;

}
