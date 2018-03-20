<?php

namespace Mugen\LaravelSwooleServer\Server;

use Mugen\LaravelSwooleServer\Contracts\AbstractHandler;
use Mugen\LaravelSwooleServer\Contracts\AbstractManager;
use Mugen\LaravelSwooleServer\Handlers\WebSocketHandler;
use Swoole\Server;
use Swoole\WebSocket\Server as WebSocketServer;

class WebSocketManager extends AbstractManager
{
    /**
     * Creates swoole server.
     *
     * @return \Swoole\Server
     */
    protected function createSwooleServer(): Server
    {
        $host = $this->config('host', '127.0.0.1');
        $port = $this->config('port', '8001');

        return new WebSocketServer($host, $port);
    }

    protected function handler(): AbstractHandler
    {
        return new WebSocketHandler($this->container, $this->name);
    }
}
