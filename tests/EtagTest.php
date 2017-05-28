<?php

namespace Tests;

use Denismitr\ETag\ETagMiddleware;
use Illuminate\Http\Request;
use Mockery as m;

class Test extends \PHPUnit\Framework\TestCase
{
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

        // Request
        $request = Request::create('http://example.com/articles', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json'
        ]);

        $middleware = new ETagMiddleware;

        $returnedResponse = $middleware->handle($request, function() use ($response) {
            return $response;
        });

        $this->assertNotNull($returnedResponse);
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