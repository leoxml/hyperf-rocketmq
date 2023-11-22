<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Library\Responses;

use Exception;
use Uncleqiu\RocketMQ\Library\Common\XMLParser;
use Uncleqiu\RocketMQ\Library\Constants;
use Uncleqiu\RocketMQ\Library\Exception\MessageNotExistException;
use Uncleqiu\RocketMQ\Library\Exception\MessageResolveException;
use Uncleqiu\RocketMQ\Library\Exception\MQException;
use Uncleqiu\RocketMQ\Library\Exception\TopicNotExistException;
use Uncleqiu\RocketMQ\Library\Model\Message;
use Throwable;
use XMLReader;

class ConsumeMessageResponse extends BaseResponse
{
    protected $messages;

    public function __construct()
    {
        $this->messages = [];
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function parseResponse($statusCode, $content): array
    {
        $this->statusCode = $statusCode;
        if ($statusCode == 200) {
            $this->succeed = true;
        } else {
            $this->parseErrorResponse($statusCode, $content);
        }

        try {
            $content = (string)$content;
            if ($this->loadAndValidateXmlContent($content, $xmlReader)) {
                while ($xmlReader->read()) {
                    if ($xmlReader->nodeType == XMLReader::ELEMENT
                        && $xmlReader->name == 'Message') {
                        $this->messages[] = Message::fromXML($xmlReader);
                    }
                }
                return $this->messages;
            }
            throw new MessageResolveException($statusCode, 'Some messages cannot be resolved', MessagePartialResolver::resolve($content));
        } catch (MessageResolveException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new MQException($statusCode, $e->getMessage(), $e);
        } catch (Throwable $t) {
            throw new MQException($statusCode, $t->getMessage(), $t);
        }
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
            if ($result['Code'] == Constants::MESSAGE_NOT_EXIST) {
                throw new MessageNotExistException($statusCode, $result['Message'], $exception, $result['Code'], $result['RequestId'], $result['HostId']);
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
