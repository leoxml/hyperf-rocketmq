<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ;

use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\Coroutine;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class Builder
{

    protected ?EventDispatcherInterface $eventDispatcher = null;

    protected LoggerInterface $logger;

    public function __construct(protected ContainerInterface $container, protected LoggerFactory $loggerFactory/*, protected ClientFactory $factory*/)
    {
        if ($container->has(EventDispatcherInterface::class)) {
            $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
        }
    }

    protected function setLogger(string $groupName): void
    {
        $this->logger = $this->loggerFactory->get('rocketmq_log', $groupName ?: 'default');
    }

    protected function isCoroutine(): bool
    {
        return Coroutine::id() > 0;
    }
}
