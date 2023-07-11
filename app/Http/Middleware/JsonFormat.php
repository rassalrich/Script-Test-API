<?php

namespace App\Http\Middleware;

use Closure;

class JsonFormat
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
        // Pre-Middleware Action

        $request->headers->set('Accept', 'application/json');
        $response = $next($request);

        // Post-Middleware Action

        return $response;
    }
}
