<?php

namespace josegonzalez\Queuesadilla\Event;

use League\Event\AbstractListener;
use League\Event\EventInterface;

abstract class MultiEventListener extends AbstractListener implements EventListenerInterface
{
    abstract public function implementedEvents(): array;

    public function handle(EventInterface $event): mixed
    {
        $events = $this->implementedEvents();
        if (empty($events)) {
            return null;
        }
        if (!isset($events[$event->getName()])) {
            return null;
        }

        $handler = $events[$event->getName()];
        if (!method_exists($this, $handler)) {
            return null;
        }

        return $this->$handler($event);
    }
}
