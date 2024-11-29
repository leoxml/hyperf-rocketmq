<?php

declare(strict_types=1);
use function Hyperf\Support\env;

return [
    'default' => [
        'host'        => env('ROCKETMQ_HTTP_ENDPOINT'),
        'access_key'  => env('ROCKETMQ_ACCESS_KEY'),
        'secret_key'  => env('ROCKETMQ_SECRET_KEY'),
        'instance_id' => env('ROCKETMQ_INSTANCE_ID'),
        'topic_ext'   => env('ROCKETMQ_TOPIC_EXT'), // topic 后缀
        'pool'        => [ // 只对 producer 有效
            'min_connections' => 10,
            'max_connections' => 50,
            'connect_timeout' => 3.0,
            'wait_timeout'    => 30.0,
            'heartbeat'       => -1,
            'max_idle_time'   => 60.0,
        ],
    ],
];
