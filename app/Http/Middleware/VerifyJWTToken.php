<?php

namespace App\Http\Middleware;

use Closure;
use Config;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Helpers;

class VerifyJWTToken
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
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Failed to validating token.',
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Token Expired.',
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid or blacklisted token.',
            ], $e->getStatusCode());
        }

        return $next($request);
    }

}
