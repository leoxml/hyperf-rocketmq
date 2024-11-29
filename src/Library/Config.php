<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Library;

use GuzzleHttp\HandlerStack;
use Hyperf\Guzzle\CoroutineHandler;
use Hyperf\Guzzle\PoolHandler;

class Config
{
    private $proxy;  // http://username:password@192.168.16.1:10

    private $connectTimeout;

    private $requestTimeout;

    private $expectContinue;

    private $handler;

    public function __construct()
    {
        $this->proxy = null;
        $this->requestTimeout = 30; // 30 seconds
        $this->connectTimeout = 3;  // 3 seconds
        $this->expectContinue = false;
        $this->handler = HandlerStack::create(new CoroutineHandler());
    }

    public function getProxy()
    {
        return $this->proxy;
    }

    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }

    public function getRequestTimeout(): float
    {
        return $this->requestTimeout;
    }

    public function setRequestTimeout($requestTimeout)
    {
        $this->requestTimeout = $requestTimeout;
    }

    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;
    }

    public function getConnectTimeout(): float
    {
        return $this->connectTimeout;
    }

    public function getExpectContinue(): bool
    {
        return $this->expectContinue;
    }

    public function setExpectContinue($expectContinue)
    {
        $this->expectContinue = $expectContinue;
    }

    /**
     * @return null|HandlerStack|PoolHandler
     */
    public function getHandler()
    {
        if ($this->handler instanceof PoolHandler) {
            return HandlerStack::create($this->handler);
        }
        return $this->handler;
    }

    /**
     * @param HandlerStack|PoolHandler $handler
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
    }
}
