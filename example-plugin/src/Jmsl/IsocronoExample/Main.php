<?php 

declare(strict_types=1);

namespace Jmsl\IsocronoExample;

use Jmsl\Isocrono\Driver\PDODriver;
use Jmsl\Isocrono\Isocrono;
use Jmsl\Isocrono\Query\Bind\BindType;
use Jmsl\Isocrono\Query\Query;
use Jmsl\Isocrono\Support\DriverFactory;
use Jmsl\Isocrono\Thread\QueryPool;
use PDO;
use PDOException;
use pocketmine\plugin\PluginBase;
use Ramsey\Uuid\Uuid;

class Main extends PluginBase
{

    protected ?QueryPool $pool = null;

    protected function onLoad(): void
    {
        $pdoParams = [
            'mysql:host=127.0.0.1;port=3306;dbname=isocrono',
            'root',
            '',
            [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        ];
        
        $this->pool = Isocrono::create(new DriverFactory(PDODriver::class, $pdoParams), totalThreads: 1);
    }

    protected function onEnable(): void
    {
        if(is_null($this->pool)) {
            return;
        }

        $createTable = <<<SQL
            CREATE TABLE IF NOT EXISTS `test` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
                `uuid` CHAR(36) UNIQUE NOT NULL, 
                `random_number` TINYINT NOT NULL
            )
        SQL;

        Query::prepare($createTable)
            ->resultSuccessfully()
            ->then($this->whenTableExists(...))
            ->catch($this->tableCreationFailed(...))
            ->execute($this->pool);
    }

    protected function whenTableExists(bool $created): void 
    {
        var_dump($created ? 'Table created.' : 'Table already exists.');
        var_dump('Inserting records...');

        for($_ = 0; $_ < 10; $_++) {
            Query::prepare('INSERT INTO `test` (`uuid`, `random_number`) VALUES (:uuid, :random_number)')
                ->bind('uuid', Uuid::uuid4()->toString())
                ->bind('random_number', mt_rand(-128, 127), BindType::INT)
                ->resultAsLastId()
                ->then(static fn(mixed $newRecordId) => var_dump(sprintf('New table record #%s', $newRecordId)))
                ->catch(static fn(PDOException $ex) => var_dump(sprintf('Failed to insert record: %s', $ex->getMessage())))
                ->execute($this->pool);
        }
    }

    protected function tableCreationFailed(PDOException $ex): void 
    {
        var_dump(sprintf('An error occurred while creating/verifying the table: %s', $ex->getMessage()));
    }

    protected function onDisable(): void
    {
        if(is_null($this->pool)) {
            return;
        }

        // HACK: make sure everything has been inserted...
        $this->pool->executePendingQueries();

        // ...and so we can list the latest records
        Query::prepare('SELECT * FROM `test` ORDER BY `id` DESC LIMIT 10')
            ->resultAsAllLines()
            ->then(static fn(array $results) => var_dump($results))
            ->catch(static fn(PDOException $ex) => var_dump(sprintf('Unable to list last inserted records: %s', $ex->getMessage())));

        // By default `stop()` will wait for pending queries to be executed
        $this->pool->stop(waitPendingQueries: true);
    }

}