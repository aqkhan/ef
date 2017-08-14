<?php

namespace App\Http\Middleware;

use App\Employee;
use Closure;

class CheckEmployeeExistance
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

        if ($request->employee_id){

            $sale = Employee::findOrFail($request->employee_id);

        }

        return $next($request);

    }
}
