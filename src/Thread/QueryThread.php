<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Thread;

use Jmsl\Isocrono\Driver\Driver;
use Jmsl\Isocrono\Query\Query;
use Jmsl\Isocrono\Query\ScheduledQuery;
use Jmsl\Isocrono\Support\DriverFactory;
use pmmp\thread\ThreadSafeArray;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\thread\Thread;

class QueryThread extends Thread
{

    private bool $wait = true;

    public function __construct(
        private int $id,
        private DriverFactory $driverFactory,
        private ThreadSafeArray $queue,
        private SleeperHandlerEntry $sleeperHandlerEntry
    ) {}

    public function noWait(): void 
    {
        $this->wait = false;
    }

    public function onRun(): void 
    {
        $driver = $this->driverFactory->make();
        $notifier = $this->sleeperHandlerEntry->createNotifier();

        while($this->wait) {
            $this->queue->synchronized($this->heartbeat(...), $driver, $notifier);
        }

        $driver->close();
    }
    
    private function heartbeat(Driver $driver, SleeperNotifier $sleeperNotifier): void 
    {
        while($this->queue->count() < 1 && $this->wait) {
            $this->queue->wait();
        }
        
        $query = $this->queue->shift();
        $this->queue->notify();
        if($query instanceof ScheduledQuery) {
            $driver->executeQuery($query);
            $sleeperNotifier->wakeupSleeper();
        }
    }
    
}
