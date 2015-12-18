<?php

namespace Chrismou\Echonest\tests;

use Chrismou\Echonest\Echonest;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_TestCase;
use \Mockery as m;

class EchonestTest extends PHPUnit_Framework_TestCase
{
    /** @var \Mockery\MockInterface */
    protected $mockClient;

    /** @var \Chrismou\Echonest\Echonest  */
    protected $echonest;

    /** @var string */
    protected $apiResponse;

    /** @var \GuzzleHttp\Psr7\Response */
    protected $guzzleResponse;

    /** @var string */
    protected $apiUrl;

    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $resource;

    /** @var string */
    protected $action;

    /** @var array */
    protected $dummyResponseHeaders;

    /**
     * Setup the test class
     */
    public function setUp()
    {
        $this->mockClient = m::mock('\GuzzleHttp\ClientInterface', [
            'get' => null
        ]);

        $this->apiResponse = json_encode(["key" => "value"]);

        $this->dummyResponseHeaders = [
            'x-ratelimit-limit' => '2',
            'x-ratelimit-remaining' => '2',
            'date' => [date('Y-m-d H:i:').'00'],
        ];

        $this->guzzleResponse = new Response(
            200,
            $this->dummyResponseHeaders,
            $this->apiResponse
        );

        $this->apiUrl = 'http://developer.echonest.com/api/v4/';

        $this->apiKey = 'abcde';

        $this->resource = 'artist';

        $this->action = 'search';

        $this->echonest = new Echonest($this->mockClient, $this->apiKey);
    }

    /** @test */
    public function it_makes_a_request()
    {
        $this->mockClient->shouldReceive('get')
            ->with($this->buildRequestUrl())
            ->once()
            ->andReturn($this->guzzleResponse);

        $response = $this->echonest->query($this->resource, $this->action);

        $this->assertEquals(json_decode($this->apiResponse), $response);
    }

    /** @test */
    public function it_retries_a_request_when_exception_is_thrown()
    {
        $this->mockClient->shouldReceive('get')
            ->with($this->buildRequestUrl())
            ->once()
            ->andThrow(
                new \GuzzleHttp\Exception\ServerException(
                    'Internal Server Error',
                    m::mock('Psr\Http\Message\RequestInterface')
                )
            );

        $this->mockClient->shouldReceive('get')
            ->with($this->buildRequestUrl())
            ->once()
            ->andReturn($this->guzzleResponse);

        $response = $this->echonest->query($this->resource, $this->action);

        $this->assertEquals(json_decode($this->apiResponse), $response);
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function it_quits_when_it_hits_the_max_retries_limit()
    {
        $this->echonest = new Echonest($this->mockClient, $this->apiKey);

        $this->mockClient->shouldReceive('get')
            ->with($this->buildRequestUrl())
            ->twice()
            ->andThrow(
                new \GuzzleHttp\Exception\ServerException(
                    'Internal Server Error',
                    m::mock('Psr\Http\Message\RequestInterface')
                )
            );

        $this->echonest->query($this->resource, $this->action, [], true, 2);

        //$this->assertEquals(json_decode($this->apiResponse, true), $response);
    }

    /** @test */
    public function it_waits_between_requests()
    {
        $this->mockClient->shouldReceive('get')
            ->with($this->buildRequestUrl())
            ->twice()
            ->andReturn($this->guzzleResponse);

        $this->echonest->query($this->resource, $this->action);

        $start  = new \DateTime();
        $this->echonest->query($this->resource, $this->action);
        $end  = new \DateTime();

        $diff = $start->diff($end);

        $this->assertTrue($diff->format('%S') >= 1);
    }

    protected function buildRequestUrl()
    {
        return sprintf(
            '%s%s/%s?%s',
            $this->apiUrl,
            $this->resource,
            $this->action,
            preg_replace('/%5B[0-9]+%5D/simU', '', http_build_query(['api_key' => $this->apiKey]))
        );
    }

    /**
     * Custom teardown to include mockery expectations as assertions
     */
    public function tearDown()
    {
        if ($container = \Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }

        \Mockery::close();

        parent::tearDown();
    }
}
