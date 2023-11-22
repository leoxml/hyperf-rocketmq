<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Library;

use Uncleqiu\RocketMQ\Library\Exception\AckMessageException;
use Uncleqiu\RocketMQ\Library\Exception\InvalidArgumentException;
use Uncleqiu\RocketMQ\Library\Exception\MessageNotExistException;
use Uncleqiu\RocketMQ\Library\Exception\MQException;
use Uncleqiu\RocketMQ\Library\Exception\ReceiptHandleErrorException;
use Uncleqiu\RocketMQ\Library\Exception\TopicNotExistException;
use Uncleqiu\RocketMQ\Library\Http\HttpClient;
use Uncleqiu\RocketMQ\Library\Model\Message;
use Uncleqiu\RocketMQ\Library\Requests\AckMessageRequest;
use Uncleqiu\RocketMQ\Library\Requests\ConsumeMessageRequest;
use Uncleqiu\RocketMQ\Library\Responses\AckMessageResponse;
use Uncleqiu\RocketMQ\Library\Responses\ConsumeMessageResponse;

class MQConsumer
{
    private $instanceId;

    private $topicName;

    private $consumer;

    private $messageTag;

    private $client;

    /**
     * MQConsumer constructor.
     * @param null $instanceId
     * @param $topicName
     * @param $consumer
     * @param null $messageTag
     */
    public function __construct(HttpClient $client, $instanceId, $topicName, $consumer, $messageTag = null)
    {
        if (empty($topicName)) {
            throw new InvalidArgumentException(400, 'TopicName is null');
        }
        if (empty($consumer)) {
            throw new InvalidArgumentException(400, 'TopicName is null');
        }

        $this->instanceId = $instanceId;
        $this->topicName = $topicName;
        $this->consumer = $consumer;
        $this->messageTag = $messageTag;
        $this->client = $client;
    }

    public function getInstanceId()
    {
        return $this->instanceId;
    }

    public function getTopicName()
    {
        return $this->topicName;
    }

    public function getConsumer()
    {
        return $this->consumer;
    }

    public function getMessageTag()
    {
        return $this->messageTag;
    }

    /**
     * consume message.
     *
     * @param $numOfMessages: consume how many messages once, 1~16
     * @param int $waitSeconds: if > 0, means the time(second) the request holden at server if there is no message to consume.
     *                          If <= 0, means the server will response back if there is no message to consume.
     *                          It's value should be 1~30
     *
     * @throws TopicNotExistException if queue does not exist
     * @throws MessageNotExistException if no message exists
     * @throws InvalidArgumentException if the argument is invalid
     * @throws MQException if any other exception happends
     * @return array<Message>
     */
    public function consumeMessage($numOfMessages, int $waitSeconds = -1): array
    {
        if ($numOfMessages < 0 || $numOfMessages > 16) {
            throw new InvalidArgumentException(400, 'numOfMessages should be 1~16');
        }
        if ($waitSeconds > 30) {
            throw new InvalidArgumentException(400, 'numOfMessages should less then 30');
        }
        $request = new ConsumeMessageRequest($this->instanceId, $this->topicName, $this->consumer, $numOfMessages, $this->messageTag, $waitSeconds);
        $response = new ConsumeMessageResponse();
        return $this->client->sendRequest($request, $response);
    }

    /**
     * consume message orderly.
     *
     * Next messages will be consumed if all of same shard are acked. Otherwise, same messages will be consumed again after NextConsumeTime.
     *
     * Attention: the topic should be order topic created at console, if not, mq could not keep the order feature.
     *
     * This interface is suitable for globally order and partitionally order messages, and could be used in multi-thread scenes.
     *
     * @param $numOfMessages: consume how many messages once, 1~16
     * @param int $waitSeconds: if > 0, means the time(second) the request holden at server if there is no message to consume.
     *                          If <= 0, means the server will response back if there is no message to consume.
     *                          It's value should be 1~30
     *
     * @throws TopicNotExistException if queue does not exist
     * @throws MessageNotExistException if no message exists
     * @throws InvalidArgumentException if the argument is invalid
     * @throws MQException if any other exception happends
     * @return Message may contains several shard's messages, the messages of one shard are ordered
     */
    public function consumeMessageOrderly($numOfMessages, int $waitSeconds = -1): Message
    {
        if ($numOfMessages < 0 || $numOfMessages > 16) {
            throw new InvalidArgumentException(400, 'numOfMessages should be 1~16');
        }
        if ($waitSeconds > 30) {
            throw new InvalidArgumentException(400, 'numOfMessages should less then 30');
        }
        $request = new ConsumeMessageRequest($this->instanceId, $this->topicName, $this->consumer, $numOfMessages, $this->messageTag, $waitSeconds);
        $request->setTrans(Constants::TRANSACTION_ORDER);
        $response = new ConsumeMessageResponse();
        return $this->client->sendRequest($request, $response);
    }

    /**
     * ack message.
     *
     * @param $receiptHandles:
     *            array of $receiptHandle, which is got from consumeMessage
     *
     * @throws TopicNotExistException if queue does not exist
     * @throws ReceiptHandleErrorException if the receiptHandle is invalid
     * @throws InvalidArgumentException if the argument is invalid
     * @throws AckMessageException if any message not deleted
     * @throws MQException if any other exception happends
     * @return AckMessageResponse
     */
    public function ackMessage($receiptHandles): ?AckMessageResponse
    {
        $request = new AckMessageRequest($this->instanceId, $this->topicName, $this->consumer, $receiptHandles);
        $response = new AckMessageResponse();
        return $this->client->sendRequest($request, $response);
    }
}
