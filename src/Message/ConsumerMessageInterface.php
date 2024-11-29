<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Message;

use Leoxml\RocketMQ\Library\Model\Message as RocketMQMessage;

interface ConsumerMessageInterface extends MessageInterface
{
    public function consumeMessage(RocketMQMessage $rocketMQMessage): string;

    public function getGroupId(): string;

    public function setGroupId(string $groupId);

    public function getNumOfMessage(): int;

    public function setNumOfMessage(int $num);

    public function getWaitSeconds(): int;

    public function setWaitSeconds(int $seconds);

    public function getProcessNums(): int;

    public function setProcessNums(int $num);

    public function isEnable(): bool;

    public function setEnable(bool $enable);

    public function getMaxConsumption(): int;

    public function setMaxConsumption(int $num);

    public function getOpenCoroutine(): bool;

    public function setOpenCoroutine(bool $isOpen);

    public function getSaveConsumeLog(): bool;

    public function setSaveConsumeLog(bool $isSaveLog);

    /**
     * 处理消费成功（针对当前消息者）.
     */
    public function handleConsumeSuccess(RocketMQMessage $message): void;

    /**
     * 处理错误信息（针对当前消息者）.
     */
    public function handleError(\Throwable $throwable, RocketMQMessage $message): void;
}
