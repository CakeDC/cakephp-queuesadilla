<?php
declare(strict_types=1);

namespace Josegonzalez\CakeQueuesadilla\Test\TestCase\Engine;

use Cake\Datasource\ConnectionManager;
use Cake\Log\Engine\FileLog;
use Cake\TestSuite\TestCase;
use Josegonzalez\CakeQueuesadilla\Engine\CakeEngine;
use Josegonzalez\CakeQueuesadilla\Queue\Queue;
use PDO;

/**
 * CakeEngineTest class
 */
class CakeEngineTest extends TestCase
{
    /**
     * setup method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Queue::reset();
    }

    /**
     * teardown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Queue::reset();
    }

    /**
     * test config() with valid datasource name
     *
     * @return void
     */
    public function testValidConfig(): void
    {
        Queue::setConfig('valid', [
            'className' => CakeEngine::class,
            'datasource' => 'test',
        ]);
        $engine = Queue::engine('valid');
        $this->assertInstanceOf(CakeEngine::class, $engine);
        $this->assertSame('test', $engine->config('datasource'));
    }

    /**
     * test config() with invalid datasource name
     *
     * @return void
     */
    public function testInvalidDatasourceName(): void
    {
        $logger = $this->createMock(FileLog::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('The datasource configuration `wrong-datasource` was not found.');
        Queue::setConfig('invalid', [
            'className' => CakeEngine::class,
            'datasource' => 'wrong-datasource',
        ]);
        $engine = Queue::engine('invalid');
        $engine->setLogger($logger);
        $this->assertInstanceOf(CakeEngine::class, $engine);
        $this->assertSame('wrong-datasource', $engine->config('datasource'));
        $engine->connect();
    }

    /**
     * test config() with invalid datasource config (missing datasource class)
     *
     * @return void
     */
    public function testInvalidConfig(): void
    {
        $logger = $this->createMock(FileLog::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Datasource class `wrong-datasource-class` could not be found. ');
        Queue::setConfig('invalid', [
            'className' => CakeEngine::class,
            'datasource' => 'wrong-datasource-class',
        ]);
        ConnectionManager::setConfig('wrong-datasource-class', [
            'user' => 'invalid-user',
            'password' => 'invalid-password',
            'host' => 'localhost',
        ]);
        $engine = Queue::engine('invalid');
        $engine->setLogger($logger);
        $this->assertFalse($engine->connect());
    }

    /**
     * test config() with invalid datasource connection params
     *
     * @return void
     */
    public function testInvalidConfigParams(): void
    {
        $logger = $this->createMock(FileLog::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->matchesRegularExpression('/Connection to Mysql could not be established:/'));
        Queue::setConfig('invalid', [
            'className' => CakeEngine::class,
            'datasource' => 'wrong-datasource-params',
        ]);
        ConnectionManager::setConfig('wrong-datasource-params', [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'user' => 'invalid-user',
            'password' => 'invalid-password',
            'host' => 'localhost',
        ]);
        $engine = Queue::engine('invalid');
        $engine->setLogger($logger);
        $this->assertFalse($engine->connect());
    }

    /**
     * test config with noDatasource (default)
     *
     * @return void
     */
    public function testNoDatasource(): void
    {
        Queue::setConfig('noDatasource', [
            'className' => CakeEngine::class,
        ]);
        $engine = Queue::engine('noDatasource');
        $this->assertInstanceOf(CakeEngine::class, $engine);
        $this->assertSame('default', $engine->config('datasource'));
    }

    /**
     * test connection
     *
     * @return void
     */
    public function testConnection(): void
    {
        Queue::setConfig('valid', [
            'className' => CakeEngine::class,
            'datasource' => 'test',
        ]);
        $engine = Queue::engine('valid');
        $this->assertInstanceOf(CakeEngine::class, $engine);
        $this->assertSame('test', $engine->config('datasource'));
        $this->assertTrue($engine->connect());
        $this->assertInstanceOf(PDO::class, $engine->connection());
    }
}
