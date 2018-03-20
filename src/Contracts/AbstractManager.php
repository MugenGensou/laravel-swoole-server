<?php

namespace Mugen\LaravelSwooleServer\Contracts;

use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Swoole\Process;
use Swoole\Server;

abstract class AbstractManager implements ManagerInterface
{
    const MAC_OSX = 'Darwin';

    /**
     * @var \Illuminate\Container\Container|\Illuminate\Foundation\Application
     */
    protected $container;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Swoole\Server
     */
    protected $server;

    /**
     * @var AbstractHandler
     */
    protected $handler;

    /**
     * @var integer
     */
    protected $pid;

    /**
     * @var array
     */
    protected $events = [
        'start',
        'shutdown',
        'workerStart',
        'workerStop',
        'workerExit',
        'connect',
        'receive',
        'packet',
        'close',
        'bufferFull',
        'bufferEmpty',
        'task',
        'finish',
        'pipeMessage',
        'workerError',
        'managerStart',
        'managerStop',
    ];

    public function __construct(Container $container, string $name, array $config)
    {
        $this->container = $container;
        $this->name      = $name;
        $this->config    = $config;
    }

    /**
     * Initialize.
     */
    protected function initialize()
    {
        $this->setProcessName('manager');

        $this->server = $this->createSwooleServer();
        $this->getServer()->set($this->config('options', []));

        $this->setListeners();
    }

    /**
     * Creates swoole server.
     *
     * @return Server
     */
    abstract protected function createSwooleServer(): Server;

    /**
     * Return event handler.
     *
     * @return AbstractHandler
     */
    abstract protected function handler(): AbstractHandler;

    /**
     * Set
     * @param string $process
     */
    final protected function setProcessName(string $process)
    {
        // Mac OS doesn't support swoole_set_process_name function.
        if (PHP_OS === static::MAC_OSX)
            return;

        $appName = $this->container->make('config')->get('app.name');
        $name    = sprintf('swoole.%s.%s for %s', $this->name, $process, $appName);

        @cli_set_process_title($name);
    }

    /**
     * Set event listener.
     */
    final protected function setListeners()
    {
        foreach ($this->getEvents() as $event) {
            $this->getServer()->on($event, function () use ($event) {
                $listener = 'on' . ucfirst($event);

                if (method_exists($this, $listener))
                    call_user_func_array([$this, $listener], func_get_args());

                if (method_exists($this->getHandler(), $listener))
                    call_user_func_array([$this->getHandler(), $listener], func_get_args());

                $this->container->make('events')->dispatch("swoole.{$this->name}.{$event}", func_get_args());
            });
        }
    }

    /**
     * Get events.
     *
     * @return array
     */
    final protected function getEvents()
    {
        return array_unique(array_merge($this->events, $this->getHandler()->getEvents()));
    }

    /**
     * Get listener handler.
     * @return AbstractHandler
     */
    final protected function getHandler()
    {
        if (!$this->handler) {
            $this->handler = class_exists($handlerClass = $this->config('handler'))
                ? new $handlerClass($this->container, $this->name)
                : $this->handler();
        }

        return $this->handler;
    }

    /**
     * @return Server
     */
    final protected function getServer()
    {
        return $this->server;
    }

    /**
     * Get config.
     * @param string $key
     * @param null   $default
     * @return mixed
     */
    final protected function config(string $key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * Clear APC or OPCache.
     */
    final protected function clearCache()
    {
        if (function_exists('apc_clear_cache'))
            apc_clear_cache();

        if (function_exists('opcache_reset'))
            opcache_reset();
    }

    /**
     * Create pid file.
     */
    final protected function createPidFile()
    {
        $pidFile = $this->getPidFile();
        $pid     = $this->getServer()->master_pid;

        $this->removePidFile();
        file_put_contents($pidFile, $pid);
    }

    /**
     * Remove pid file.
     */
    final protected function removePidFile()
    {
        file_exists($pidFile = $this->getPidFile()) && unlink($pidFile);
    }

    /**
     * Gets pid file path.
     *
     * @return string
     */
    final protected function getPidFile()
    {
        return $this->config('options.pid_file', storage_path("logs/{$this->name}.pid"));
    }

    /**
     * @return bool|string
     */
    final protected function getPid()
    {
        if ($this->pid)
            return $this->pid;

        if ($this->getServer()) {
            $pid = $this->getServer()->master_pid;
        } else {
            $pid = file_exists($pidFile = $this->getPidFile())
                ? file_get_contents($pidFile)
                : null;
        }

        if (!$pid)
            $this->removePidFile();

        return $this->pid = (int)$pid;
    }

    /**
     * Check this server is running.
     *
     * @return bool
     */
    final public function isRunning()
    {
        return ($pid = $this->getPid()) ? Process::kill($pid, 0) : false;
    }

    /**
     * "onStart" listener.
     */
    public function onStart()
    {
        $this->setProcessName('master');

        $this->createPidFile();
    }

    /**
     * "onWorkerStart" listener.
     */
    public function onWorkerStart()
    {
        $this->clearCache();

        $this->setProcessName("worker.{$this->getServer()->worker_id}");
    }

    /**
     * "onShutdown" listener.
     */
    public function onShutdown()
    {
        $this->removePidFile();
    }

    /**
     * Start swoole server.
     */
    public function start()
    {
        $this->pid = null;

        $this->initialize();

        return $this->getServer()->start();
    }

    /**
     * Stop swoole server.
     */
    public function stop()
    {
        return Process::kill($this->getPid(), SIGTERM);
    }

    /**
     * Reload swoole server.
     */
    public function reload()
    {
        return Process::kill($this->getPid(), SIGUSR1);
    }

    /**
     * Restart swoole server.
     */
    public function restart()
    {
        $this->stop();

        return $this->start();
    }
}
