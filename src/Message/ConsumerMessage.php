<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Message;

use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Codec\Json;
use Psr\Container\ContainerInterface;
use Leoxml\RocketMQ\Constants\MqConstant;
use Leoxml\RocketMQ\Library\Model\Message as RocketMQMessage;
use Leoxml\RocketMQ\Model\MqConsumeLog;
use Leoxml\RocketMQ\Model\MqErrorLog;
use Leoxml\RocketMQ\Packer\Packer;
use Leoxml\RocketMQ\Result;

class ConsumerMessage extends Message implements ConsumerMessageInterface
{

    public ContainerInterface $container;

    public string $groupId;

    /**
     * filter tag for consumer. If not empty, only consume the message which's messageTag is equal to it.
     */
    public string $messageTag;

    /**
     * consume how many messages once, 1~16.
     */
    public int $numOfMessage = 1;

    /**
     * if > 0, means the time(second) the request holden at server if there is no message to consume.
     * If <= 0, means the server will response back if there is no message to consume.
     * It's value should be 1~30.
     */
    public ?int $waitSeconds = 3;

    /**
     * 进程数量.
     */
    public int $processNums = 1;

    /**
     * 是否初始化时启动.
     */
    public bool $enable = true;

    /**
     * 进程最大消费数.
     */
    public int $maxConsumption = 0;

    /**
     * 是否开启协程并发消费.
     */
    public bool $openCoroutine = true;

    /**
     * 是否记录消费日志.
     */
    protected bool $saveConsumeLog = true;

    /**
     * 消费消息.
     * @return mixed
     */
    public function consumeMessage(RocketMQMessage $rocketMQMessage): string
    {
        $msgTag = $rocketMQMessage->getMessageTag(); // 消息标签
        $msgKey = $rocketMQMessage->getMessageKey(); // 消息唯一标识
        $msgBody = $this->unserialize($rocketMQMessage->getMessageBody()); // 消息体
        $msgId = $rocketMQMessage->getMessageId();

        // todo 消费处理
        return Result::ACK;
    }

    public function getGroupId(): string
    {
        return $this->groupId ?? '';
    }

    public function setGroupId(string $groupId): self
    {
        $this->groupId = $this->getEnvExt($groupId);
        return $this;
    }

    public function getNumOfMessage(): int
    {
        return $this->numOfMessage;
    }

    public function setNumOfMessage(int $num): self
    {
        $this->numOfMessage = $num;
        return $this;
    }

    public function getWaitSeconds(): int
    {
        return $this->waitSeconds;
    }

    public function setWaitSeconds(int $seconds): self
    {
        $this->waitSeconds = $seconds;
        return $this;
    }

    public function getProcessNums(): int
    {
        return $this->processNums;
    }

    public function setProcessNums(int $num): self
    {
        $this->processNums = $num;
        return $this;
    }

    public function isEnable(): bool
    {
        return $this->enable;
    }

    public function setEnable(bool $enable): self
    {
        $this->enable = $enable;
        return $this;
    }

    public function getMaxConsumption(): int
    {
        return $this->maxConsumption;
    }

    public function setMaxConsumption(int $num): self
    {
        $this->maxConsumption = $num;
        return $this;
    }

    public function getOpenCoroutine(): bool
    {
        return $this->openCoroutine;
    }

    public function getSaveConsumeLog(): bool
    {
        return $this->saveConsumeLog;
    }

    public function setSaveConsumeLog(bool $isSaveLog): self
    {
        $this->saveConsumeLog = $isSaveLog;
        return $this;
    }

    public function setOpenCoroutine(bool $isOpen): self
    {
        $this->openCoroutine = $isOpen;
        return $this;
    }

    public function unserialize(string $data)
    {
        $container = ApplicationContext::getContainer();
        $packer = $container->get(Packer::class);

        return $packer->unpack($data);
    }

    /**
     * 处理消费成功（针对当前消息者）.
     */
    public function handleConsumeSuccess(RocketMQMessage $message): void
    {
        if ($this->getSaveConsumeLog()) {
            switch ($this->getLogType()) {
                case MqConstant::LOG_TYPE_FILE:
                    //$this->getLogger()->info('[消息消费成功]', Json::encode($this->getMqInfo($message)));
                    $this->getLogger()->info('[消息消费成功]', $this->getMqInfo($message));
                    break;
                case MqConstant::LOG_TYPE_DB:
                    (new MqConsumeLog())->setConnection($this->getDbConnection())->insert($this->getMqInfo($message));
                    break;
            }
        }
    }

    /**
     * 处理错误信息（针对当前消息者）.
     */
    public function handleError(\Throwable $throwable, RocketMQMessage $message): void
    {
        if ($this->container->has(FormatterInterface::class)) {
            $formatter = $this->container->get(FormatterInterface::class);
            $errInfo = $formatter->format($throwable);
        } else {
            $errInfo = $throwable->getMessage();
        }

        switch ($this->getLogType()) {
            case MqConstant::LOG_TYPE_FILE:
                $this->getLogger()->error('[mq_info] ' . Json::encode($this->getMqInfo($message)) . ' [error_msg] ' . $errInfo);
                break;
            case MqConstant::LOG_TYPE_DB:
                (new MqErrorLog)->setConnection($this->getDbConnection())->insert([
                    'mq_info' => Json::encode($this->getMqInfo($message)),
                    'error_msg' => $errInfo,
                ]);
                break;
        }
    }

    /**
     * 获取队列信息.
     */
    protected function getMqInfo(RocketMQMessage $message): array
    {
        return [
            'topic' => $this->getTopic(),
            'message_key' => $message->getMessageKey(),
            'message_tag' => $message->getMessageTag(),
            'message_id' => $message->getMessageId(),
            'payload' => $message->getMessageBody(),
        ];
    }
}
