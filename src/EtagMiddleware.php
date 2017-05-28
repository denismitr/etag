<?php

namespace Denismitr\Etags;

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

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        if ($request->method() === 'GET' || $request->method() === 'HEAD') {
            $etag = '"' . md5($response->getContent()) . '"';

            $response->header('ETag', $etag);

            $ifMatch = $request->header('If-Match');
            $ifNotMatch = $request->header('If-Not-Match');

            if ($ifMatch) {
                $etagList = explode(',', $ifMatch);

                if ( ! in_array($etag, $etagList) || ! in_array('*') ) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => [
                                'http_code' => 412,
                                'code' => 'PRECONDITION_FAILED',
                                'message' => 'Precondition failed.'
                            ]
                        ]);
                    }

                    return response('Precondition failed', 412);
                }
            } else if ($ifNotMatch) {
                $etagList = explode(',', $ifNotMatch);

                if ( in_array($etag, $etagList) || in_array('*') ) {
                    if ($request->expectsJson()) {
                        return response()->json(null, 304);
                    }

                    return response(null, 304);
                }
            }
        }

        return $response;
    }
}
