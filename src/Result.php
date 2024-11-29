<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ;


class Result
{
    /**
     * Acknowledge the message.
     */
    public const ACK = 'ack';

    /**
     * Unacknowledged the message.
     */
    public const NACK = 'nack';

    /**
     * Reject the message and drop it.
     */
    public const DROP = 'drop';
}