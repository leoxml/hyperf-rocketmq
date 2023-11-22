<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Model;

use Hyperf\DbConnection\Model\Model;

class MqConsumeLog extends Model
{
    protected ?string $table = 'rocketmq_consume_log';
}
