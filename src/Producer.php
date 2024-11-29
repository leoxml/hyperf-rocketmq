<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ;

use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Guzzle\PoolHandler;
use Leoxml\RocketMQ\Event\AfterProduce;
use Leoxml\RocketMQ\Library\Model\TopicMessage;
use Leoxml\RocketMQ\Library\MQClient;
use Leoxml\RocketMQ\Library\MQProducer;
use Leoxml\RocketMQ\Message\ProducerMessageInterface;

class Producer extends Builder
{
    protected array $clientPool = [];

    public function produce(ProducerMessageInterface $producerMessage): bool
    {
        $this->injectMessageProperty($producerMessage);

        $poolName = $producerMessage->getPoolName();
        $config = new Config($poolName);
        $result = $this->checkIsProduceSuccess($this->publishMessage($config, $producerMessage));

        if ($result) {
            $producerMessage->handleSuccessProduce(); // 处理生成成功
            $this->eventDispatcher && $this->eventDispatcher->dispatch(new AfterProduce($producerMessage));
        }

        return $result;
    }

    protected function getClient(Config $config): MQClient
    {
        if (!isset($this->clientPool[$config->getPoolName()])) {
            $this->clientPool[$config->getPoolName()] = new MQClient(
                $config->getHost(),
                $config->getAccessKey(),
                $config->getSecretKey(),
                null,
                $this->getMQConfig($config)
            );
        }
        return $this->clientPool[$config->getPoolName()];
    }

    /**
     * 配置文件转换.
     */
    protected function getMQConfig(Config $config): Library\Config
    {
        $mqConfig = new \Leoxml\RocketMQ\Library\Config();
        $mqConfig->setConnectTimeout($config->getConnectTimeout());
        $mqConfig->setRequestTimeout($config->getWaitTimeout());

        $mqConfig->setHandler(make(PoolHandler::class, [
            'option' => [
                'min_connections' => $config->getMinConnections(),
                'max_connections' => $config->getMaxConnection(),
                'connect_timeout' => $config->getConnectTimeout(),
                'wait_timeout' => $config->getWaitTimeout(),
                'heartbeat' => $config->getHeartBeat(),
                'max_idle_time' => $config->getMaxIdleTime(),
            ],
        ]));
        return $mqConfig;
    }

    protected function publishMessage(Config $config, ProducerMessageInterface $message): TopicMessage
    {
        $producer = $this->getProducer($config, $message);

        $publishMessage = new TopicMessage($message->payload());
        $message->getMessageTag() && $publishMessage->setMessageTag($message->getMessageTag());
        $message->getMessageKey() && $publishMessage->setMessageKey($message->getMessageKey());
        if ($timeInMillis = $message->getDeliverTime()) {
            $publishMessage->setStartDeliverTime($timeInMillis);
        }

        return $producer->publishMessage($publishMessage);
    }

    /**
     * 判断是否投递成功
     */
    private function checkIsProduceSuccess(TopicMessage $publishRet): bool
    {
        // 如果返回了 message id ，则视为投递成功（不考虑，MQ存储缓存丢失情况）
        return isset($publishRet->messageId) && !empty($publishRet->messageId);
    }

    private function getProducer(Config $config, ProducerMessageInterface $producerMessage): MQProducer
    {
        return $this->getClient($config)->getProducer($config->getInstanceId(), $producerMessage->getTopic());
    }

    private function injectMessageProperty(ProducerMessageInterface $producerMessage)
    {
        if (class_exists(AnnotationCollector::class)) {
            /** @var \Leoxml\RocketMQ\Annotation\Producer $annotation */
            $annotation = AnnotationCollector::getClassAnnotation(get_class($producerMessage), Annotation\Producer::class);
            if ($annotation) {
                $producerMessage->setIsAddEnvExt($annotation->addEnvExt); // 这里需要写在最前面
                $annotation->poolName && $producerMessage->setPoolName($annotation->poolName);
                $annotation->topic && $producerMessage->setTopic($annotation->topic);
                $annotation->messageTag && $producerMessage->setMessageTag($annotation->messageTag);
                $annotation->messageKey && $producerMessage->setMessageKey($annotation->messageKey);
            }
        }
    }
}
