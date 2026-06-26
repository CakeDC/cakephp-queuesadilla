<?php

namespace josegonzalez\Queuesadilla\Utility;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait LoggerTrait
{
    protected ?LoggerInterface $logger = null;

    public function setLogger(?LoggerInterface $logger = null): LoggerInterface
    {
        if ($logger === null) {
            $logger = new NullLogger();
        }

        return $this->logger = $logger;
    }

    public function logger(): LoggerInterface
    {
        if ($this->logger === null) {
            $this->setLogger(null);
        }

        return $this->logger;
    }
}
