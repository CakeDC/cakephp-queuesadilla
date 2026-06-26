<?php

namespace josegonzalez\Queuesadilla;

use InvalidArgumentException;
use josegonzalez\Queuesadilla\Engine\NullEngine;
use josegonzalez\Queuesadilla\Utility\DsnParserTrait;
use josegonzalez\Queuesadilla\Utility\LoggerTrait;
use ReflectionClass;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param object $object Object instance.
     * @param string $methodName Method name.
     * @param array<int, mixed> $parameters Method parameters.
     * @return mixed
     */
    protected function protectedMethodCall(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * @param string $traitName Trait class name.
     * @return object
     */
    protected function getObjectForTrait(string $traitName): object
    {
        return match ($traitName) {
            DsnParserTrait::class => new class {
                use DsnParserTrait;
            },
            LoggerTrait::class => new class {
                use LoggerTrait;
            },
            default => throw new InvalidArgumentException(sprintf('Unknown trait: %s', $traitName)),
        };
    }

    /**
     * @param class-string $className Class name.
     * @param list<string>|null $methods Methods to mock.
     * @param array<int, mixed> $constructorArgs Constructor arguments.
     * @return object
     */
    protected function buildPartialMock(string $className, ?array $methods, array $constructorArgs = []): object
    {
        $builder = $this->getMockBuilder($className)
            ->setConstructorArgs($constructorArgs);
        if ($methods !== null) {
            $builder->onlyMethods($methods);
        }

        return $builder->getMock();
    }

    /**
     * @param class-string $engineClass Engine class name.
     * @param list<string>|null $methods Methods to mock.
     * @param mixed $logger Logger instance.
     * @param mixed $config Engine configuration.
     * @return object
     */
    protected function mockEngineInstance(string $engineClass, ?array $methods, $logger, $config): object
    {
        if ($methods === null) {
            return new $engineClass($logger, $config);
        }

        return $this->buildPartialMock($engineClass, $methods, [$logger, $config]);
    }

    /**
     * @param array<int, mixed> $returns Pop return values in call order.
     * @return NullEngine
     */
    protected function createNullEnginePopStub(array $returns): NullEngine
    {
        return new class($returns) extends NullEngine {
            private array $returns;

            private int $index = 0;

            public function __construct(array $returns)
            {
                $this->returns = $returns;
            }

            public function pop(array $options = []): ?array
            {
                if (!array_key_exists($this->index, $this->returns)) {
                    return null;
                }

                $value = $this->returns[$this->index++];

                return is_array($value) ? $value : null;
            }
        };
    }
}
