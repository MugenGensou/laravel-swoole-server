<?php

namespace Mugen\LaravelSwooleServer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Mugen\LaravelSwooleServer\Commands\Traits\CommandHelper;
use Mugen\LaravelSwooleServer\Commands\Traits\WatcherHelper;
use Mugen\LaravelSwooleServer\Contracts\AbstractManager;

class SwooleServerCommand extends Command
{
    use CommandHelper,
        WatcherHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:server {action : start|stop|reload|restart|watch}
                                          {server}                                          
                                          {--d|daemonize}';

    /**
     * @var string
     */
    protected $action;

    /**
     * @var string
     */
    protected $serverName;

    /**
     * @var AbstractManager
     */
    protected $server;

    /**
     * @var array
     */
    protected $servers;

    public function handle()
    {
        $this->initAction();
        $this->initServer();
        $this->runAction();
    }

    /**
     * Start swoole server.
     */
    public function start()
    {
        if ($this->server->isRunning())
            $this->exit("Failed, {$this->serverName} server is already running.");

        $this->info("Starting {$this->serverName} server...");
        $this->info("> (Run this command to ensure the {$this->serverName} server process is running: ps aux | grep \"swoole\")");

        $this->server->start();
    }

    /**
     * Stop swoole server.
     */
    public function stop()
    {
        if (!$this->server->isRunning())
            $this->exit("Failed, {$this->serverName} server process is not running.");

        $this->info("Stopping {$this->serverName} server...");

        $start = time();
        $this->server->stop();
        do {
            if (!$this->server->isRunning())
                break;

            usleep(100000);
        } while (time() < $start + 15);

        $this->server->isRunning()
            ? $this->exit("Unable to stop the {$this->serverName} server process.")
            : $this->info('> success');
    }

    /**
     * Reload swoole server.
     */
    public function reload()
    {
        if (!$this->server->isRunning())
            $this->exit("Failed, {$this->serverName} server process is not running.");

        $this->info("Reloading {$this->serverName} server...");

        $this->server->reload();

        $this->server->isRunning()
            ? $this->info('> success')
            : $this->exit('> failure');
    }

    /**
     * Restart swoole server.
     */
    public function restart()
    {
        if ($this->server->isRunning())
            $this->stop();

        $this->start();
    }

    /**
     * Watch swoole server.
     */
    public function watch()
    {
        if ($this->server->isRunning())
            $this->stop();

        if (!extension_loaded('inotify'))
            $this->exit('Extension inotify is required!');

        $this->removeWatchedFile();

        Event::listen("swoole.{$this->serverName}.start", function () {
            $this->createWatcher(function () {
                exec("php artisan swoole:server reload {$this->serverName} -d > /dev/null &");
            })->watch();
        });

        Event::listen("swoole.{$this->serverName}.shutdown", function () {
            $this->removeWatchedFile();
        });

        $this->warn("Watching at {$this->serverName} server ...");
        $this->start();
    }

    /**
     * Initialize servers.
     */
    protected function initServer()
    {
        $this->servers    = array_keys($this->config('servers'));
        $this->serverName = $this->argument('server');

        if (!in_array($this->serverName, $this->servers))
            $this->exit("Failed, {$this->serverName} server not exists.");

        $this->server = $this->createServer();
    }

    /**
     * Initialize command action.
     */
    protected function initAction()
    {
        $this->action = $this->argument('action');

        if (!in_array($this->action, ['start', 'stop', 'reload', 'restart', 'watch']))
            $this->exit("Unexpected action argument {$this->action} .");
    }

    /**
     * Run action.
     */
    protected function runAction()
    {
        $this->detectSwoole();

        $this->{$this->action}();
    }

    /**
     * Extension swoole is required.
     */
    protected function detectSwoole()
    {
        if (!extension_loaded('swoole'))
            $this->exit('Extension swoole is required!');
    }

    /**
     * @return \Mugen\LaravelSwooleServer\Contracts\AbstractManager
     */
    protected function createServer()
    {
        $config = $this->config("servers.{$this->serverName}");

        $config['options'] = array_merge($config['options'] ?? [], [
            'pid_file'  => $this->config('default.options.pid_dir') . "{$this->serverName}.pid",
            'log_file'  => $this->config('default.options.log_dir') . "{$this->serverName}.log",
            'daemonize' => $this->option('daemonize') && $this->action !== 'watch',
        ]);

        $managerClass = "\\Mugen\\LaravelSwooleServer\\Server\\" . ucfirst(camel_case($config['type'])) . 'Manager';
        if (!isset($config['type']) || !class_exists($managerClass))
            $this->exit("Failed, unknown {$managerClass}.");

        return new $managerClass($this->getLaravel(), $this->serverName, $config);
    }
}
