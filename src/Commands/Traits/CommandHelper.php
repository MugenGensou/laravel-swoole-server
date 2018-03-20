<?php

namespace Mugen\LaravelSwooleServer\Commands\Traits;

trait CommandHelper
{
    /**
     * @param string $key
     * @param null   $default
     * @return mixed
     */
    protected function config(string $key, $default = null)
    {
        if (strpos($key, 'swoole-server') === false)
            $key = 'swoole-server.' . $key;

        return $this->laravel->make('config')->get($key, $default);
    }

    /**
     * Exit command.
     * @param      $string
     * @param null $verbosity
     */
    protected function exit($string, $verbosity = null)
    {
        $this->error($string, $verbosity);
        exit(1);
    }
}
