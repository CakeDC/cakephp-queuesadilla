<?php
declare(strict_types=1);

namespace Josegonzalez\CakeQueuesadilla\Test\App;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Josegonzalez\CakeQueuesadilla\CakeQueuesadillaPlugin;

class Application extends BaseApplication
{
    /**
     * @inheritDoc
     */
    public function bootstrap(): void
    {
        parent::bootstrap();
        $this->addPlugin(CakeQueuesadillaPlugin::class);
    }

    /**
     * @inheritDoc
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }
}
