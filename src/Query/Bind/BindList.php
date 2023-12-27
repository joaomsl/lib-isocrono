<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Query\Bind;

use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class BindList extends ThreadSafe
{

    private ThreadSafeArray $binds;

    public function __construct()
    {
        $this->binds = new ThreadSafeArray;   
    }

    public function add(Bind ...$bind): static 
    {
        $this->binds->merge($bind, false);
        return $this;
    }

    public function all(): ThreadSafeArray 
    {
        return $this->binds;
    }

}
