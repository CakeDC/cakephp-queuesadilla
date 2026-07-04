<?php

namespace josegonzalez\Queuesadilla\Engine;

use IronMQ;
use josegonzalez\Queuesadilla\Engine\Base;

class IronEngine extends Base
{
    /**
     * @var array<string, mixed>
     */
    protected $baseConfig = [
        'api_version' => 1,
        'delay' => null,
        'expires_in' => null,
        'host' => 'mq-aws-us-east-1.iron.io',
        'port' => 443,
        'project_id' => null,
        'protocol' => 'https',
        'queue' => 'default',
        'token' => null,
        'time_to_run' => 60,
    ];

    /**
     * @var array<int, string>
     */
    protected array $ironSettings = [
        'api_version',
        'host',
        'port',
        'project_id',
        'protocol',
        'token',
    ];

    public function connect(): bool
    {
        $settings = [];
        foreach ($this->ironSettings as $key) {
            $settings[$key] = $this->config($key);
        }

        $this->connection = new IronMQ($settings);

        return (bool)$this->connection;
    }

    public function acknowledge(array $item): bool
    {
        if (!parent::acknowledge($item)) {
            return false;
        }

        return (bool)$this->connection()->deleteMessage($item['queue'], $item['id']);
    }

    public function reject(array $item): bool
    {
        return $this->acknowledge($item);
    }

    public function pop(array $options = []): ?array
    {
        $queue = $this->setting($options, 'queue');
        $item = $this->connection()->getMessage($queue);
        if (!$item) {
            return null;
        }

        $data = json_decode($item->body, true);

        return [
            'id' => $item->id,
            'class' => $data['class'],
            'args' => $data['args'],
            'queue' => $queue,
        ];
    }

    public function push(array $item, array $options = []): bool
    {
        $queue = $this->setting($options, 'queue');

        $payload = json_encode([
            'class' => $item['class'],
            'args' => $item['args'],
            'queue' => $queue,
        ]);

        return (bool)$this->connection()->postMessage($queue, $payload, [
            'timeout' => $this->config('time_to_run'),
            'delay' => $this->config('delay'),
            'expires_in' => $this->config('expires_in'),
        ]);
    }

    public function queues(): array
    {
        return $this->connection()->getQueues();
    }

    public function release(array $item, array $options = []): bool
    {
        $queue = $this->setting($options, 'queue');

        return (bool)$this->connection()->postMessage($queue, json_encode($item), [
            'timeout' => $this->config('time_to_run'),
            'delay' => $this->config('delay'),
            'expires_in' => $this->config('expires_in'),
        ]);
    }
}
