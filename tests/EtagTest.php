<?php

namespace Tests;

use Denismitr\ETag\ETagMiddleware;
use Illuminate\Http\Request;
use Mockery as m;

class Test extends \Orchestra\Testbench\TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /** @test */
    public function it_returns_response_with_etag_header()
    {
        $response = m::mock('Illuminate\Http\Response')
            ->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200)
            ->shouldReceive('getContent')
            ->once()
            ->andReturn($this->getValidResponseContent())
            ->getMock();

        $response
            ->shouldReceive('header')
            ->once()
            ->with('ETag', $this->getValidEtag(
                $this->getValidResponseContent()
            ));

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('method')->andReturn('GET');
        $request->shouldReceive('expectsJson')->andReturn(true);
        $request->shouldReceive('header')->with('If-Match')->once()->andReturn(null);
        $request->shouldReceive('header')->with('If-Not-Match')->once()->andReturn(null);

        $this->runMiddleware($request, $response);
    }

    /** @test */
    public function it_returns_304_unmodified_when_the_correct_etag_is_passed()
    {
        $data = $this->getValidResponseContent();
        $etag = $this->getValidEtag($data);

        $response = m::mock('Illuminate\Http\Response')
            ->shouldReceive('getStatusCode')
            ->once()
            ->andReturn(200)
            ->shouldReceive('getContent')
            ->once()
            ->andReturn($data)
            ->getMock();

        $response
            ->shouldReceive('header')
            ->once()
            ->with('ETag', $etag);

        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('method')->andReturn('GET');
        $request->shouldReceive('expectsJson')->andReturn(true);
        $request->shouldReceive('header')->with('If-Match')->once()->andReturn(null);
        $request->shouldReceive('header')->with('If-Not-Match')->once()->andReturn($etag);

        $response = $this->runMiddleware($request, $response);

        $this->assertEquals(304, $response->status());
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
     * @param string $data
     * @return string
     */
    protected function getValidEtag($data)
    {
        return '"' . md5($data) . '"';
    }

    protected function getValidResponseContent()
    {
        return json_encode([
            "data" => [
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