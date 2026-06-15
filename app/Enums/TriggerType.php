<?php

namespace App\Enums;

enum TriggerType: string
{
    case Automatic = 'automatic';
    case ManualAll = 'manual_all';
    case ManualSite = 'manual_site';
}
