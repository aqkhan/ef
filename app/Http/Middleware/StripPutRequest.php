<?php

namespace App\Http\Middleware;

use Closure;

class StripPutRequest
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

        $request_array = $request->all();
        $request_array['name'] = 'Guilherme';
        $request->replace($request_array);
        return $next($request);

    }
}
