<?php

declare(strict_types=1);

namespace Jmsl\Isocrono;

use Jmsl\Isocrono\Support\DriverFactory;
use Jmsl\Isocrono\Thread\QueryPool;

class Isocrono
{

    public static function create(DriverFactory $driverFactory, int $totalThreads = 1): QueryPool
    {
        return new QueryPool($driverFactory, $totalThreads);
    }

}
