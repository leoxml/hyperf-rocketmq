<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Library\Requests;

use Leoxml\RocketMQ\Library\Constants;
use Leoxml\RocketMQ\Library\Traits\MessagePropertiesForPublish;
use XMLWriter;

class PublishMessageRequest extends BaseRequest
{
    use MessagePropertiesForPublish;

    private $topicName;

    public function __construct($instanceId, $topicName, $messageBody, $properties = null, $messageTag = null)
    {
        parent::__construct($instanceId, 'post', 'topics/' . $topicName . '/messages');

        $this->topicName = $topicName;
        $this->messageBody = $messageBody;
        $this->messageTag = $messageTag;
        $this->properties = $properties;
    }

    public function getTopicName()
    {
        return $this->topicName;
    }

    public function generateBody(): string
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->openMemory();
        $xmlWriter->startDocument('1.0', 'UTF-8');
        $xmlWriter->startElementNS(null, 'Message', Constants::XML_NAMESPACE);
        $this->writeMessagePropertiesForPublishXML($xmlWriter);
        $xmlWriter->endElement();
        $xmlWriter->endDocument();
        return $xmlWriter->outputMemory();
    }

    public function generateQueryString(): ?string
    {
        if ($this->instanceId != null && $this->instanceId != '') {
            return http_build_query(['ns' => $this->instanceId]);
        }
        return null;
    }
}
