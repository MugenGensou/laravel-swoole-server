<?php

namespace Mugen\LaravelSwooleServer\Handlers;

use Mugen\LaravelSwooleServer\Contracts\AbstractHandler;

class WebSocketHandler extends AbstractHandler
{
    protected $events = [
        'open',
        'message',
    ];
}
