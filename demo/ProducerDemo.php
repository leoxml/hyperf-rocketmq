<?php

declare(strict_types=1);

use Hyperf\Context\ApplicationContext;
use Leoxml\RocketMQ\Producer;

$producer = new DemoProducer(['test' => '张三', 'age' => 30]);
ApplicationContext::getContainer()->get(Producer::class)->produce($producer);
