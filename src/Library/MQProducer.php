<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Library;

use Uncleqiu\RocketMQ\Library\Exception\InvalidArgumentException;
use Uncleqiu\RocketMQ\Library\Http\HttpClient;
use Uncleqiu\RocketMQ\Library\Model\TopicMessage;
use Uncleqiu\RocketMQ\Library\Requests\PublishMessageRequest;
use Uncleqiu\RocketMQ\Library\Responses\PublishMessageResponse;

class MQProducer
{
    protected string $instanceId;

    protected string $topicName;

    protected HttpClient $client;

    public function __construct(HttpClient $client, $instanceId, $topicName)
    {
        if (empty($topicName)) {
            throw new InvalidArgumentException(400, 'TopicName is null');
        }
        $this->instanceId = $instanceId;
        $this->client = $client;
        $this->topicName = $topicName;
    }

    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    public function getTopicName(): string
    {
        return $this->topicName;
    }

    public function publishMessage(TopicMessage $topicMessage)
    {
        $request = new PublishMessageRequest(
            $this->instanceId,
            $this->topicName,
            $topicMessage->getMessageBody(),
            $topicMessage->getProperties(),
            $topicMessage->getMessageTag()
        );
        $response = new PublishMessageResponse();
        return $this->client->sendRequest($request, $response);
    }
}
