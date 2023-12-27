<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Driver;

use Jmsl\Isocrono\Query\Query;

interface Driver
{

    public function executeQuery(Query $query): void;
    
    public function close(): void;
    
}
