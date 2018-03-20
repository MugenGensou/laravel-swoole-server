<?php

namespace Mugen\LaravelSwooleServer\Handlers;

use Mugen\LaravelSwooleServer\Contracts\AbstractHandler;

class HttpHandler extends AbstractHandler
{
    protected $events = [
        'request',
    ];
}
