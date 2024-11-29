<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Event;

use Leoxml\RocketMQ\Library\Model\Message as RocketMQMessage;

class ConsumeEvent
{
    /**
     * @var RocketMQMessage
     */
    protected RocketMQMessage $message;

    public function __construct(RocketMQMessage $message)
    {
        $this->message = $message;
    }

    public function getMessage(): RocketMQMessage
    {
        return $this->message;
    }
}
