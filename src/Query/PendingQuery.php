<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Query;

use Closure;
use Jmsl\Isocrono\Query\Bind\Bind;
use Jmsl\Isocrono\Query\Bind\BindList;
use Jmsl\Isocrono\Query\Bind\BindType;
use Jmsl\Isocrono\Thread\QueryPool;
use stdClass;

class PendingQuery
{

    private stdClass $storage;

    public function __construct(string $query)
    {
        $this->storage = new stdClass;
        $this->storage->query = $query;
    }

    public function bind(string $placeholder, mixed $value, BindType $type = BindType::STRING): static 
    {
        $this->storage->binds ??= [];
        $this->storage->binds[] = new Bind($placeholder, $value, $type);
        return $this;
    }

    public function then(Closure $closure): static 
    {
        $this->storage->then = $closure;
        return $this;
    }

    public function catch(Closure $closure): static 
    {
        $this->storage->catch = $closure;
        return $this;
    }

    public function fetchMode(FetchMode $fetchMode): static 
    {
        $this->storage->fetchMode = $fetchMode;
        return $this;
    }

    public function resultAsFirstRow(): static 
    {
        return $this->fetchMode(FetchMode::FIRST_RESULT);
    }

    public function resultAsAllLines(): static 
    {
        return $this->fetchMode(FetchMode::ALL_RESULTS);
    }

    public function resultAsLastId(): static 
    {
        return $this->fetchMode(FetchMode::LAST_INSERT_ID);
    }

    public function resultSuccessfully(): static 
    {
        return $this->fetchMode(FetchMode::SUCCESSFULLY);
    }

    public function build(): Query
    {
        $bindList = new BindList;
        if(isset($this->storage->binds)) {
            $bindList->add(...$this->storage->binds);
        }

        return new Query(
            $this->storage->query,
            $bindList,
            // new Promise($this->storage->then ?? null, $this->storage->catch ?? null),
            $this->storage->fetchMode ?? FetchMode::SUCCESSFULLY
        );
    }

    public function execute(QueryPool $pool): void 
    {
        $pool->scheduleQuery($this->build());
    }
    
}
