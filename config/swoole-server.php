<?php

return [

    /**
     * Default options.
     */
    'default' => [

        /**
         * Default swoole server.
         */
        'server'  => explode(',', env('SWOOLE_DEFAULT_SERVER', 'http')),

        /**
         * Common options.
         */
        'options' => [
            'log_dir' => storage_path('logs/'),
            'pid_dir' => storage_path('logs/'),
        ]
    ],

    /**
     * Swoole Servers.
     */
    'servers' => [

        'http' => [
            'type'    => 'http',
            'host'    => env('SWOOLE_HTTP_HOST', '127.0.0.1'),
            'port'    => env('SWOOLE_HTTP_PORT', '8000'),
            'options' => [
                'enable_static_handler' => true,
                'document_root'         => public_path(),
            ],
        ],

        'web_socket' => [
            'type' => 'web_socket',
            'host' => env('SWOOLE_WEB_SOCKET_HOST', '127.0.0.1'),
            'port' => env('SWOOLE_WEB_SOCKET_PORT', '8001'),
        ],

        'redis_task' => [
            'type'    => 'redis_task',
            'host'    => env('SWOOLE_REDIS_TASK_HOST', '127.0.0.1'),
            'port'    => env('SWOOLE_REDIS_TASK_PORT', '8002'),
            'options' => [
                'worker_num'      => env('SWOOLE_REDIS_TASK_WORKER_NUM', 1),
                'task_worker_num' => env('SWOOLE_REDIS_TASK_TASK_WORKER_NUM', 4)
            ],
        ],

    ],


    /**
     * |--------------------------------------------------------------------------
     * | File watcher configurations.
     * |--------------------------------------------------------------------------
     * |
     */
    'watcher' => [

        'directories' => [
            base_path(),
        ],

        'excluded_directories' => [
            base_path('.git/'),
            base_path('.idea/'),
            base_path('node_modules/'),
            base_path('storage/'),
            base_path('vendor/'),
        ],

        'suffixes' => [
            '.php',
        ],
    ],
];
