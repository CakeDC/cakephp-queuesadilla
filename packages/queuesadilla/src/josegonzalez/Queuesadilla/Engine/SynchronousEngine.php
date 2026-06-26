<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Worker\SequentialWorker;

class SynchronousEngine extends MemoryEngine
{
    public function push(array $item, array $options = []): bool
    {
        parent::push($item, $options);
        $worker = $this->getWorker();

        return (bool)$worker->work();
    }

    protected function getWorker(): SequentialWorker
    {
        return new SequentialWorker($this, $this->logger(), ['maxIterations' => 1]);
    }
}
