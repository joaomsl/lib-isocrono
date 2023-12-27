<?php

declare(strict_types=1);

namespace Jmsl\Isocrono\Driver;

use BadMethodCallException;
use InvalidArgumentException;
use Jmsl\Isocrono\Driver\Driver;
use Jmsl\Isocrono\Query\Bind\Bind;
use Jmsl\Isocrono\Query\Bind\BindType;
use Jmsl\Isocrono\Query\FetchMode;
use Jmsl\Isocrono\Query\Query;
use Jmsl\Isocrono\Query\ScheduledQuery;
use Jmsl\Isocrono\Support\Promise;
use PDO;
use PDOException;
use pmmp\thread\ThreadSafeArray;

class PDODriver implements Driver
{

    private PDO $connection;

    public function __construct(
        private string $dsn,
        private string $user, 
        private string $password,
        private ThreadSafeArray|null $options = null
    ) {
        if(empty($dsn)) {
            throw new InvalidArgumentException('The connection dsn cannot be empty.');
        }
        if(empty($user)) {
            throw new InvalidArgumentException('The connection user cannot be empty.');
        }
        
        $options ??= new ThreadSafeArray;
        $options[PDO::ATTR_ERRMODE] ??= PDO::ERRMODE_EXCEPTION;

        $this->connection = new PDO($dsn, $user, $password, (array)$options);
    }
    
    private function getPDOParamType(BindType $type): int 
    {
        return match($type) {
            BindType::STRING => PDO::PARAM_STR,
            BindType::INT => PDO::PARAM_INT,
            BindType::BOOLEAN => PDO::PARAM_BOOL
        };
    }

    public function executeQuery(ScheduledQuery $scheduledQuery): void 
    {
        if(!isset($this->connection)) {
            throw new BadMethodCallException('Connection is closed.');
        }

        $query = $scheduledQuery->getQuery();
        $statement = $this->connection->prepare($query->getQuery());
        
        /** @var Bind $bind */
        foreach($query->getBindList()->all() as $bind) {
            $statement->bindValue(
                $bind->getPlaceholder(), 
                $bind->getValue(), 
                $this->getPDOParamType($bind->getType())
            );
        }

        try {
            $successfully = $statement->execute();

            $result = match($query->getFetchMode()) {
                FetchMode::FIRST_RESULT => $statement->fetch(),
                FetchMode::ALL_RESULTS => $statement->fetchAll(),
                FetchMode::LAST_INSERT_ID => $this->connection->lastInsertId(),
                FetchMode::SUCCESSFULLY => $successfully
            };

            $scheduledQuery->setPromiseHandler(fn(Promise $promise) => $promise->resolve($result));
        } catch(PDOException $ex) {
            $scheduledQuery->setPromiseHandler(fn(Promise $promise) => $promise->reject($ex));
        }

        $statement->closeCursor();
    }
    
    public function close(): void 
    {
        unset($this->connection);
    }

}
