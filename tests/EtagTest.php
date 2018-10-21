<?php

namespace Tests;

use Denismitr\ETag\ETagMiddleware;
use Illuminate\Http\Request;
use Mockery as m;

class Test extends \Orchestra\Testbench\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected $content;
    protected $etag;

    function setUp()
    {
        parent::setUp();

        $this->content = $this->getValidJsonResponseContent();
        $this->etag = $this->getValidEtag($this->content);
    }

    public function tearDown()
    {
        m::close();
    }

    /** @test */
    public function it_returns_response_with_etag_header()
    {
        $response = $this->getResponseMockWithEtag($this->content, $this->etag);

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('method')->once()->andReturn('GET');
        $request->shouldReceive('expectsJson')->once()->andReturn(true);
        $request->shouldReceive('header')->with('If-Match')->once()->andReturn(null);
        $request->shouldReceive('header')->with('If-None-Match')->once()->andReturn(null);

        $response = $this->runMiddleware($request, $response);
        $this->assertInstanceOf('Mockery_0_Illuminate_Http_Response', $response);
    }

    /** @test */
    public function it_returns_304_unmodified_when_the_correct_etag_is_passed()
    {
        $response = $this->getResponseMockWithEtag($this->content, $this->etag);

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('method')->once()->andReturn('GET');
        $request->shouldReceive('expectsJson')->once()->andReturn(true);
        $request->shouldReceive('header')->with('If-Match')->once()->andReturn(null);
        $request->shouldReceive('header')->with('If-None-Match')->once()->andReturn($this->etag);

        $response = $this->runMiddleware($request, $response);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(304, $response->status());
    }

    /** @test */
    public function it_returns_304_unmodified_when_the_correct_asterix_is_passed()
    {
        $response = $this->getResponseMockWithEtag($this->content, $this->etag);

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('expectsJson')->once()->andReturn(true);
        $request->shouldReceive('method')->once()->andReturn('GET');
        $request->shouldReceive('header')->with('If-Match')->once()->andReturn(null);
        $request->shouldReceive('header')->with('If-None-Match')->once()->andReturn('*');

        $response = $this->runMiddleware($request, $response);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(304, $response->status());
    }

    /** @test */
    public function it_receives_new_content_if_etag_not_in_if_not_match_header()
    {
        $response = $this->getResponseMockWithEtag($this->content, $this->etag);

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('expectsJson')->once()->andReturn(true);
        $request->shouldReceive('method')->once()->andReturn('GET');
        $request->shouldReceive('header')->with('If-Match')->once()->andReturn(null);
        $request->shouldReceive('header')->with('If-None-Match')->once()->andReturn('"wrong-etag"');

        $response = $this->runMiddleware($request, $response);

        $this->assertInstanceOf('Mockery_0_Illuminate_Http_Response', $response);
    }

    /** @test */
    public function it_receives_unmodified_response_if_etag_match_with_if_match_header()
    {
        $response = $this->getResponseMockWithEtag($this->content, $this->etag);

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('expectsJson')->once()->andReturn(true);
        $request->shouldReceive('method')->once()->andReturn('GET');
        $request->shouldReceive('header')->with('If-Match')->once()->andReturn($this->etag);
        $request->shouldReceive('header')->with('If-None-Match')->once()->andReturn(null);

        $response = $this->runMiddleware($request, $response);

        $this->assertInstanceOf('Mockery_0_Illuminate_Http_Response', $response);
    }

    /** @test */
    public function it_receives_unmodified_response_if_asterix_given_with_if_match_header()
    {
        $response = $this->getResponseMockWithEtag($this->content, $this->etag);

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('expectsJson')->once()->andReturn(true);
        $request->shouldReceive('method')->once()->andReturn('GET');
        $request->shouldReceive('header')->with('If-Match')->once()->andReturn('*');
        $request->shouldReceive('header')->with('If-None-Match')->once()->andReturn(null);

        $response = $this->runMiddleware($request, $response);

        $this->assertInstanceOf('Mockery_0_Illuminate_Http_Response', $response);
    }

    /** @test */
    public function it_receives_412_with_json_error_if_etag_not_match_the_if_match_header()
    {
        $response = $this->getResponseMockWithEtag($this->content, $this->etag);

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('expectsJson')->once()->andReturn(true);
        $request->shouldReceive('method')->once()->andReturn('GET');
        $request->shouldReceive('header')->with('If-Match')->once()->andReturn('"wrong-etag"');
        $request->shouldReceive('header')->with('If-None-Match')->once()->andReturn(null);

        $response = $this->runMiddleware($request, $response);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(412, $response->status());
        $this->assertSame($response->getData(true), [
            "error" => [
                "http_code" => 412,
                "code" => "PRECONDITION_FAILED",
                "message" => "Precondition failed."
            ]
        ]);
    }

    /** @test */
    public function it_returns_the_response_untouched_if_response_has_error_status()
    {
        $response = $this->getResponseWithErrorCode(403, 2);

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldNotReceive('method');
        $request->shouldNotReceive('expectsJson');

        $response = $this->runMiddleware($request, $response);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_returns_the_response_untouched_if_response_does_not_want_json()
    {
        $response = $this->getResponseWithoutJson();

        $request = m::mock('Illuminate\Http\Request');
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('expectsJson')->once()->andReturn(false);
        $request->shouldNotReceive('method');

        $response = $this->runMiddleware($request, $response);

        $this->assertInstanceOf('Mockery_0_Illuminate_Http_Response', $response);
    }

    /**
     * Run the middleware
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    protected function runMiddleware($request, $response)
    {
        $middleware = new ETagMiddleware;

        return $middleware->handle($request, function() use ($response) {
            return $response;
        });
    }

    /**
     * Get etag
     * @param string $content
     * @return string
     */
    protected function getValidEtag($content)
    {
        return '"' . md5($content) . '"';
    }


    protected function getResponseMockWithEtag($content, $etag)
    {
        $response = m::mock('Illuminate\Http\Response')
            ->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200)
            ->shouldReceive('getContent')
            ->once()
            ->andReturn($content)
            ->getMock();

        $response
            ->shouldReceive('header')
            ->once()
            ->with('ETag', $etag);

        return $response;
    }

    protected function getResponseWithErrorCode($code = 401, $times = 1)
    {
        $response = m::mock('Illuminate\Http\Response');

        $response->shouldReceive('getStatusCode')
            ->times($times)
            ->andReturn($code);

        $response->shouldNotReceive('header');
        $response->shouldNotReceive('getContent');

        return $response;
    }

    protected function getResponseWithoutJson()
    {
        $response = m::mock('Illuminate\Http\Response');

        $response->shouldReceive('getStatusCode')
            ->times(1)
            ->andReturn(200);

        $response->shouldNotReceive('header');
        $response->shouldNotReceive('getContent');

        return $response;
    }

    /**
     * Get response contant sample content
     *
     * @return array
     */
    protected function getValidJsonResponseContent()
    {
        return json_encode([
            "content" => [
                "articles" => [
                    [
                        "title" => "Article 1"
                    ],
                    [
                        "title" => "Article 2"
                    ]
                ]
            ]
        ]);
    }
}