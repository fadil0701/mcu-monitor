<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration for Production
    |--------------------------------------------------------------------------
    |
    | This configuration optimizes caching for production use with Redis
    | for the MCU monitoring system.
    |
    */

    'default' => env('CACHE_DRIVER', 'redis'),

    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'lock_connection' => null,
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'mcu_'),

    /*
    |--------------------------------------------------------------------------
    | Cache Tags
    |--------------------------------------------------------------------------
    |
    | Define cache tags for better cache management and invalidation
    |
    */
    'tags' => [
        'dashboard' => ['dashboard_stats', 'skpd_stats', 'mcu_chart_data'],
        'participants' => ['participants_list', 'participant_stats'],
        'schedules' => ['today_queue', 'schedule_stats'],
        'mcu_results' => ['health_status_chart', 'mcu_results_stats'],
        'system' => ['system_health', 'database_metrics'],
    ],
];

