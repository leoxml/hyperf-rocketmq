<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Library\Exception;

use Uncleqiu\RocketMQ\Library\Model\MessagePartialResult;

class MessageResolveException extends MQException
{
    private MessagePartialResult $partialResult;

    public function __construct(
        $code,
        $message,
        MessagePartialResult $result,
        $previousException = null,
        $onsErrorCode = null,
        $requestId = null,
        $hostId = null
    ){
        parent::__construct($code, $message, $previousException, $onsErrorCode, $requestId, $hostId);
        $this->partialResult = $result;
    }

    /**
     * @return MessagePartialResult
     */
    public function getPartialResult(): MessagePartialResult
    {
        return $this->partialResult;
    }
}