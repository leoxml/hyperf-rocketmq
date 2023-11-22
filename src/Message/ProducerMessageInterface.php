<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Message;

interface ProducerMessageInterface extends MessageInterface
{
    public function setPayload($data);

    public function payload(): string;

    public function getMessageKey(): string;

    public function setMessageKey(string $messageKey);

    public function getDeliverTime(): ?int;

    public function setDeliverTime(int $timestamp);

    public function getSaveProduceLog(): bool;

    public function setSaveProduceLog(bool $isSaveLog);

    public function getProduceInfo(bool $toJson = true): array|string;

    public function saveMessageStatus();

    public function updateMessageStatus(int $status);
}
