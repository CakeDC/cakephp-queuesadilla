<?php
declare(strict_types=1);

namespace Josegonzalez\CakeQueuesadilla\Engine;

use Cake\Core\Exception\CakeException;
use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Hash;
use josegonzalez\Queuesadilla\Engine\PdoEngine;
use PDO;
use ReflectionMethod;

class CakeEngine extends PdoEngine
{
    /**
     * Base config
     *
     * @var array<string, mixed>
     */
    protected $baseConfig = [
        'delay' => null,
        'expires_in' => null,
        'priority' => 0,
        'queue' => 'default',
        'attempts' => 0,
        'attempts_delay' => 600,
        'table' => 'jobs',
        'datasource' => 'default',
    ];

    /**
     * @return bool
     */
    public function connect(): bool
    {
        $config = $this->settings;
        try {
            /** @var \Cake\Database\Connection $connection */
            $connection = ConnectionManager::get(Hash::get($config, 'datasource'));
            $this->connection = $this->getPdoFromConnection($connection);
        } catch (CakeException $e) {
            $this->logger()->error($e->getMessage());
            $this->connection = null;
        }

        return (bool)$this->connection;
    }

    /**
     * Returns the native PDO connection from a CakePHP database connection.
     *
     * @param \Cake\Database\Connection $connection Cake database connection.
     * @return \PDO
     */
    protected function getPdoFromConnection(Connection $connection): PDO
    {
        $driver = $connection->getDriver();
        $method = new ReflectionMethod(Driver::class, 'getPdo');

        return $method->invoke($driver);
    }
}
