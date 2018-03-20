<?php

namespace Mugen\LaravelSwooleServer\Commands;

use Illuminate\Console\Command;
use Mugen\LaravelSwooleServer\Commands\Traits\CommandHelper;

class SwooleServersCommand extends Command
{
    use CommandHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:servers {action : start|stop|reload|restart}';

    /**
     * @var string
     */
    protected $action;

    /**
     * @var array
     */
    protected $default;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->initAction();
        $this->initDefaultServers();
        $this->runAll();
    }

    /**
     * Initialize command action.
     */
    protected function initAction()
    {
        $this->action = $this->argument('action');

        if (!in_array($this->action, ['start', 'stop', 'reload', 'restart']))
            $this->exit("Unexpected action argument {$this->action} .");
    }

    protected function initDefaultServers()
    {
        $this->default = $this->config('default.server');
    }

    protected function runAll()
    {
        $this->warn(ucfirst($this->action) . " all server ...");

        foreach ($this->default as $serverName) {
            $this->info(ucfirst($this->action) . " {$serverName} server ...");
            exec("php artisan swoole:server {$this->action} {$serverName} -d > /dev/null &");
        }
    }
}
