<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Producer extends AbstractAnnotation
{

    public function __construct(
        public string $poolName = 'default',
        public string $dbConnection = 'default',
        public string $topic = '',
        public string $messageKey = '',
        public string $messageTag = '',
        public bool   $addEnvExt = false,
    )
    {
    }
}
