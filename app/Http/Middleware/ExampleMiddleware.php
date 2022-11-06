<?php

namespace App\Http\Middleware;

class ExampleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        return $next($request);
    }
}
