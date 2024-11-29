<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Message;

use Hyperf\Database\Model\Model;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Codec\Json;
use Leoxml\RocketMQ\Constants\MqConstant;
use Leoxml\RocketMQ\Exception\RocketMQException;
use Leoxml\RocketMQ\Model\MqProduceStatusLog;
use Leoxml\RocketMQ\Packer\Packer;

class ProducerMessage extends Message implements ProducerMessageInterface
{
    protected string $messageKey = '';

    protected string $messageTag = '';

    protected string|array $payload = '';

    /**
     * 是否保存了消息投递状态日志（调用 saveMessageStatus）
     * @var bool
     */
    protected bool $hasSaveStatusLog = false;

    /**
     * 是否记录生产日志.
     */
    protected bool $saveProduceLog = true;

    /**
     * 投递时间（10位时间戳）.
     */
    protected ?int $deliverTime = null;

    public function getMessageKey(): string
    {
        if (!$this->messageKey) {
            $this->setMessageKey(session_create_id('rocketmq'));
        }
        return $this->messageKey;
    }

    public function setMessageKey(string $messageKey): self
    {
        $this->messageKey = $messageKey;
        return $this;
    }

    public function setPayload($data): self
    {
        $this->payload = $data;
        return $this;
    }

    public function payload(): string
    {
        return $this->serialize();
    }

    public function getSaveProduceLog(): bool
    {
        return $this->saveProduceLog;
    }

    public function setSaveProduceLog(bool $isSaveLog): self
    {
        $this->saveProduceLog = $isSaveLog;
        return $this;
    }

    /**
     * 保存消息状态
     */
    public function saveMessageStatus(): void
    {
        if (!$this->payload()) {
            throw new RocketMQException('请设置payload');
        }
        if ($this->hasSaveStatusLog) {
            return;
        }
        $this->saveMsgStatus(MqConstant::PRODUCE_STATUS_WAIT);
        $this->hasSaveStatusLog = true;
    }

    /**
     * 更新消息状态
     * @param int $status
     */
    public function updateMessageStatus(int $status): void
    {
        // 只有记录了状态信息（调用 saveMessageStatus 方法），才更新
        $this->hasSaveStatusLog && $this->getStatusLogModel()
            ->where('message_key', $this->getMessageKey())
            ->update(['status' => $status]);
    }

    /**
     * 记录消息状态信息
     */
    protected function saveMsgStatus(int $status): void
    {
        $this->getStatusLogModel()->insert([
            'status' =>$status,
            'message_key' => $this->getMessageKey(),
            'mq_info' => $this->getProduceInfo(),
        ]);
    }

    /**
     * 处理消息投递成功的事件
     */
    public function handleSuccessProduce()
    {
        // 1. 如果记录生产状态日志，消费成功删除日志
        $this->updateMessageStatus(MqConstant::PRODUCE_STATUS_SENT);
        // 2. 记录日志
        if ($this->saveProduceLog) {
            switch ($this->getLogType()) {
                case MqConstant::LOG_TYPE_FILE:
                    $this->getLogger()->info('[消息投递成功]'. $this->getProduceInfo());
                    break;
                case MqConstant::LOG_TYPE_DB:
                    // 只有记录了消息投递状态，就不操作。因为步骤1已经更新了
                    $this->hasSaveStatusLog === false && $this->saveMsgStatus(MqConstant::PRODUCE_STATUS_SENT);
                    break;
            }
        }
    }

    /**
     * 获取生成的消息信息.
     * @param bool $toJson
     * @return array|string
     */
    public function getProduceInfo(bool $toJson = true): array|string
    {
        $data = [
            'pool' => $this->getPoolName(),
            'topic' => $this->getTopic(),
            'message_key' => $this->getMessageKey(),
            'message_tag' => $this->getMessageTag(),
            'payload' => $this->payload(),
        ];
        return $toJson ? Json::encode($data) : $data;
    }

    /**
     * Serialize the message body to a string.
     */
    public function serialize(): string
    {
        $packer = ApplicationContext::getContainer()->get(Packer::class);
        return $packer->pack($this->payload);
    }

    public function getDeliverTime(): ?int
    {
        return $this->deliverTime ? $this->deliverTime * 1000 : null;
    }

    public function setDeliverTime(int $timestamp): self
    {
        $this->deliverTime = $timestamp;
        return $this;
    }

    protected function getStatusLogModel(): Model
    {
        return (new MqProduceStatusLog())->setConnection($this->getDbConnection());
    }
}
