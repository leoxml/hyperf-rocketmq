<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ;


use Hyperf\Guzzle\PoolHandler;
use Hyperf\Utils\Coroutine\Locker;
use Leoxml\RocketMQ\Library\MQClient;

class ClientFactory
{
    /**
     * @var MQClient[][]
     */
    protected array $clientPool = [];

    public function getClient(Config $config): MQClient
    {
        if (!isset($this->clientPool[$config->getPoolName()])) {
            if (Locker::lock(static::class . 'getClient')) {
                try {
                    !isset($this->clientPool[$config->getPoolName()]) && $this->clientPool[$config->getPoolName()] = new MQClient(
                        $config->getHost(),
                        $config->getAccessKey(),
                        $config->getSecretKey(),
                        null,
                        $this->getMQConfig($config)
                    );
                } finally {
                    Locker::unlock(static::class . 'getClient');
                }
            }
            $this->clientPool[$config->getPoolName()] = new MQClient(
                $config->getHost(),
                $config->getAccessKey(),
                $config->getSecretKey(),
                null,
                $this->getMQConfig($config)
            );
        }
        return $this->clientPool[$config->getPoolName()];
    }

    /**
     * 配置文件转换.
     */
    protected function getMQConfig(Config $config): Library\Config
    {
        $mqConfig = new \Leoxml\RocketMQ\Library\Config();
        $mqConfig->setConnectTimeout($config->getConnectTimeout());
        $mqConfig->setRequestTimeout($config->getWaitTimeout());

        $mqConfig->setHandler(make(PoolHandler::class, [
            'option' => [
                'min_connections' => $config->getMinConnections(),
                'max_connections' => $config->getMaxConnection(),
                'connect_timeout' => $config->getConnectTimeout(),
                'wait_timeout' => $config->getWaitTimeout(),
                'heartbeat' => $config->getHeartBeat(),
                'max_idle_time' => $config->getMaxIdleTime(),
            ],
        ]));
        return $mqConfig;
    }
}