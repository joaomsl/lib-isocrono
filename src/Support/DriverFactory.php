<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Support;

use InvalidArgumentException;
use Jmsl\Isocrono\Driver\Driver;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

class DriverFactory extends ThreadSafe
{

    private ThreadSafeArray $driverClassParams;

    public function __construct(
        private string $driverClass, 
        array $driverClassParams = []
    ) {
        if(!is_a($driverClass, Driver::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'The "%s" class needs to implement the "%s" interface.', 
                $driverClass, 
                Driver::class
            ));
        }
        $this->driverClassParams = ThreadSafeArray::fromArray($driverClassParams) ?? new ThreadSafeArray;
    }

    public function make(): Driver
    {
        return new ($this->driverClass)(...$this->driverClassParams);
    }

}
