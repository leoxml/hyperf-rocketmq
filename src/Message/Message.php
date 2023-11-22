<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Message;

use Hyperf\Amqp\Exception\MessageException;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Uncleqiu\RocketMQ\Constants\MqConstant;

abstract class Message implements MessageInterface
{
    protected const MESSAGE_TAG_DELIMITER = '||';

    protected string $poolName = 'default';

    protected string $topic = '';

    protected string $dbConnection = 'default';

    /**
     * 是否添加 env 后缀（对 topic、groupId、messageTag 起效）
     * 主要为了区分多个环境共用一个实例
     * @var bool
     */
    protected bool $addEnvExt = false;

    /**
     * 错误日志类型.
     */
    protected int $logType = MqConstant::LOG_TYPE_FILE;

    /**
     * 日志分组.
     */
    protected string $logGroup = 'default';

    public function getPoolName(): string
    {
        return $this->poolName;
    }

    public function setPoolName(string $poolName): self
    {
        $this->poolName = $poolName;
        return $this;
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function setTopic(string $topic): self
    {
//        $this->topic = $this->getEnvExt($topic);
        $topicExt = config(sprintf('rocketmq.%s.topic_ext', $this->getPoolName()));
        $this->topic = $topicExt ? $topic . $topicExt : $topic;
        return $this;
    }

    public function getLogGroup(): string
    {
        return $this->logGroup;
    }

    public function setLogGroup(string $logGroup): self
    {
        $this->logGroup = $logGroup;
        return $this;
    }

    public function getDbConnection(): string
    {
        return $this->dbConnection;
    }

    public function setDbConnection(string $connection): self
    {
        $this->dbConnection = $connection;
        return $this;
    }

    public function getMessageTag(): ?string
    {
        return $this->messageTag ?? null;
    }

    public function setMessageTag(string $messageTag): self
    {
        $this->messageTag = implode(self::MESSAGE_TAG_DELIMITER, array_map(function ($item) {
            return $this->getEnvExt($item);
        }, explode(self::MESSAGE_TAG_DELIMITER, $messageTag)));
        return $this;
    }

    public function getLogType(): int
    {
        return $this->logType;
    }

    public function setLogType(int $type): self
    {
        $this->logType = $type;
        return $this;
    }

    public function setIsAddEnvExt(bool $isSet): self
    {
        $this->addEnvExt = $isSet;
        return $this;
    }

    public function isAddEnvExt(): bool
    {
        return $this->addEnvExt;
    }

    protected function getEnvExt(string $key): string
    {
        return $this->isAddEnvExt() ? $key . '_' . config('app_env') : $key;
    }

    protected function getLogger(): \Psr\Log\LoggerInterface
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get('rocketmq_log', $this->getLogGroup());
    }

    public function serialize(): string
    {
        throw new MessageException('You have to overwrite serialize() method.');
    }

    public function unserialize(string $data)
    {
        throw new MessageException('You have to overwrite unserialize() method.');
    }
}
