<?php

declare(strict_types=1);

namespace Uncleqiu\RocketMQ\Model;

use Hyperf\DbConnection\Model\Model;

class MqProduceStatusLog extends Model
{
    protected ?string $table = 'rocketmq_produce_status_log';
}
