<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\RedisEngine;
use josegonzalez\Queuesadilla\FixtureData;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @property string $url
 * @property array<string, mixed> $config
 * @property LoggerInterface $Logger
 * @property RedisEngine $Engine
 * @property FixtureData $Fixtures
 * @property class-string<EngineInterface> $engineClass
 */
class RedisEngineTest extends EngineTestCase
{
    public function setUp() : void
    {
        if (!class_exists('Redis')) {
            $this->markTestSkipped('Redis extension is not installed or configured properly.');
        }

        $this->url = getenv('REDIS_URL');
        if (empty($this->url)) {
            $this->markTestSkipped('REDIS_URL is not configured.');
        }
        $this->config = ['url' => $this->url];
        $this->Logger = new NullLogger;
        $this->engineClass = 'josegonzalez\Queuesadilla\Engine\RedisEngine';
        $this->Engine = $this->mockEngine();
        if (!$this->Engine->connect()) {
            unset($this->Engine);
            $this->markTestSkipped('No connection to Redis available.');
        }
        $this->Fixtures = new FixtureData;
        $this->clearEngine();
    }

    public function tearDown() : void
    {
        if (!isset($this->Engine)) {
            return;
        }
        $this->clearEngine();
        unset($this->Engine);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::__construct
     */
    public function testConstruct()
    {
        $Engine = new RedisEngine($this->Logger, []);
        $this->assertNotNull($Engine->connection());

        $Engine = new RedisEngine($this->Logger, $this->url);
        $this->assertNotNull($Engine->connection());

        $Engine = new RedisEngine($this->Logger, $this->config);
        $this->assertNotNull($Engine->connection());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::connect
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::redisInstance
     */
    public function testConnect()
    {
        $this->assertTrue($this->Engine->connect());

        $config = $this->config;
        $Engine = $this->mockEngine(null, $config);
        $Engine->config('database', 1);
        $this->assertTrue($Engine->connect());

        $config = $this->config;
        $Engine = $this->mockEngine(null, $config);
        $Engine->config('persistent', false);
        $this->assertTrue($Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::connect
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::redisInstance
     */
    public function testConnectAuth()
    {
        $config = $this->config;
        $Engine = $this->mockEngine(null, $config);
        $Engine->config('pass', 'some_password');
        $this->assertFalse($Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::connect
     */
    public function testConnectionException()
    {
        $Engine = $this->mockEngine(['redisInstance']);
        $Engine->expects($this->once())
                ->method('redisInstance')
                ->will($this->throwException(new \Exception('Connection refused')));

        $this->assertFalse($Engine->connect());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\Base::getJobClass
     */
    public function testGetJobClass()
    {
        $this->assertEquals('\\josegonzalez\\Queuesadilla\\Job\\Base', $this->Engine->getJobClass());
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::acknowledge
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::evalSha
     */
    public function testAcknowledge()
    {
        $this->assertFalse($this->Engine->acknowledge(['key' => 'value']));
        $this->assertFalse($this->Engine->acknowledge($this->Fixtures->default['first']));

        $this->assertTrue($this->Engine->push($this->Fixtures->default['first']));
        $this->assertTrue($this->Engine->push($this->Fixtures->other['third']));
        $this->assertTrue($this->Engine->acknowledge($this->Fixtures->default['first']));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::reject
     */
    public function testReject()
    {
        $this->assertFalse($this->Engine->reject(['key' => 'value']));
        $this->assertFalse($this->Engine->reject($this->Fixtures->default['first']));

        $this->assertTrue($this->Engine->push($this->Fixtures->default['first']));
        $this->assertTrue($this->Engine->push($this->Fixtures->other['third']));
        $this->assertTrue($this->Engine->reject($this->Fixtures->default['first']));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::pop
     */
    public function testPop()
    {
        $this->assertNull($this->Engine->pop(['queue' => 'default']));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], ['queue' => 'default']));
        $this->assertEquals($this->Fixtures->default['first'], $this->Engine->pop(['queue' => 'default']));
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::push
     */
    public function testPush()
    {
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], ['queue' => 'default']));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['second'], [
            'delay' => 30,
        ]));
        $this->assertTrue($this->Engine->push($this->Fixtures->other['third'], [
            'expires_in' => 1,
        ]));
        $this->assertTrue($this->Engine->push($this->Fixtures->default['fourth'], ['queue' => 'default']));

        sleep(2);

        $pop1 = $this->Engine->pop();
        $pop2 = $this->Engine->pop();
        $pop3 = $this->Engine->pop();
        $pop4 = $this->Engine->pop();

        $this->assertNull($pop1['class']);
        $this->assertEmpty($pop1['args']);

        $this->markTestIncomplete(
            'RedisEngine does not yet implement delay or expires_in (tbd sorted sets)'
        );

        $this->assertEquals('yet_another_function', $pop2['class']);
        $this->assertNull($pop3);
        $this->assertNull($pop4);
    }

    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::pop
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::release
     */
    public function testRelease()
    {
        $this->assertTrue($this->Engine->push($this->Fixtures->default['first'], ['queue' => 'default']));
        $this->assertEquals($this->Fixtures->default['first'], $this->Engine->pop(['queue' => 'default']));

        $this->assertFalse($this->Engine->release([], ['queue' => 'default']));

        $this->assertEquals(false, $this->Engine->release($this->Fixtures->default['second'], ['queue' => 'default']));
        $this->assertEquals(null, $this->Engine->pop(['queue' => 'default']));

        $this->assertEquals(1, $this->Engine->release($this->Fixtures->default['fifth'], ['queue' => 'default']));
        $this->assertEquals($this->Fixtures->default['fifth'], $this->Engine->pop(['queue' => 'default']));
    }
    /**
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::queues
     * @covers josegonzalez\Queuesadilla\Engine\RedisEngine::requireQueue
     */
    public function testQueues()
    {
        $this->assertEquals([], $this->Engine->queues());
        $this->Engine->push($this->Fixtures->default['first']);
        $this->assertEquals(['default'], $this->Engine->queues());

        $this->Engine->push($this->Fixtures->other['second'], ['queue' => 'other']);
        $queues = $this->Engine->queues();
        sort($queues);
        $this->assertEquals(['default', 'other'], $queues);

        $this->Engine->pop();
        $this->Engine->pop();
        $queues = $this->Engine->queues();
        sort($queues);
        $this->assertEquals(['default', 'other'], $queues);
    }

    protected function clearEngine()
    {
        $this->Engine->connection()->flushdb();
        $this->Engine->connection()->script('flush');
    }

    protected function mockEngine($methods = null, $config = null)
    {
        if ($config === null) {
            $config = $this->config;
        }

        return $this->mockEngineInstance($this->engineClass, $methods, $this->Logger, $config);
    }
}
