<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Thread;

use Jmsl\Isocrono\Driver\Driver;
use Jmsl\Isocrono\Query\Query;
use Jmsl\Isocrono\Query\ScheduledQuery;
use Jmsl\Isocrono\Support\DriverFactory;
use pmmp\thread\ThreadSafeArray;
use pocketmine\thread\Thread;

class QueryThread extends Thread
{

    private bool $wait = true;

    public function __construct(
        private int $id,
        private DriverFactory $driverFactory,
        public ThreadSafeArray $scheduledQueriesQueue,
        public ThreadSafeArray $processedQueriesQueue,
    ) {}

    public function noWait(): void 
    {
        $this->wait = false;
    }

    public function onRun(): void 
    {
        $driver = $this->driverFactory->make();

        while($this->wait) {
            $this->scheduledQueriesQueue->synchronized($this->heartbeat(...), $driver);
        }

        $driver->close();
    }
    
    private function heartbeat(Driver $driver): void 
    {
        while($this->scheduledQueriesQueue->count() < 1 && $this->wait) {
            $this->scheduledQueriesQueue->wait();
        }
        
        $query = $this->scheduledQueriesQueue->shift();
        $this->scheduledQueriesQueue->notify();
        if($query instanceof ScheduledQuery) {
            $driver->executeQuery($query);
            $this->processedQueue[] = $query;
        }
    }
    
}
