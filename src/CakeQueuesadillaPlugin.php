<?php
declare(strict_types=1);

namespace Josegonzalez\CakeQueuesadilla;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Josegonzalez\CakeQueuesadilla\Command\QueuesadillaCommand;

/**
 * Queuesadilla Plugin
 */
class CakeQueuesadillaPlugin extends BasePlugin
{
    /**
     * Add routes for the plugin.
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            'Josegonzalez/CakeQueuesadilla',
            ['path' => '/queuesadilla'],
            function (RouteBuilder $builder): void {
                $builder->fallbacks(DashedRoute::class);
            },
        );
        parent::routes($routes);
    }

    /**
     * Register console commands.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update.
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands->add('queuesadilla', QueuesadillaCommand::class);
    }
}
