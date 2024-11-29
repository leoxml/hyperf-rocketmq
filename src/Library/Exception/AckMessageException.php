<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Library\Exception;

use Leoxml\RocketMQ\Library\Constants;
use Leoxml\RocketMQ\Library\Model\AckMessageErrorItem;

/**
 * Ack message could fail for some receipt handles,
 *     and AckMessageException will be thrown.
 * All failed receiptHandles are saved in "$ackMessageErrorItems".
 */
class AckMessageException extends MQException
{
    protected $ackMessageErrorItems;

    public function __construct($code, $message, $previousException = null, $requestId = null, $hostId = null)
    {
        parent::__construct($code, $message, $previousException, Constants::ACK_FAIL, $requestId, $hostId);

        $this->ackMessageErrorItems = [];
    }

    public function addAckMessageErrorItem(AckMessageErrorItem $item)
    {
        $this->ackMessageErrorItems[] = $item;
    }

    public function getAckMessageErrorItems()
    {
        return $this->ackMessageErrorItems;
    }
}
