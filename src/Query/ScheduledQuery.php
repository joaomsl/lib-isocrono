<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Query;

use Closure;
use pmmp\thread\ThreadSafe;

class ScheduledQuery extends ThreadSafe
{

    private ?Closure $promiseHandler = null;

    public function __construct(private Query $query) {}
    
    public function getQuery(): Query
    {
        return $this->query;
    }

    public function setPromiseHandler(Closure $handler): void 
    {
        $this->promiseHandler = $handler;
    }

    public function getPromiseHandler(): ?Closure
    {
        return $this->promiseHandler;
    }
    
}
