<?php

namespace Mugen\LaravelSwooleServer\Server;

use Illuminate\Contracts\Http\Kernel;
use Mugen\LaravelSwooleServer\Contracts\AbstractHandler;
use Mugen\LaravelSwooleServer\Contracts\AbstractManager;
use Mugen\LaravelSwooleServer\Handlers\HttpHandler;
use Mugen\LaravelSwooleServer\Http\Request;
use Mugen\LaravelSwooleServer\Http\Response;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server as HttpServer;
use Swoole\Server;

class HttpManager extends AbstractManager
{
    protected $kernel;

    /**
     * Creates swoole server.
     *
     * @return \Swoole\Server
     */
    protected function createSwooleServer(): Server
    {
        $host = $this->config('host', '127.0.0.1');
        $port = $this->config('port', '8000');

        return new HttpServer($host, $port);
    }

    protected function handler(): AbstractHandler
    {
        return new HttpHandler($this->container, $this->name);
    }

    protected function getKernel()
    {
        if (!$this->kernel)
            $this->kernel = $this->container->make(Kernel::class);

        return $this->kernel;
    }

    /**
     * "onRequest" listener.
     *
     * @param \Swoole\Http\Request  $swooleRequest
     * @param \Swoole\Http\Response $swooleResponse
     */
    public function onRequest(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse)
    {
        $kernel             = $this->getKernel();
        $illuminateRequest  = Request::make($swooleRequest)->toIlluminate();
        $illuminateResponse = $kernel->handle($illuminateRequest);
        $kernel->terminate($illuminateRequest, $illuminateResponse);

        Response::make($illuminateResponse, $swooleResponse)->send();

        // Unset request and response.
        $swooleRequest      = null;
        $swooleResponse     = null;
        $illuminateRequest  = null;
        $illuminateResponse = null;
    }
}
