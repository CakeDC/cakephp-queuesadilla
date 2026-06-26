<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\FixtureData;
use josegonzalez\Queuesadilla\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Base test case for queue engine integration tests.
 *
 * @property string $url
 * @property array<string, mixed> $config
 * @property LoggerInterface $Logger
 * @property EngineInterface $Engine
 * @property FixtureData $Fixtures
 * @property class-string<EngineInterface> $engineClass
 */
abstract class EngineTestCase extends TestCase
{
    protected string $url;

    /**
     * @var array<string, mixed>
     */
    protected array $config;

    protected LoggerInterface $Logger;

    protected EngineInterface $Engine;

    protected FixtureData $Fixtures;

    /**
     * @var class-string<EngineInterface>
     */
    protected string $engineClass;
}
