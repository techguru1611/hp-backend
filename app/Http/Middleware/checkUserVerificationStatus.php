<?php

namespace App\Http\Middleware;

use Closure;
use Helpers;
use Config;

class checkUserVerificationStatus
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
        if (!$request->user()) {
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.UNAUTHORIZED_ACTION'),
            ], 401);
        }

        if ($request->user()->verification_status == Config::get('constant.PENDING_MOBILE_STATUS')) {
            auth()->invalidate(true);
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.user_not_verified'),
            ], 401);
        } else if ($request->user()->verification_status == Config::get('constant.REJECTED_MOBILE_STATUS')) {
            auth()->invalidate(true);
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.user_already_rejected'),
            ], 401);
        } else if ($request->user()->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) {
            auth()->invalidate(true);
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.user_not_registered'),
            ], 401);
        }
        $lastActivity = Helpers::checkLastActivity($request->user());
        if ($lastActivity === false) {
            auth()->invalidate(true);
            return response()->json([
                'status' => 0,
                'message' => 'Token Expired.',
            ], 401);
        }        

        $response = $next($request);

        // Manipulate response
        if ($response->headers->get('content-type') == 'application/json' && isset($response->original['status']) && $response->original['status'] == Config::get('constant.SUCCESS_RESPONSE_STATUS')) {
            $response->original['wallet_balance'] = ($request->user()->userDetail !== null) ? $request->user()->userDetail->balance_amount : 0;
            $response->original['commission_wallet_balance'] = ($request->user()->userDetail !== null) ? $request->user()->userDetail->commission_wallet_balance : 0;            
            $response->original['country_code'] = ($request->user()->userDetail !== null) ? $request->user()->userDetail->country_code : Config::get('constant.DEFAULT_COUNTRY');
        }
        
        return response()->json($response->original);
    }
}
