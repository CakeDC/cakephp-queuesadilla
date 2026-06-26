<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Utility\DsnParserTrait;
use josegonzalez\Queuesadilla\Utility\LoggerTrait;
use josegonzalez\Queuesadilla\Utility\SettingTrait;
use Psr\Log\LoggerInterface;

abstract class Base implements EngineInterface
{
    use DsnParserTrait;

    use LoggerTrait;

    use SettingTrait;

    /**
     * @var array<string, mixed>
     */
    protected $baseConfig = [];

    protected mixed $connection = null;

    public string|bool|null $lastJobId = null;

    /**
     * @param array<string, mixed>|string $config
     */
    public function __construct(?LoggerInterface $logger = null, array|string $config = [])
    {
        if (is_array($config) && !empty($config['url'])) {
            $url = $config['url'];
            unset($config['url']);
            $config = array_merge($config, $this->parseDsn($url));
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        }

        $this->setLogger($logger);
        $this->config($this->baseConfig);
        $this->config($config);
    }

    public function getJobClass(): string
    {
        return '\\josegonzalez\\Queuesadilla\\Job\\Base';
    }

    public function connection(): mixed
    {
        if ($this->connection === null) {
            $this->connect();
        }

        return $this->connection;
    }

    public function lastJobId(): string|bool|null
    {
        return $this->lastJobId;
    }

    /**
     * @param array<string, mixed> $item
     */
    public function acknowledge(array $item): bool
    {
        return !empty($item['id']) && !empty($item['queue']);
    }
}
