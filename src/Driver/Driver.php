<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Driver;

use Jmsl\Isocrono\Query\ScheduledQuery;

interface Driver
{

    public function executeQuery(ScheduledQuery $query): void;
    
    public function close(): void;
    
}
