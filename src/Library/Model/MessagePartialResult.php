<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Library\Model;

class MessagePartialResult
{

    private array $messages;

    private array $failResolveMessages;

    /**
     * @param array $messages
     * @param array $failResolveMessages
     */
    public function __construct(array $messages, array $failResolveMessages)
    {
        $this->messages = $messages;
        $this->failResolveMessages = $failResolveMessages;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @return array
     */
    public function getFailResolveMessages(): array
    {
        return $this->failResolveMessages;
    }
}