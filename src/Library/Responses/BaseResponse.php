<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Library\Responses;

use Exception;
use DOMDocument;
use XMLReader;
use Leoxml\RocketMQ\Library\Exception\MQException;

abstract class BaseResponse
{
    protected $succeed;

    protected $statusCode;

    // from header
    protected $requestId;

    abstract public function parseResponse($statusCode, $content);

    abstract public function parseErrorResponse($statusCode, $content, MQException $exception = null);

    public function isSucceed()
    {
        return $this->succeed;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    protected function loadXmlContent($content): XMLReader
    {
        $content = (string)$content;
        $xmlReader = new XMLReader();
        $isXml = $xmlReader->XML($content);
        if ($isXml === false) {
            throw new MQException($this->statusCode, $content);
        }
        try {
            while ($xmlReader->read()) {
            }
        } catch (Exception $e) {
            throw new MQException($this->statusCode, $content);
        }
        $xmlReader->XML($content);
        return $xmlReader;
    }

    protected function loadAndValidateXmlContent($content, &$xmlReader): bool
    {
        $doc = new DOMDocument();
        if (!$doc->loadXML($content)) {
            return false;
        }
        $xmlReader = $this->loadXmlContent($content);
        return true;
    }
}
