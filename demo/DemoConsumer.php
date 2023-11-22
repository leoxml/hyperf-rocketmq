<?php

declare(strict_types=1);

use Uncleqiu\RocketMQ\Annotation\Consumer;
use Uncleqiu\RocketMQ\Library\Model\Message as RocketMQMessage;
use Uncleqiu\RocketMQ\Message\ConsumerMessage;
use Uncleqiu\RocketMQ\Result;

#[Consumer(topic: 'Topic_03_test', groupId: 'test_test', messageTag: 'hyperf_test', name: 'DemoConsumer', processNums: 2)]
class DemoConsumer extends ConsumerMessage
{
    public function consumeMessage(RocketMQMessage $rocketMQMessage): string
    {
        $msgTag = $rocketMQMessage->getMessageTag(); // 消息标签
        $msgKey = $rocketMQMessage->getMessageKey(); // 消息唯一标识
        $msgBody = $this->unserialize($rocketMQMessage->getMessageBody()); // 消息体
        $msgId = $rocketMQMessage->getMessageId();

        // todo 消息消费逻辑...
        var_dump('消息成功' . $rocketMQMessage->getMessageBody());
        return Result::ACK;
    }
}
