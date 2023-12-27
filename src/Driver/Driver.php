<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Driver;

use Closure;
use Jmsl\Isocrono\Query\ScheduledQuery;

interface Driver
{

    public function executeQuery(ScheduledQuery $query): Closure;
    
    public function close(): void;
    
}
