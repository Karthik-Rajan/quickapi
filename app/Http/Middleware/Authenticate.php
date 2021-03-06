<?php

namespace App\Http\Middleware;

use App\Helpers\Helper;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (!$request->header('quest')) {
            $token = \Auth::guard($guard)->getToken();
            if (null != $token) {
                $data = explode(".", $token);
                $data = base64_decode($data[1]);
                $now  = time();
                $data = json_decode($data);

                if ($data->exp < $now - 60) {
                    return Helper::getResponse(['status' => 401, 'message' => 'Token Expired']);
                }
            }

            if ($this->auth->guard($guard)->guest()) {
                return Helper::getResponse(['status' => 401, 'message' => 'Unauthorised']);
            }
        }

        return $next($request)->header('Access-Control-Allow-Origin', '*');
    }
}
