<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\FixtureData;
use josegonzalez\Queuesadilla\TestCase;

abstract class EngineTestCase extends TestCase
{
    protected $url;

    protected $config;

    protected $Logger;

    protected $Engine;

    protected $Fixtures;

    protected $engineClass;
}
