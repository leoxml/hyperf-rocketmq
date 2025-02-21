<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Library\Responses;

use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

class MQPromise
{
    private $response;

    private $promise;

    public function __construct(PromiseInterface &$promise, BaseResponse &$response)
    {
        $this->promise = $promise;
        $this->response = $response;
    }

    public function isCompleted(): bool
    {
        return $this->promise->getState() != 'pending';
    }

    public function getResponse(): BaseResponse
    {
        return $this->response;
    }

    public function getState(): string
    {
        return $this->promise->getState();
    }

    /**
     * @return mixed
     */
    public function wait()
    {
        try {
            $res = $this->promise->wait();
            if ($res instanceof ResponseInterface) {
                $this->response->setRequestId($res->getHeaderLine('x-mq-request-id'));
                return $this->response->parseResponse($res->getStatusCode(), $res->getBody());
            }
        } catch (TransferException $e) {
            $message = $e->getMessage();
            if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
                $message = $e->getResponse()->getBody();
            }
            $this->response->parseErrorResponse($e->getCode(), $message);
        }
        $this->response->parseErrorResponse('500', 'Unknown');
    }
}
