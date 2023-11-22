<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Library\Model;

class FailResolveMessage
{

    private string $messageId;

    private string $receiptHandle;

    private string $orgResponseData;

    /**
     * @param $messageId
     * @param $receiptHandle
     * @param $orgResponseData
     */
    public function __construct($messageId, $receiptHandle, $orgResponseData)
    {
        $this->messageId = $messageId;
        $this->receiptHandle = $receiptHandle;
        $this->orgResponseData = $orgResponseData;
    }

    /**
     * @return string
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * @return string
     */
    public function getReceiptHandle(): string
    {
        return $this->receiptHandle;
    }

    /**
     * @return string
     */
    public function getOrgResponseData(): string
    {
        return $this->orgResponseData;
    }
}