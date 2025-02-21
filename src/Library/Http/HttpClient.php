<?php

declare(strict_types=1);

namespace Leoxml\RocketMQ\Library\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use Leoxml\RocketMQ\Library\AsyncCallback;
use Leoxml\RocketMQ\Library\Config;
use Leoxml\RocketMQ\Library\Constants;
use Leoxml\RocketMQ\Library\Exception\MQException;
use Leoxml\RocketMQ\Library\Requests\BaseRequest;
use Leoxml\RocketMQ\Library\Responses\BaseResponse;
use Leoxml\RocketMQ\Library\Responses\MQPromise;
use Leoxml\RocketMQ\Library\Signature\Signature;

class HttpClient
{
    protected string $endpoint;

    private Client $client;

    private string $accessId;

    private string $accessKey;

    private ?string $securityToken;

    private ?float $requestTimeout;

    private ?float $connectTimeout;

    private string $agent;

    public function __construct(
        $endPoint,
        $accessId,
        $accessKey,
        $securityToken = null,
        Config $config = null
    ) {
        if ($config == null) {
            $config = new Config();
        }
        $this->accessId = $accessId;
        $this->accessKey = $accessKey;

        $this->client = new Client([
            'base_uri' => $endPoint,
            'handler' => $config->getHandler(),
            'timeout' => $config->getRequestTimeout(),
            'defaults' => [
                'headers' => [
                    'Host' => $endPoint,
                ],
                'proxy' => $config->getProxy(),
                'expect' => $config->getExpectContinue(),
            ],
        ]);
        $this->requestTimeout = $config->getRequestTimeout();
        $this->connectTimeout = $config->getConnectTimeout();
        $this->securityToken = $securityToken;
        $this->endpoint = $endPoint;
        $guzzleVersion = Client::MAJOR_VERSION;
        $this->agent = Constants::CLIENT_VERSION . $guzzleVersion . ' PHP/' . PHP_VERSION . ')';
    }

    public function sendRequestAsync(
        BaseRequest $request,
        BaseResponse &$response,
        AsyncCallback $callback = null
    ): MQPromise {
        $promise = $this->sendRequestAsyncInternal($request, $response, $callback);
        return new MQPromise($promise, $response);
    }

    public function sendRequest(BaseRequest $request, BaseResponse &$response)
    {
        $promise = $this->sendRequestAsync($request, $response);
        return $promise->wait();
    }

    private function addRequiredHeaders(BaseRequest &$request)
    {
        $body = $request->generateBody();
        $queryString = $request->generateQueryString();

        $request->setBody($body);
        $request->setQueryString($queryString);

        $request->setHeader(Constants::USER_AGENT, $this->agent);
        if ($body != null) {
            $request->setHeader(Constants::CONTENT_LENGTH, strlen($body));
        }
        $request->setHeader('Date', gmdate(Constants::GMT_DATE_FORMAT));
        if (! $request->isHeaderSet(Constants::CONTENT_TYPE)) {
            $request->setHeader(Constants::CONTENT_TYPE, 'text/xml');
        }
        $request->setHeader(Constants::VERSION_HEADER, Constants::VERSION_VALUE);

        if ($this->securityToken != null) {
            $request->setHeader(Constants::SECURITY_TOKEN, $this->securityToken);
        }

        $sign = Signature::SignRequest($this->accessKey, $request);
        $request->setHeader(
            Constants::AUTHORIZATION,
            Constants::AUTH_PREFIX . ' ' . $this->accessId . ':' . $sign
        );
    }

    private function sendRequestAsyncInternal(BaseRequest &$request, BaseResponse &$response, AsyncCallback $callback = null): PromiseInterface
    {
        $this->addRequiredHeaders($request);

        $parameters = ['exceptions' => false, 'http_errors' => false];
        $queryString = $request->getQueryString();
        $body = $request->getBody();
        if ($queryString != null) {
            $parameters['query'] = $queryString;
        }
        if ($body != null) {
            $parameters['body'] = $body;
        }

        $parameters['timeout'] = $this->requestTimeout;
        $parameters['connect_timeout'] = $this->connectTimeout;

        $request = new Request(
            strtoupper($request->getMethod()),
            $request->getResourcePath(),
            $request->getHeaders()
        );
        try {
            if ($callback != null) {
                return $this->client->sendAsync($request, $parameters)->then(
                    function ($res) use (&$response, $callback) {
                        try {
                            $response->setRequestId($res->getHeaderLine('x-mq-request-id'));
                            $callback->onSucceed($response->parseResponse($res->getStatusCode(), $res->getBody()));
                        } catch (MQException $e) {
                            $callback->onFailed($e);
                        }
                    }
                );
            } else {
                return $this->client->sendAsync($request, $parameters);
            }
        } catch (TransferException $e) {
            $message = $e->getMessage();
            if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
                $message = $e->getResponse()->getBody();
            }
            throw new MQException($e->getCode(), $message, $e);
        }
    }
}
