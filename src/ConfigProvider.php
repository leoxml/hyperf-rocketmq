<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ;

use Hyperf\Codec\Packer\JsonPacker;
use Leoxml\RocketMQ\Listener\BeforeMainServerStartListener;
use Leoxml\RocketMQ\Packer\Packer;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                Producer::class => Producer::class,
                Packer::class => JsonPacker::class,
                Consumer::class => ConsumerFactory::class,
                ClientFactory::class => ClientFactory::class,
            ],
            'listeners' => [
                BeforeMainServerStartListener::class => 99,
            ],
            'commands' => [],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for rocketmq.',
                    'source' => __DIR__ . '/../publish/rocketmq.php',
                    'destination' => BASE_PATH . '/config/autoload/rocketmq.php',
                ],
                [
                    'id' => 'mq_status_log_migration',
                    'description' => 'The mq_produce_status_log migration for rocketmq.',
                    'source' => __DIR__ . '/../publish/migrations/2022_05_24_172602_create_rocketmq_produce_status_log_table.php',
                    'destination' => BASE_PATH . '/migrations/rocketmq/2022_05_24_172602_create_rocketmq_produce_status_log_table.php',
                ],
                [
                    'id' => 'rocketmq_status_log_migration',
                    'description' => 'The rocketmq_consume_log migration for rocketmq.',
                    'source' => __DIR__ . '/../publish/migrations/2022_05_24_172643_create_rocketmq_consume_log_table.php',
                    'destination' => BASE_PATH . '/migrations/rocketmq/2022_05_24_172643_create_rocketmq_consume_log_table.php',
                ],
                [
                    'id' => 'rocketmq_error_log_migrations',
                    'description' => 'The rocketmq_error_log migration for rocketmq.',
                    'source' => __DIR__ . '/../publish/migrations/2022_05_22_141058_create_rocketmq_error_log_table.php',
                    'destination' => BASE_PATH . '/migrations/rocketmq/2022_05_22_141058_create_rocketmq_error_log_table.php',
                ],
            ],
        ];
    }
}
