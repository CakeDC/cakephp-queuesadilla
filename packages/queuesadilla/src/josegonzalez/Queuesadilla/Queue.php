<?php

namespace josegonzalez\Queuesadilla;

use josegonzalez\Queuesadilla\Engine\EngineInterface;
use josegonzalez\Queuesadilla\Event\EventManagerTrait;

class Queue
{
    use EventManagerTrait;

    protected EngineInterface $engine;

    public function __construct(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Push a single job onto the queue.
     *
     * @param string $callable a job callable
     * @param array<int|string, mixed> $args an array of data to set for the job
     * @param array<string, mixed> $options an array of options for publishing the job
     */
    public function push(string $callable, array $args = [], array $options = []): bool
    {
        $queue = $this->engine->setting($options, 'queue');
        $item = [
            'queue' => $queue,
            'class' => $callable,
            'args'  => [$args],
            'id'    => md5(uniqid('', true)),
            'queue_time' => microtime(true),
        ];
        $success = $this->engine->push($item, $options);

        unset($item['id']);
        if ($success) {
            $item['id'] = $this->engine->lastJobId();
        }

        $item['args'] = $args;
        $this->dispatchEvent('Queue.afterEnqueue', [
            'item' => $item,
            'success' => $success,
        ]);

        return $success;
    }
}
