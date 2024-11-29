<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Event;

use Leoxml\RocketMQ\Library\Model\Message as RocketMQMessage;
use Throwable;

class FailToConsume extends ConsumeEvent
{
    protected Throwable $throwable;

    public function __construct(RocketMQMessage $message, Throwable $throwable)
    {
        parent::__construct($message);
        $this->throwable = $throwable;
    }

    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }
}
