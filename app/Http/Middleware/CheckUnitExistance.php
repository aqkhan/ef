<?php

namespace App\Http\Middleware;

use App\Unit;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CheckUnitExistance
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

        if ($request->unit_id){

            $sale = Unit::findOrFail($request->unit_id);

        }

        return $next($request);
    }
}
