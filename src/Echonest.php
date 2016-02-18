<?php

namespace Chrismou\Echonest;

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
     * @param \GuzzleHttp\ClientInterface $httpClient
     * @param string $apiKey
     */
    public function __construct(\GuzzleHttp\ClientInterface $httpClient, $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $resource
     * @param string $action
     * @param array $params
     * @param bool $autoRateLimit
     * @param int $maxAttempts
     *
     * @return mixed
     * @throws Exception\TooManyAttemptsException
     */
    public function query($resource, $action, array $params = [], $autoRateLimit = true, $maxAttempts = 10)
    {
        if (!isset($params['apiKey'])) {
            $params['api_key'] = $this->apiKey;
        }

        $encodedParams = preg_replace('/%5B[0-9]+%5D/simU', '', http_build_query($params));

        $requestUrl = sprintf(
            '%s%s/%s?%s',
            $this->apiUrl,
            $resource,
            $action,
            $encodedParams
        );

        if ($autoRateLimit) {
            usleep($this->getRateLimitDelay());
        }

        for ($attempt=1; $attempt<=$maxAttempts; $attempt++) {
            try {
                $response = $this->doRequest($requestUrl);
                // If it hasn't thrown an exception, assume it's been successful
                break;
            } catch (\Exception $e) {
                // Do nothing
            }
        }

        if (!isset($response)) {
            throw new \Chrismou\Echonest\Exception\TooManyAttemptsException(
                "Echonest query abandoned after " . $attempt . " failed attempts"
            );
        }

        $this->setRateLimitData($response);

        return json_decode($response->getBody());
    }

    /**
     * @param string $requestUrl
     * @return \GuzzleHttp\Psr7\Response
     */
    protected function doRequest($requestUrl)
    {
        return $this->httpClient->get($requestUrl);
    }

    /**
     * @param \GuzzleHttp\Psr7\Response $response
     */
    protected function setRateLimitData(\GuzzleHttp\Psr7\Response $response)
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
}
