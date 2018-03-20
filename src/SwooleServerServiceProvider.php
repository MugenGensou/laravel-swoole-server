<?php

namespace Mugen\LaravelSwooleServer;

use Illuminate\Support\ServiceProvider;
use Mugen\LaravelSwooleServer\Commands\SwooleServerCommand;
use Mugen\LaravelSwooleServer\Commands\SwooleServersCommand;
use Mugen\LaravelSwooleServer\Commands\SwooleWatchCommand;

class SwooleServerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfig();
        $this->registerCommands();
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/swoole-server.php' => base_path('config/swoole-server.php')
        ]);
    }

    /**
     * Merge configurations.
     */
    protected function mergeConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/swoole-server.php', 'swoole-server');
    }

    /**
     * Register commands.
     */
    protected function registerCommands()
    {
        $this->commands([
            SwooleServerCommand::class,
            SwooleServersCommand::class,
            SwooleWatchCommand::class,
        ]);
    }
}