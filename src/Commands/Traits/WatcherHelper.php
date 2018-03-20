<?php

namespace Mugen\LaravelSwooleServer\Commands\Traits;

use HuangYi\Watcher\Watcher;

trait WatcherHelper
{
    protected $events = [
        'IN_CREATE'      => 256,
        'IN_DELETE'      => 512,
        'IN_DELETE_SELF' => 1024,
        'IN_MODIFY'      => 2,
        'IN_MOVE'        => 192,
    ];

    /**
     * Get .watched file.
     * @return string
     */
    protected function getWatchedFile()
    {
        return storage_path("logs/{$this->serverName}.watched");
    }

    /**
     * @return int
     */
    protected function getWatchedSecond()
    {
        return file_exists($file = $this->getWatchedFile()) ? (int)file_get_contents($file) : 0;
    }

    /**
     * Create .watched file.
     * @param $mircoSecond
     */
    protected function updateWatchedFile($mircoSecond)
    {
        file_put_contents($this->getWatchedFile(), $mircoSecond);
    }

    /**
     * Remove .watched file.
     */
    protected function removeWatchedFile()
    {
        file_exists($file = $this->getWatchedFile()) && unlink($file);
    }

    /**
     * Create Watcher.
     *
     * @param callable $callback
     * @return Watcher
     * @throws \Exception
     */
    protected function createWatcher(callable $callback)
    {

        $config = $this->config('watcher');

        $watcher = new Watcher($config['directories'], $config['excluded_directories'], $config['suffixes']);

        $watcher->setHandler(function (Watcher $watcher, array $event) use ($callback) {
            if ($eventName = array_search($event['mask'], $this->events, true)) {
                $this->updateWatchedFile($now = time());
                $this->info("{$eventName}: {$event['name']} at $now");
                do {
                    if (($watchedSecond = $this->getWatchedSecond()) < (time() - 1)) {
                        call_user_func($callback);
                        break;
                    }
                    sleep(1);
                } while ($watchedSecond === $now);
            }
        });

        return $watcher;
    }
}