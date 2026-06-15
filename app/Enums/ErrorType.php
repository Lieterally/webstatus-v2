<?php

namespace App\Enums;

enum ErrorType: string
{
    case None = 'none';
    case Timeout = 'timeout';
    case ConnectionFailure = 'connection_failure';
    case DnsFailure = 'dns_failure';
}
