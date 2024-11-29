<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Model;

use Hyperf\DbConnection\Model\Model;

class MqErrorLog extends Model
{
    protected ?string $table = 'rocketmq_error_log';
}
