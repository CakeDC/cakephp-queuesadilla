<?php

namespace josegonzalez\Queuesadilla\Worker;

declare(ticks = 1);

use Exception;
use josegonzalez\Queuesadilla\Engine\EngineInterface;
use josegonzalez\Queuesadilla\Event\Event;
use josegonzalez\Queuesadilla\Worker\Base;
use Psr\Log\LoggerInterface;

class SequentialWorker extends Base
{
    protected $running;

    public function __construct(EngineInterface $engine, ?LoggerInterface $logger = null, $params = [])
    {
        parent::__construct($engine, $logger, $params);
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGQUIT, [&$this, 'signalHandler']);
            pcntl_signal(SIGTERM, [&$this, 'signalHandler']);
            pcntl_signal(SIGINT, [&$this, 'signalHandler']);
            pcntl_signal(SIGUSR1, [&$this, 'signalHandler']);
        }

        $this->running = true;
    }

    /**
     * events this class listens to
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Worker.job.empty' => 'jobEmpty',
            'Worker.job.exception' => 'jobException',
            'Worker.job.success' => 'jobSuccess',
        ];
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function work()
    {
        if (!$this->connect()) {
            $this->logger()->alert(sprintf('Worker unable to connect, exiting'));
            $this->dispatchEvent('Worker.job.connectionFailed');

            return false;
        }

        $jobClass = $this->engine->getJobClass();
        $time = microtime(true);
        while ($this->running) {
            if (is_int($this->maxRuntime) && $this->runtime >= $this->maxRuntime) {
                $this->logger()->debug('Max runtime reached, exiting');
                $this->dispatchEvent('Worker.maxRuntime');
                break;
            } elseif (is_int($this->maxIterations) && $this->iterations >= $this->maxIterations) {
                $this->logger()->debug('Max iterations reached, exiting');
                $this->dispatchEvent('Worker.maxIterations');
                break;
            }

            $this->runtime += microtime(true) - $time;
            $time = microtime(true);
            $this->iterations++;
            $item = $this->engine->pop($this->queue);
            $this->dispatchEvent('Worker.job.seen', ['item' => $item]);
            if (empty($item)) {
                $this->dispatchEvent('Worker.job.empty');
                sleep($this->interval);
                continue;
            }

            $success = false;
            if (!is_array($item) || !$this->isJobCallable($item['class'] ?? null)) {
                $this->logger()->alert('Invalid callable for job. Rejecting job from queue.');
                $job = is_array($item) ? new $jobClass($item, $this->engine) : null;
                if ($job !== null) {
                    $job->reject();
                }
                $this->dispatchEvent('Worker.job.invalid', ['job' => $job]);
                continue;
            }

            $job = new $jobClass($item, $this->engine);

            $this->dispatchEvent('Worker.job.start', ['job' => $job]);

            try {
                $success = $this->perform($item, $job);
            } catch (Exception $e) {
                $this->dispatchEvent('Worker.job.exception', [
                    'job' => $job,
                    'exception' => $e,
                ]);
            }

            if ($success) {
                $job->acknowledge();
                $this->dispatchEvent('Worker.job.success', ['job' => $job]);
                continue;
            }

            $this->logger()->info('Failed. Releasing job to queue');
            $job->release();
            $this->dispatchEvent('Worker.job.failure', ['job' => $job]);
        }

        return true;
    }

    public function connect()
    {
        $maxIterations = $this->maxIterations ? sprintf(', max iterations %s', $this->maxIterations) : '';
        $this->logger()->info(sprintf('Starting worker%s', $maxIterations));

        return (bool)$this->engine->connection();
    }

    public function perform($item, $job)
    {
        if (!is_array($item) || !$this->isJobCallable($item['class'] ?? null)) {
            return false;
        }

        $success = $this->invokeJobCallable($item['class'], $job);

        if ($success !== false) {
            $success = true;
        }

        return $success;
    }

    /**
     * @param array<int, string>|string $class Job callable.
     * @return bool
     */
    protected function isJobCallable($class): bool
    {
        if (is_array($class) && count($class) === 2) {
            return class_exists($class[0]) && method_exists($class[0], $class[1]);
        }
        if (is_string($class) && function_exists($class)) {
            return true;
        }

        return is_callable($class);
    }

    /**
     * @param array<int, string>|string $class Job callable.
     * @param mixed $job Job instance.
     * @return mixed
     */
    protected function invokeJobCallable($class, $job)
    {
        if (is_array($class) && count($class) === 2) {
            $instance = new $class[0]();
            $method = new \ReflectionMethod($instance, $class[1]);
            $arguments = $method->getNumberOfParameters() > 0 ? [$job] : [];

            return $method->invokeArgs($instance, $arguments);
        }
        if (is_string($class) && function_exists($class)) {
            $function = new \ReflectionFunction($class);
            $arguments = $function->getNumberOfParameters() > 0 ? [$job] : [];

            return $function->invokeArgs($arguments);
        }

        return call_user_func($class, $job);
    }

    protected function disconnect()
    {
    }

    public function signalHandler($signo = null)
    {
        $signals = [];
        if (defined('SIGQUIT')) {
            $signals[SIGQUIT] = 'SIGQUIT';
        }
        if (defined('SIGTERM')) {
            $signals[SIGTERM] = 'SIGTERM';
        }
        if (defined('SIGINT')) {
            $signals[SIGINT] = 'SIGINT';
        }
        if (defined('SIGUSR1')) {
            $signals[SIGUSR1] = 'SIGUSR1';
        }

        if ($signo !== null && isset($signals[$signo])) {
            $signal = $signals[$signo];
            $this->logger->info(sprintf('Received %s... Shutting down', $signal));
        }

        if (defined('SIGQUIT') && $signo === SIGQUIT) {
            $this->logger()->debug('SIG: Caught SIGQUIT');
            $this->running = false;
        } elseif (defined('SIGTERM') && $signo === SIGTERM) {
            $this->logger()->debug('SIG: Caught SIGTERM');
            $this->running = false;
        } elseif (defined('SIGINT') && $signo === SIGINT) {
            $this->logger()->debug('SIG: Caught CTRL+C');
            $this->running = false;
        } elseif (defined('SIGUSR1') && $signo === SIGUSR1) {
            $this->logger()->debug('SIG: Caught SIGUSR1');
            $this->running = false;
        } else {
            $this->logger()->debug('SIG:received other signal');
        }

        return true;
    }

    public function shutdownHandler($signo = null)
    {
        $this->disconnect();

        $this->logger->info(sprintf(
            "Worker shutting down after running %d iterations in %ds",
            $this->iterations,
            $this->runtime
        ));

        return true;
    }

    /**
     * Event triggered on Worker.job.empty
     *
     * @param Event $event
     * @return void
     */
    public function jobEmpty(Event $event)
    {
        $event;
        $this->logger()->debug('No job!');
    }

    /**
     * Event triggered on Worker.job.exception
     *
     * @param Event $event
     * @return void
     */
    public function jobException(Event $event)
    {
        $data = $event->data();
        $this->logger()->alert(sprintf('Exception: "%s"', $data['exception']->getMessage()));
    }

    /**
     * Event triggered on Worker.job.success
     *
     * @param Event $event
     * @return void
     */
    public function jobSuccess(Event $event)
    {
        $event;
        $this->logger()->debug('Success. Acknowledging job on queue.');
    }
}
