<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class AdminPermissions
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

        return $next($request);

        $user = Auth::user();

        $group = $user->user_group;

        if($group->name !== 'SysAdmin' || $group->name !== 'OfficeAdmin')

            return response([

                'error' => 'You do not have enough permissions.'

            ], 400);

        return $next($request);
    }
}
