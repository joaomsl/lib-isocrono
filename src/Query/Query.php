<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Query;

use InvalidArgumentException;
use Jmsl\Isocrono\Query\Bind\BindList;
use pmmp\thread\ThreadSafe;

class Query extends ThreadSafe
{

    public function __construct(
        private string $query,
        private BindList $bindList,
        private FetchMode $fetchMode
    ) {
        if(empty($query)) {
            throw new InvalidArgumentException('The query cannot be empty.');
        }
    }

    public function getQuery(): string 
    {
        return $this->query;
    }

    public function getBindList(): BindList
    {
        return $this->bindList;
    }
    
    public function getFetchMode(): FetchMode
    {
        return $this->fetchMode;
    }
    
    public static function prepare(string $query): PendingQuery
    {
        return new PendingQuery($query);
    }
    
}
