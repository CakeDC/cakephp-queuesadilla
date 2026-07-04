<?php

namespace josegonzalez\Queuesadilla\Engine;

/**
 * Describes a queue engine
 */
interface EngineInterface
{
    /**
     * Returns a string representation of the class name
     */
    public function getJobClass(): string;

    /**
     * Gets a configuration setting
     *
     * @param array<string, mixed>|string $settings an array of settings or queue name
     * @param string $key a key to set or retrieve
     * @param mixed $default a default value to return if the config value does not exist
     */
    public function setting(array|string $settings, string $key, mixed $default = null): mixed;

    /**
     * Creates a connection to the underlying engine datastore
     */
    public function connect(): bool;

    /**
     * Returns a connection to the underlying datastore
     */
    public function connection(): mixed;

    /**
     * Returns the identifier of the last job pushed to the queue.
     */
    public function lastJobId(): string|bool|null;

    /**
     * Acknowledges a message on the queue.
     *
     * @param array<string, mixed> $item an array of item data
     */
    public function acknowledge(array $item): bool;

    /**
     * Rejects a message from the queue.
     *
     * @param array<string, mixed> $item an array of item data
     */
    public function reject(array $item): bool;

    /**
     * Pop the next job off of the queue.
     *
     * @param array<string, mixed> $options an array of options for popping a job from the queue
     * @return array<string, mixed>|null an array of item data
     */
    public function pop(array $options = []): ?array;

    /**
     * Push a single job onto the queue.
     *
     * @param array<string, mixed> $item an item payload
     * @param array<string, mixed> $options an array of options for publishing the job
     */
    public function push(array $item, array $options = []): bool;

    /**
     * Get a list of available queues
     *
     * @return array<int, string>
     */
    public function queues(): array;

    /**
     * Release the job back into the queue.
     *
     * @param array<string, mixed> $item an array of item data
     * @param array<string, mixed> $options an array of options for releasing the job
     */
    public function release(array $item, array $options = []): bool;
}
