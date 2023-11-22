<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Event;

use Uncleqiu\RocketMQ\Message\ProducerMessageInterface;

class ProduceEvent
{
    protected ProducerMessageInterface $message;

    public function __construct(ProducerMessageInterface $message)
    {
        $this->message = $message;
    }

    public function getMessage(): ProducerMessageInterface
    {
        return $this->message;
    }
}
