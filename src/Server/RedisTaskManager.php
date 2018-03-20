<?php

namespace Mugen\LaravelSwooleServer\Server;

use Mugen\LaravelSwooleServer\Contracts\AbstractHandler;
use Mugen\LaravelSwooleServer\Contracts\AbstractManager;
use Mugen\LaravelSwooleServer\Handlers\EmptyHandler;
use Swoole\Redis\Server as RedisServer;
use Swoole\Server;

class RedisTaskManager extends AbstractManager
{
    /**
     * Creates swoole server.
     *
     * @return Server
     */
    protected function createSwooleServer(): Server
    {
        $host = $this->config('host', '127.0.0.1');
        $port = $this->config('port', '8002');

        $redisServer = new RedisServer($host, $port);

        $redisServer->setHandler('LPUSH', function ($fd, $data) use ($redisServer) {
            $data = array_map(function (string $item) {
                return $this->isSerialized($item) ? unserialize($item) : $item;
            }, $data);

            $taskId = $redisServer->task($data);

            $redisServer->send($fd, $taskId === false
                ? RedisServer::format(RedisServer::ERROR)
                : RedisServer::format(RedisServer::INT, $taskId)
            );
        });

        return $redisServer;
    }

    /**
     * Return event handler.
     *
     * @return AbstractHandler
     */
    protected function handler(): AbstractHandler
    {
        return new EmptyHandler($this->container, $this->name);
    }

    private function isSerialized(string $data)
    {
        $data = trim($data);
        if ('N;' == $data)
            return true;
        if (!preg_match('/^([adObis]):/', $data, $badions))
            return false;
        switch ($badions[1]) {
            case 'a' :
            case 'O' :
            case 's' :
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data))
                    return true;
                break;
        }
        return false;
    }
}
