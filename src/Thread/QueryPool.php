<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Thread;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use Jmsl\Isocrono\Query\Query;
use Jmsl\Isocrono\Query\ScheduledQuery;
use Jmsl\Isocrono\Support\DriverFactory;
use Jmsl\Isocrono\Support\Promise;
use pmmp\thread\ThreadSafeArray;
use Ramsey\Uuid\Uuid;

class QueryPool
{

    private ThreadSafeArray $scheduledQueriesQueue;
    private ThreadSafeArray $processedQueriesQueue;

    /** @var array<string, Promise> */
    private array $promises = [];


    /** @var QueryThread[] */
    private array $threads = [];

    public function __construct(DriverFactory $driverFactory, int $totalThreads = 1) {
        $this->scheduledQueriesQueue = new ThreadSafeArray;
        $this->processedQueriesQueue = new ThreadSafeArray;

        if($totalThreads < 1) {
            throw new InvalidArgumentException('The number of threads must be >= 1');
        }

        for($id = 0; $id < $totalThreads; $id++) {
            $thread = $this->threads[] = new QueryThread($id, $driverFactory, $this->scheduledQueriesQueue);
            $thread->start();
        }
    }

    private function queueSynchronized(Closure $closure): void 
    {
        $this->scheduledQueriesQueue->synchronized($closure, $this->scheduledQueriesQueue);
    }

    public function scheduleQuery(Query $query, Promise $promise): void 
    {
        if(count($this->threads) < 1) {
            throw new BadMethodCallException('All threads have already been stopped.');
        }

        $scheduledQuery = new ScheduledQuery(Uuid::uuid4()->toString(), $query);
        $this->promises[$scheduledQuery->getId()] = $promise;

        $this->scheduledQueriesQueueSynchronized(function(ThreadSafeArray $queue) use($scheduledQuery) {
            $queue[] = $scheduledQuery;
            $queue->notifyOne();
        });
    }

    public function executePendingQueries(): void 
    {
        $this->scheduledQueriesQueueSynchronized(function(ThreadSafeArray $queue) {
            while($queue->count() > 0) {
                $queue->wait();
            }
        });
    }

    public function stop(bool $waitPendingQueries = true): void 
    {
        if($waitPendingQueries) {
            $this->executePendingQueries();
        }

        // signal to threads that the next tick should not wait for new queries
        array_walk($this->threads, fn(QueryThread $thread) => $thread->noWait());
        
        // we notify the threads to perform another tick, and due to the above instruction 
        // the threads will exit the loop of waiting for a new query
        $this->scheduledQueriesQueue->notify();
        
        // and now just perform the join to terminate the threads
        array_walk($this->threads, fn(QueryThread $thread) => $thread->quit());
        
        $this->threads = [];
    }

}
