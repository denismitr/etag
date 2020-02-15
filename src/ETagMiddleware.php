<?php

namespace Denismitr\ETag;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ETagMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($this->isNonEtagable($response, $request)) {
            return $response;
        }

        $etag = $this->extractEtag($response);

        $response->header('ETag', $etag);

        $ifMatch = $request->header('If-Match');
        $ifNotMatch = $request->header('If-None-Match');

        if (is_string($ifMatch) && $this->noEtagMatch($etag, $ifMatch)) {
            return response()->json([
                'error' => [
                    'http_code' => 412,
                    'code' => 'PRECONDITION_FAILED',
                    'message' => 'Precondition failed.'
                ]
            ], 412);
        }

        if (is_string($ifNotMatch) && $this->isEtagMatch($etag, $ifNotMatch)) {
            return response()->json(null, 304);
        }

        return $response;
    }

    /**
     * @param JsonResponse|Response $response
     * @param Request $request
     * @return bool
     */
    private function isNonEtagable($response, Request $request): bool
    {
        if ($response->getStatusCode() !== 200 || ! $request->expectsJson()) {
            return true;
        }

        $method = strtolower($request->method());

        if ($method !== 'get' && $method !== 'head') {
            return true;
        }

        return false;
    }

    /**
     * @param string $etag
     * @param string $ifMatch
     * @return bool
     */
    private function noEtagMatch(string $etag, string $ifMatch): bool
    {
        $etagList = explode(',', $ifMatch);

        return ! in_array($etag, $etagList) && ! in_array('*', $etagList);
    }

    /**
     * @param string $etag
     * @param string $ifNonMatch
     * @return bool
     */
    private function isEtagMatch(string $etag, string $ifNonMatch): bool
    {
        $etagList = explode(',',  $ifNonMatch);

        return in_array($etag, $etagList) || in_array('*', $etagList);
    }

    /**
     * @param JsonResponse|Response $response
     * @return string
     */
    private function extractEtag($response): string
    {
        return '"' . md5($response->getContent()) . '"';
    }
}
