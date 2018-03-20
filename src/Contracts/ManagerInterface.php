<?php

namespace Mugen\LaravelSwooleServer\Contracts;

interface ManagerInterface
{
    /**
     * Start swoole server.
     */
    public function start();

    /**
     * Stop swoole server.
     */
    public function stop();

    /**
     * Reload swoole server.
     */
    public function reload();

    /**
     * Restart swoole server.
     */
    public function restart();
}
