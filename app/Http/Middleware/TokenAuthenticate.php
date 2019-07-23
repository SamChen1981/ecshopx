<?php

namespace app\api\middleware;

use Closure;
use app\api\library\Token;
use app\api\library\Protocol;

class TokenAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param \think\facade\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = Token::authorization();

        if ($token === false) {
            return show_error(10001, trans('message.token.invalid'));
        }

        if ($token === 'token-expired') {
            return show_error(10002, trans('message.token.expired'));
        }

        return $next($request);
    }
}
