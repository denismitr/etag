<?php

namespace Denismitr\ETag;

use Closure;

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

        if ($response->getStatusCode() !== 200 || ! $request->expectsJson()) {
            return $response;
        }

        if ($request->method() === 'GET' || $request->method() === 'HEAD') {
            $etag = '"' . md5($response->getContent()) . '"';

            $response->header('ETag', $etag);

            $ifMatch = $request->header('If-Match');
            $ifNotMatch = $request->header('If-Not-Match');

            if ( ! is_null($ifMatch) ) {
                $etagList = explode(',', $ifMatch);

                if ( ! in_array($etag, $etagList) && ! in_array('*', $etagList) ) {
                    return response()->json([
                        'error' => [
                            'http_code' => 412,
                            'code' => 'PRECONDITION_FAILED',
                            'message' => 'Precondition failed.'
                        ]
                    ], 412);
                }
            } else if ( ! is_null($ifNotMatch) ) {
                $etagList = explode(',', $ifNotMatch);

                if ( in_array($etag, $etagList) || in_array('*', $etagList) ) {
                    return response()->json(null, 304);
                }
            }
        }

        return $response;
    }
}
