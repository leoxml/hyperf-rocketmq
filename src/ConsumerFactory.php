<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ;

use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;

class ConsumerFactory
{
    public function __invoke(ContainerInterface $container)
    {
//        return new Consumer($container, $container->get(LoggerFactory::class), $container->get(ClientFactory::class));
        return new Consumer($container, $container->get(LoggerFactory::class));
    }
}
