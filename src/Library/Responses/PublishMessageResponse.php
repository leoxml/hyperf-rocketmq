<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Library\Responses;

use Exception;
use Uncleqiu\RocketMQ\Library\Common\XMLParser;
use Uncleqiu\RocketMQ\Library\Constants;
use Uncleqiu\RocketMQ\Library\Exception\InvalidArgumentException;
use Uncleqiu\RocketMQ\Library\Exception\MalformedXMLException;
use Uncleqiu\RocketMQ\Library\Exception\MQException;
use Uncleqiu\RocketMQ\Library\Exception\TopicNotExistException;
use Uncleqiu\RocketMQ\Library\Model\Message;
use Uncleqiu\RocketMQ\Library\Model\TopicMessage;
use Throwable;
use XMLReader;

class PublishMessageResponse extends BaseResponse
{
    public function __construct()
    {
    }

    public function parseResponse($statusCode, $content): TopicMessage
    {
        $this->statusCode = $statusCode;
        if ($statusCode == 201) {
            $this->succeed = true;
        } else {
            $this->parseErrorResponse($statusCode, $content);
        }

        $xmlReader = $this->loadXmlContent($content);
        try {
            return $this->readMessageIdAndMD5XML($xmlReader);
        } catch (Exception $e) {
            throw new MQException($statusCode, $e->getMessage(), $e);
        } catch (Throwable $t) {
            throw new MQException($statusCode, $t->getMessage());
        }
    }

    public function readMessageIdAndMD5XML(XMLReader $xmlReader): TopicMessage
    {
        $message = Message::fromXML($xmlReader);
        $topicMessage = new TopicMessage(null);
        $topicMessage->setMessageId($message->getMessageId());
        $topicMessage->setMessageBodyMD5($message->getMessageBodyMD5());
        $topicMessage->setReceiptHandle($message->getReceiptHandle());

        return $topicMessage;
    }

    public function parseErrorResponse($statusCode, $content, MQException $exception = null)
    {
        $this->succeed = false;
        $xmlReader = $this->loadXmlContent($content);
        try {
            $result = XMLParser::parseNormalError($xmlReader);
            if ($result['Code'] == Constants::TOPIC_NOT_EXIST) {
                throw new TopicNotExistException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
            }
            if ($result['Code'] == Constants::INVALID_ARGUMENT) {
                throw new InvalidArgumentException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
            }
            if ($result['Code'] == Constants::MALFORMED_XML) {
                throw new MalformedXMLException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
            }
            throw new MQException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
        } catch (Exception $e) {
            if ($exception != null) {
                throw $exception;
            }
            if ($e instanceof MQException) {
                throw $e;
            }
            throw new MQException($statusCode, $e->getMessage());
        } catch (Throwable $t) {
            throw new MQException($statusCode, $t->getMessage());
        }
    }
}
