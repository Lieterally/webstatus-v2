<?php

namespace App\Enums;

enum SiteStatus: string
{
    case Up = 'up';
    case PartiallyDown = 'partially_down';
    case TotallyDown = 'totally_down';
}
