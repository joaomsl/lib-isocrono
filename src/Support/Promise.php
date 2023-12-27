<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Support;

use Closure;
use pmmp\thread\ThreadSafe;
use Throwable;

class Promise extends ThreadSafe
{

    public function __construct(
        private ?Closure $onSuccess = null, 
        private ?Closure $onFail = null
    ) {
    }

    public function resolve(mixed $result): void 
    {
        if(!is_null($this->onSuccess)) {
            ($this->onSuccess)($result);
        }
    }

    public function reject(mixed $rejection): void 
    {
        if(!is_null($this->onFail)) {
            ($this->onFail)($rejection);
            return;
        }
        if($rejection instanceof Throwable) {
            throw $rejection;
        }
    }

}
