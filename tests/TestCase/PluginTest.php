<?php
declare(strict_types=1);

namespace Josegonzalez\CakeQueuesadilla\Test\TestCase;

use Cake\TestSuite\TestCase;
use Josegonzalez\CakeQueuesadilla\CakeQueuesadillaPlugin;
use Josegonzalez\CakeQueuesadilla\Command\QueuesadillaCommand;

/**
 * PluginTest class
 */
class PluginTest extends TestCase
{
    /**
     * Test that the plugin class loads.
     *
     * @return void
     */
    public function testPluginLoads(): void
    {
        $this->assertInstanceOf(CakeQueuesadillaPlugin::class, new CakeQueuesadillaPlugin());
    }

    /**
     * Test that the command class loads.
     *
     * @return void
     */
    public function testCommandLoads(): void
    {
        $this->assertInstanceOf(QueuesadillaCommand::class, new QueuesadillaCommand());
    }
}
