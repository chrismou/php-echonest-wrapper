<?php

namespace Chrismou\Echonest;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

class Echonest
{
    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var int
     */
    protected $rateLimit;

    /**
     * @var int
     */
    protected $rateLimitRemaining;

    /**
     * @var string
     */
    protected $lastRequestTimestamp;

    /**
     * @var string
     */
    protected $apiUrl = 'http://developer.echonest.com/api/v4/';

    /**
     * @param ClientInterface $httpClient
     * @param string $apiKey
     * @param LoggerInterface|null $logger
     */
    public function __construct(ClientInterface $httpClient, $apiKey, LoggerInterface $logger = null)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    /**
     * Make a GET request to the Echonest API
     *
     * @param string $resource
     * @param string $action
     * @param array $urlParams
     *
     * @return array|null
     * @throws Exception\TooManyAttemptsException
     */
    public function get($resource, $action, array $urlParams = [])
    {
        return $this->query('GET', $resource, $action, $urlParams, [], []);
    }

    /**
     * Make a POST request to the Echonest API
     *
     * @param string $resource
     * @param string $action
     * @param array $urlParams
     * @param array $formParms
     *
     * @return array|null
     * @throws Exception\TooManyAttemptsException
     */
    public function post($resource, $action, array $urlParams = [], array $formParms = [])
    {
        return $this->query('POST', $resource, $action, $urlParams, $formParms);
    }

    /**
     * @param string $httpMethod
     * @param string $resource
     * @param string $action
     * @param array $urlParams
     * @param array $formParams
     * @param bool $autoRateLimit
     * @param int $maxAttempts
     *
     * @return array|null
     * @throws Exception\TooManyAttemptsException
     */
    public function query(
        $httpMethod,
        $resource,
        $action,
        array $urlParams = [],
        array $formParams = [],
        $autoRateLimit = true,
        $maxAttempts = 10
    ) {
        if ($autoRateLimit) {
            usleep($this->getRateLimitDelay());
        }

        $options = [];

        if (count($formParams)) {
            $options['form_params'] = $formParams;
        }

        for ($attempt=1; $attempt<=$maxAttempts; $attempt++) {
            try {
                $response = $this->doRequest(
                    $httpMethod,
                    $this->buildRequestUrl($resource, $action, $urlParams),
                    $options
                );
                // If it hasn't thrown an exception, we can assume it's been successful
                break;
            } catch (\Exception $e) {
                $this->writeLog('warning', $e->getMessage());
            }
        }

        if (!isset($response)) {
            $message = "Echonest query abandoned after " . $maxAttempts . " failed attempts";

            $this->writeLog('error', $message);
            throw new \Chrismou\Echonest\Exception\TooManyAttemptsException($message);
        }

        $this->setRateLimitData($response);

        return json_decode($response->getBody());
    }

    /**
     * @param string $httpMethod
     * @param string $requestUrl
     * @param array $options
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function doRequest($httpMethod, $requestUrl, array $options)
    {
        return $this->httpClient->request(
            $httpMethod,
            $requestUrl,
            $options
        );
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface
     */
    protected function setRateLimitData(\Psr\Http\Message\ResponseInterface $response)
    {
        $this->rateLimit = (int) $response->getHeader('x-ratelimit-limit');
        $this->rateLimitRemaining = (int) $response->getHeader('x-ratelimit-remaining');
        $this->lastRequestTimestamp = (int) strtotime($response->getHeader('date')[0]);
    }

    /**
     * @return int
     */
    protected function getRateLimitDelay()
    {
        $wait = 1.1 * 1000000;

        if ($this->lastRequestTimestamp) {
            $nextMinute = date('U', strtotime(date('Y-m-d H:i:', ((int) $this->lastRequestTimestamp + 60)).'00'));
            $now = time();

            $diff = $nextMinute - $now;

            if ($diff > 0 && $this->rateLimitRemaining > 1) {
                $wait = ($diff / ($this->rateLimitRemaining-1)) * 1100000;
            }
        }

        return $wait;
    }

    /**
     * @param string $resource
     * @param string $action
     * @param array $urlParams
     *
     * @return string
     */
    protected function buildRequestUrl($resource, $action, array $urlParams = [])
    {
        if (!isset($urlParams['apiKey'])) {
            $urlParams['api_key'] = $this->apiKey;
        }

        $encodedParams = preg_replace('/%5B[0-9]+%5D/simU', '', http_build_query($urlParams));

        return sprintf(
            '%s%s/%s?%s',
            $this->apiUrl,
            $resource,
            $action,
            $encodedParams
        );
    }

    /**
     * @param string $level
     * @param string $message
     */
    protected function writeLog($level, $message)
    {
        if ($this->logger) {
            $this->logger->$level($message);
        }
    }
}
