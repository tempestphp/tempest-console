<?php

declare(strict_types=1);

namespace App\Enums;

enum AuthenticationStrategy: string
{
    case OAUTH = 'oauth';
    case JWT = 'jwt';
}
