<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  null|string  $guard
     *
     * @return mixed
     */
    public function handle($request, \Closure $next, $guard = null)
    {
        if ($this->auth->guard($guard)->guest()) {
            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}
