<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Library\Responses;

use Uncleqiu\RocketMQ\Library\Common\XMLParser;
use Uncleqiu\RocketMQ\Library\Constants;
use Uncleqiu\RocketMQ\Library\Exception\AckMessageException;
use Uncleqiu\RocketMQ\Library\Exception\InvalidArgumentException;
use Uncleqiu\RocketMQ\Library\Exception\MQException;
use Uncleqiu\RocketMQ\Library\Exception\ReceiptHandleErrorException;
use Uncleqiu\RocketMQ\Library\Exception\TopicNotExistException;
use Uncleqiu\RocketMQ\Library\Model\AckMessageErrorItem;
use Throwable;
use XMLReader;

class AckMessageResponse extends BaseResponse
{
    public function __construct()
    {
    }

    public function parseResponse($statusCode, $content)
    {
        $this->statusCode = $statusCode;
        if ($statusCode == 204) {
            $this->succeed = true;
        } else {
            $this->parseErrorResponse($statusCode, $content);
        }
    }

    public function parseErrorResponse($statusCode, $content, MQException $exception = null)
    {
        $this->succeed = false;
        $xmlReader = $this->loadXmlContent($content);

        try {
            while ($xmlReader->read()) {
                if ($xmlReader->nodeType == XMLReader::ELEMENT) {
                    switch ($xmlReader->name) {
                    case Constants::ERROR:
                        $this->parseNormalErrorResponse($xmlReader);
                        break;
                    default: // case Constants::Messages
                        $this->parseAckMessageErrorResponse($xmlReader);
                        break;
                    }
                }
            }
        } catch (Throwable $e) {
            if ($exception != null) {
                throw $exception;
            }
            if ($e instanceof MQException) {
                throw $e;
            }
            throw new MQException($statusCode, $e->getMessage());
        }
    }

    private function parseAckMessageErrorResponse($xmlReader)
    {
        $ex = new AckMessageException($this->statusCode, 'AckMessage Failed For Some ReceiptHandles');
        $ex->setRequestId($this->getRequestId());
        while ($xmlReader->read()) {
            if ($xmlReader->nodeType == XMLReader::ELEMENT && $xmlReader->name == Constants::ERROR) {
                $ex->addAckMessageErrorItem(AckMessageErrorItem::fromXML($xmlReader));
            }
        }
        throw $ex;
    }

    private function parseNormalErrorResponse($xmlReader)
    {
        $result = XMLParser::parseNormalError($xmlReader);

        if ($result['Code'] == Constants::INVALID_ARGUMENT) {
            throw new InvalidArgumentException($this->getStatusCode(), $result['Message'], null, $result['Code'], $result['RequestId'], $result['HostId']);
        }
        if ($result['Code'] == Constants::TOPIC_NOT_EXIST) {
            throw new TopicNotExistException($this->getStatusCode(), $result['Message'], null, $result['Code'], $result['RequestId'], $result['HostId']);
        }
        if ($result['Code'] == Constants::RECEIPT_HANDLE_ERROR) {
            throw new ReceiptHandleErrorException($this->getStatusCode(), $result['Message'], null, $result['Code'], $result['RequestId'], $result['HostId']);
        }

        throw new MQException($this->getStatusCode(), $result['Message'], null, $result['Code'], $result['RequestId'], $result['HostId']);
    }
}
