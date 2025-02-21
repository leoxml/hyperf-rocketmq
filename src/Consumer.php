<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ;

use Hyperf\Process\ProcessManager;
use Leoxml\RocketMQ\Event\{AfterConsume, FailToConsume, BeforeConsume};
use Leoxml\RocketMQ\Library\Exception\{AckMessageException, MessageNotExistException, MessageResolveException};
use Leoxml\RocketMQ\Library\Model\Message as RocketMQMessage;
use Leoxml\RocketMQ\Library\MQClient;
use Leoxml\RocketMQ\Message\ConsumerMessageInterface;
use Throwable;

class Consumer extends Builder
{
    /**
     * @throws Throwable
     */
    public function consume(ConsumerMessageInterface $consumerMessage): void
    {
        $poolName = $consumerMessage->getPoolName();
        $config = new Config($poolName);
        $consumer = $this->getClient($config)->getConsumer(
            $config->getInstanceId(),
            $consumerMessage->getTopic(),
            $consumerMessage->getGroupId(),
            $consumerMessage->getMessageTag()
        );

        $this->setLogger($consumerMessage->getLogGroup());

        $maxConsumption = $consumerMessage->getMaxConsumption();
        $currentConsumption = 0;

        while (ProcessManager::isRunning()) {
            try {
                // 长轮询消费消息
                // 长轮询表示如果topic没有消息则请求会在服务端挂住3s，3s内如果有消息可以消费则立即返回
                $messages = $consumer->consumeMessage(
                    $consumerMessage->getNumOfMessage(), // 一次最多消费3条(最多可设置为16条)
                    $consumerMessage->getWaitSeconds() // 长轮询时间（最多可设置为30秒）
                );
            } catch (MessageResolveException $e) {
                // 当出现消息Body存在不合法字符，无法解析的时候，会抛出此异常。
                // 可以正常解析的消息列表。
                $messages = $e->getPartialResult()->getMessages();
                // 无法正常解析的消息列表。
                $failMessages = $e->getPartialResult()->getFailResolveMessages();

                if ($failMessages) {
                    $receiptHandles = [];
                    foreach ($failMessages as $failMessage) {
                        // 处理存在不合法字符，无法解析的消息。
                        $receiptHandles[] = $failMessage->getReceiptHandle();
                        //printf("Fail To Resolve Message. MsgID %s\n", $failMessage->getMessageId());
                        $this->logger->error('ack_error:fail_message', ['MsgID' => $failMessage->getMessageId()]);
                    }
                    $consumer->ackMessage($receiptHandles);
                }
            } catch (MessageNotExistException $e) {
                continue;
            } catch (Throwable $exception) {
                $this->logger->error((string)$exception);
                throw $exception;
            }

            $receiptHandles = [];
            // 如果只有一条消息，直接消费
            if ($consumerMessage->getOpenCoroutine() && count($messages) > 1) { // 协程并发消费
                $callback = [];
                foreach ($messages as $key => $message) {
                    $callback[$key] = $this->getCallBack($consumerMessage, $message);
                }
                $receiptHandles = parallel($callback);
            } else { // 同步执行
                foreach ($messages as $message) {
                    $receiptHandles[] = call($this->getCallBack($consumerMessage, $message));
                }
            }

            try {
                $receiptHandles = array_filter($receiptHandles);
                $receiptHandles && $consumer->ackMessage($receiptHandles);
                if ($maxConsumption > 0 && ++$currentConsumption >= $maxConsumption) {
                    break;
                }
            } catch (AckMessageException $exception) {
                // 某些消息的句柄可能超时了会导致确认不成功
                $this->logger->error('ack_error', ['RequestId' => $exception->getRequestId()]);
                foreach ($exception->getAckMessageErrorItems() as $errorItem) {
                    $this->logger->error('ack_error:receipt_handle', [
                        $errorItem->getReceiptHandle(), $errorItem->getErrorCode(), $errorItem->getErrorCode(),
                    ]);
                }
            } catch (Throwable $e) {
                $this->logger->error((string)$e);
                break;
            }
        }
    }

    protected function getCallBack(ConsumerMessageInterface $consumerMessage, RocketMQMessage $message): \Closure
    {
        return function () use ($consumerMessage, $message) {
            try {
                $this->eventDispatcher && $this->eventDispatcher->dispatch(new BeforeConsume($message));
                $result = $consumerMessage->consumeMessage($message);
                $this->eventDispatcher && $this->eventDispatcher->dispatch(new AfterConsume($message));

                $consumerMessage->handleConsumeSuccess($message);
                return $result == Result::ACK ? $message->getReceiptHandle() : null;
            } catch (\Throwable $throwable) {
                $this->eventDispatcher && $this->eventDispatcher->dispatch(new FailToConsume($message, $throwable));
                $consumerMessage->handleError($throwable, $message);
//                $result = Result::DROP;
            }
            return null;
        };
    }

    protected function getClient(Config $config): MQClient
    {
        return new MQClient(
            $config->getHost(),
            $config->getAccessKey(),
            $config->getSecretKey(),
            null,
            $this->getMQConfig($config)
        );
    }

    // 思考：消费端有必要用连接池？
    protected function getMQConfig(Config $config): Library\Config
    {
        $mqConfig = new \Leoxml\RocketMQ\Library\Config();
        $mqConfig->setConnectTimeout($config->getConnectTimeout());
        $mqConfig->setRequestTimeout($config->getWaitTimeout());

        return $mqConfig;
    }
}