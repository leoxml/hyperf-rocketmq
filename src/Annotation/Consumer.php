<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Uncleqiu\RocketMQ\Constants\MqConstant;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Consumer extends AbstractAnnotation
{

    public function __construct(
        public string $name = 'Consumer',
        public string $poolName = 'default', // 驱动
        public string $topic = '',
        public string $groupId = '',
        public string $messageTag = '', // filter tag for consumer. If not empty, only consume the message which's messageTag is equal to it.
        public int    $numOfMessage = 1, // 一次消耗多少条消息
        public int    $waitSeconds = 3,
        public int    $processNums = 1, // 进程数量
        public bool   $enable = true, // 是否初始化时启动
        public int    $maxConsumption = 0, // 程最大消费数
        public bool   $openCoroutine = false, // 是否开启协程并发消费
        public bool   $addEnvExt = false, // 是否添加 env 后缀（对 topic、groupId、messageTag 起效）, 主要为了区分多个环境共用一个实例
        public int    $logType = MqConstant::LOG_TYPE_FILE // 日志类型
    )
    {
    }
}
