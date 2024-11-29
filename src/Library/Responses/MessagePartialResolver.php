<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Library\Responses;

use Leoxml\RocketMQ\Library\Model\FailResolveMessage;
use Leoxml\RocketMQ\Library\Model\Message;
use Leoxml\RocketMQ\Library\Model\MessagePartialResult;
use XMLReader;

class MessagePartialResolver
{

    public static function resolve($source): ?MessagePartialResult
    {
        $isMatched = preg_match_all('/<Message>[\s\S]*?<\/Message>/', $source, $matches);
        if (!$isMatched) {
            return null;
        }
        $messages = [];
        $failResolveMessages = [];
        foreach ($matches[0] as $match) {
            $message = null;
            try {
                $message = self::tryToResolveToMessage($match);
            } catch (\Exception $e) {
                $message = null;
            }
            if ($message === null) {
                $failResolveMessages[] = self::tryToConvertToFailResult($match);
            } else {
                $messages[] = $message;
            }
        }
        return new MessagePartialResult($messages, $failResolveMessages);
    }

    private static function tryToResolveToMessage($content): ?Message
    {
        $xmlReader = new XMLReader();
        $isXml = $xmlReader->XML($content);
        if ($isXml === false) {
            return null;
        }
        $message = Message::fromXML($xmlReader);
        if ($message === null || $message->getMessageId() === null) {
            return null;
        }
        return $message;
    }

    private static function tryToConvertToFailResult($content): ?FailResolveMessage
    {
        $newContent = preg_replace('/(<MessageBody>[\s\S]*<\/MessageBody>)|(<Properties>[\s\S]*<\/Properties>)/', '', $content);
        if ($newContent === null) {
            return null;
        }
        $xmlReader = new XMLReader();
        $isXml = $xmlReader->XML($newContent);
        if ($isXml === false) {
            return null;
        }
        $message = Message::fromXML($xmlReader);
        return new FailResolveMessage($message->getMessageId(), $message->getReceiptHandle(), $content);
    }
}