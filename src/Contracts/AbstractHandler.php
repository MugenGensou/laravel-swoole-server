<?php

namespace Mugen\LaravelSwooleServer\Contracts;

use Illuminate\Container\Container;

abstract class AbstractHandler
{
    /**
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $name;

    protected $events = [];

    public function __construct(Container $container, string $name)
    {
        $this->container = $container;
        $this->name      = $name;
    }

    final public function getEvents(): array
    {
        return $this->events;
    }
}
