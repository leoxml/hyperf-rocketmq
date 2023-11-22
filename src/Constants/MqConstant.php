<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Constants;

class MqConstant
{
    // mq生产状态
    public const PRODUCE_STATUS_WAIT = 1; // 待发送

    public const PRODUCE_STATUS_SENDING = 2; // 发送中

    public const PRODUCE_STATUS_SENT = 3; // 已发送

    // 日志类型
    public const LOG_TYPE_FILE = 1; // 日志文件

    public const LOG_TYPE_DB = 2; // 数据库
}
