<?php

namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\Base;

class NullEngine extends Base
{
    /**
     * @var array<string, mixed>
     */
    protected $baseConfig = [
        'queue' => 'default',
    ];

    public bool|array|null $return = true;

    public function connect(): bool
    {
        $this->connection = $this->return;

        return (bool)$this->return;
    }

    public function acknowledge(array $item): bool
    {
        if (!parent::acknowledge($item)) {
            return false;
        }

        return (bool)$this->return;
    }

    public function reject(array $item): bool
    {
        return $this->acknowledge($item);
    }

    public function pop(array $options = []): ?array
    {
        return is_array($this->return) ? $this->return : null;
    }

    public function push(array $item, array $options = []): bool
    {
        $this->lastJobId = $this->return;

        return (bool)$this->return;
    }

    public function queues(): array
    {
        return [];
    }

    public function release(array $item, array $options = []): bool
    {
        return (bool)$this->return;
    }
}
