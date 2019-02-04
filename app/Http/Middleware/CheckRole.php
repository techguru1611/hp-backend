<?php

namespace App\Http\Middleware;

use Closure;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        $role = explode('-',$role);
        if ($request->user()->hasRole($role)) {
            return $next($request);
        }
        return response()->json([
            'status' => 0,
            'message' => 'This action is unauthorized.',
        ], 401);
    }
}
