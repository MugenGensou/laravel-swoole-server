<?php

namespace Mugen\LaravelSwooleServer\Commands;

use Illuminate\Console\Command;
use Mugen\LaravelSwooleServer\Commands\Traits\CommandHelper;
use Mugen\LaravelSwooleServer\Commands\Traits\WatcherHelper;

class SwooleWatchCommand extends Command
{
    use CommandHelper,
        WatcherHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:watch';

    /**
     * @var string
     */
    protected $serverName = 'all';

    /**
     * @var array
     */
    protected $default;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->warn("Watching all server ...");

        $this->runAll('restart');

        $this->createWatcher(function () {
            $this->runAll('reload');
        })->watch();
    }

    protected function runAll(string $action)
    {
        $this->info(ucfirst($action) . " all server ...");

        exec("php artisan swoole:servers {$action} > /dev/null &");
    }

    /**
     * No use.
     * @param array $directories
     * @return array
     */
    protected function traverseDirectories(array $directories)
    {
        $resultDirectories = [];

        foreach ($directories as $directory) {
            if (is_dir($directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
                $names = array_filter(scandir($directory), function ($name) use ($directory) {
                    return in_array($name, ['.', '..']) ? false : is_dir($directory . $name);
                });

                $subDirectories = array_map(function ($name) use ($directory) {
                    return $directory . $name;
                }, $names);

                $resultDirectories[] = $directory;
                $resultDirectories   = array_merge($resultDirectories, $this->traverseDirectories($subDirectories));
            }
        }

        return $resultDirectories;
    }
}
