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
use pocketmine\Server;
use pocketmine\snooze\SleeperHandlerEntry;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class QueryPool
{

    private ThreadSafeArray $scheduledQueriesQueue;
    private ThreadSafeArray $processedQueriesQueue;

    private int $processedQueriesSleeperHandlerId;

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

        $this->initThreads($driverFactory, $totalThreads);
        $this->initProcessedQueriesHandler();
    }

    private function initThreads(DriverFactory $driverFactory, int $total): void 
    {
        for($id = 0; $id < $total; $id++) {
            $thread = $this->threads[] = new QueryThread(
                $id, 
                $driverFactory, 
                $this->scheduledQueriesQueue,
                $this->processedQueriesQueue
            );
            $thread->start();
        }
    }

    private function initProcessedQueriesHandler(): void 
    {
        $handler = function() {
            $this->processedQueriesQueue->synchronized(function() {
                do {
                    $this->handleProcessedQuery($this->processedQueriesQueue->shift());
                } while($this->processedQueriesQueue->count() > 0);
            });
        };

        $this->processedQueriesSleeperHandlerId = Server::getInstance()
            ->getTickSleeper()
            ->addNotifier($handler)
            ->getNotifierId();
    }

    private function handleProcessedQuery(ScheduledQuery $scheduledQuery): void 
    {
        if(is_null($scheduledQuery->getPromiseHandler())) {
            throw new RuntimeException(sprintf('Query #%s does not have a promise handler.', $scheduledQuery->getId()));
        }

        $promise = 
            $this->promises[$scheduledQuery->getId()] ?? 
            throw new RuntimeException(sprintf('Could not find the promise of query #%s', $scheduledQuery->getId()));
        
        unset($this->promises[$scheduledQuery->getId()]);
        ($scheduledQuery->getPromiseHandler())($promise);
    }

    public function scheduleQuery(Query $query, Promise $promise): void 
    {
        if(count($this->threads) < 1) {
            throw new BadMethodCallException('All threads have already been stopped.');
        }

        $scheduledQuery = new ScheduledQuery(Uuid::uuid4()->toString(), $query);
        $this->promises[$scheduledQuery->getId()] = $promise;

        $this->scheduledQueriesQueue->synchronized(function() use($scheduledQuery) {
            $this->scheduledQueriesQueue[] = $scheduledQuery;
            $this->scheduledQueriesQueue->notifyOne();
        });
    }

    public function executePendingQueries(): void 
    {
        $this->scheduledQueriesQueue->synchronized(function() {
            while($this->scheduledQueriesQueue->count() > 0) {
                $this->scheduledQueriesQueue->wait();
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
        array_walk($this->threads, fn(QueryThread $thread) => $thread->join());
        
        $this->threads = [];
        Server::getInstance()->getTickSleeper()->removeNotifier($this->processedQueriesSleeperHandlerId);
    }

}
