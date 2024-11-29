<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Message;

interface MessageInterface
{
    public function getPoolName(): string;

    public function setPoolName(string $poolName);

    public function getTopic(): string;

    public function setTopic(string $topic);

    public function getLogGroup(): string;

    public function setLogGroup(string $logGroup);

    public function getDbConnection(): string;

    public function setDbConnection(string $connection);

    public function setIsAddEnvExt(bool $isSet);

    public function isAddEnvExt(): bool;

    public function getMessageTag(): ?string;

    public function setMessageTag(string $messageTag);

    public function getLogType(): int;

    public function setLogType(int $type);

    /**
     * Serialize the message body to a string.
     */
    public function serialize(): string;

    /**
     * Unserialize the message body.
     */
    public function unserialize(string $data);
}
