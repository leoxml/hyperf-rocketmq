<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ;

use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;
use Uncleqiu\RocketMQ\Annotation\Consumer as ConsumerAnnotation;
use Uncleqiu\RocketMQ\Message\ConsumerMessageInterface;
use Psr\Container\ContainerInterface;

class ConsumerManager
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function run()
    {
        $classes = AnnotationCollector::getClassesByAnnotation(ConsumerAnnotation::class);
        /**
         * @var string $class
         * @var ConsumerAnnotation $annotation
         */
        foreach ($classes as $class => $annotation) {
            $instance = make($class);
            if (!$instance instanceof ConsumerMessageInterface || $annotation->enable === false) {
                continue;
            }

            $instance->setIsAddEnvExt($annotation->addEnvExt); // 这里需要写在最前面
            $annotation->poolName && $instance->setPoolName($annotation->poolName); // 这个需要首先设置（topic_ext 需要使用）
            $annotation->topic && $instance->setTopic($annotation->topic);
            $annotation->groupId && $instance->setGroupId($annotation->groupId);
            $annotation->messageTag && $instance->setMessageTag($annotation->messageTag);
            $annotation->numOfMessage && $instance->setNumOfMessage($annotation->numOfMessage);
            $annotation->waitSeconds && $instance->setWaitSeconds($annotation->waitSeconds);
            $annotation->logType && $instance->setLogType($annotation->logType);
//            $annotation->enable && $instance->setEnable($instance->isEnable());
            $annotation->maxConsumption && $instance->setMaxConsumption($annotation->maxConsumption);
            $annotation->openCoroutine && $instance->setOpenCoroutine($annotation->openCoroutine);
            property_exists($instance, 'container') && $instance->container = $this->container;

            $nums = $annotation->processNums;
            $process = $this->createProcess($instance);
            $process->nums = (int)$nums;
            $process->name = $annotation->name . '-' . $instance->getMessageTag();
            ProcessManager::register($process);
        }
    }

    private function createProcess(ConsumerMessageInterface $consumerMessage): AbstractProcess
    {
        return new class($this->container, $consumerMessage) extends AbstractProcess {
            /**
             * @var Consumer
             */
            private $consumer;

            /**
             * @var ConsumerMessageInterface
             */
            private $consumerMessage;

            public function __construct(ContainerInterface $container, ConsumerMessageInterface $consumerMessage)
            {
                parent::__construct($container);
                $this->consumer = $container->get(Consumer::class);
                $this->consumerMessage = $consumerMessage;
            }

            public function handle(): void
            {
                $this->consumer->consume($this->consumerMessage);
            }

            public function getConsumerMessage(): ConsumerMessageInterface
            {
                return $this->consumerMessage;
            }

            public function isEnable($server): bool
            {
                return $this->consumerMessage->isEnable();
            }
        };
    }
}
