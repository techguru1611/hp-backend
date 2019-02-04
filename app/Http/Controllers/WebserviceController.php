<?php

namespace App\Http\Controllers;

use App\ExternalUser;
use App\User;
use App\UserTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebserviceController extends Controller
{
    public function __construct()
    {
        $this->objUser = new User();
    }

    /**
     * check user is exists or not
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userExists(Request $request){
        try{

            $rule = [
                'country_code' => 'required',
                'mobile_number' => 'required'
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'isAccountAvailable' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $auth_user =  $request->bearerToken();
            $auth_user = base64_decode($auth_user);
            $auth_user = explode(':' , $auth_user);
            $auth_user_id = $auth_user[0];
            $auth_user_secret = $auth_user[1];
            $extUser = DB::table('external_users')->where('user_id',$auth_user_id)->where('user_secret',$auth_user_secret)->first();
            if (!$extUser){
                return response()->json([
                    'status' => 0,
                    'isAccountAvailable' => 0,
                    'message' => trans('apimessages.unauthorized_access'),
                ], 401);
            }

            $mobile_number = $request->country_code.$request->mobile_number;
            $user = User::where('mobile_number',$mobile_number)->where('verification_status',1)->first();
            if (!$user){
                return response()->json([
                    'status' => 0,
                    'isAccountAvailable' => 0,
                    'message' => trans('apimessages.user_not_found'),
                ], 200);
            }

            return response()->json([
                'status' => 1,
                'isAccountAvailable' => 1,
                'message' => trans('apimessages.default_success_msg'),
            ],200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * link account
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function linkAccount(Request $request){
        try{

            $rule = [
                'country_code' => 'required',
                'mobile_number' => 'required'
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $auth_user =  $request->bearerToken();
            $auth_user = base64_decode($auth_user);
            $auth_user = explode(':' , $auth_user);
            $auth_user_id = $auth_user[0];
            $auth_user_secret = $auth_user[1];
            $extUser = DB::table('external_users')->where('user_id',$auth_user_id)->where('user_secret',$auth_user_secret)->first();
            if (!$extUser){
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.unauthorized_access'),
                ], 401);
            }

            $mobile_number = $request->country_code.$request->mobile_number;
            $user = User::where('mobile_number',$mobile_number)->where('verification_status',1)->first();
            if (!$user){
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.user_not_found'),
                ], 200);
            }

            $user->update([
                'last_activity_at' => Carbon::now(),
            ]);
            $token = auth()->login($user);
            $userDetails = $user->userDetail()->first();
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'loginToken' => $token,
                'data' => [
                    'id' => $user->id,
                    'mobile_number' => $user->mobile_number,
                    'balance_amount' => $userDetails->balance_amount
                ]
            ],200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    public function extPayment(Request $request){
        try {
            $rule = [
                'app_id' => 'required',
                'amount' => 'required',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            $user = $request->user();
            $userDetails = $user->userDetail()->first();
            // If sender doesn't have sufficient balance
            if ($userDetails->balance_amount < $request->amount) {
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'), [
                    '<Mobile Number>' => $request->user()->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.insufficient_balance_msg'),
                ], 200);
            }

            // Deduct from sender balance
            $userDetails->balance_amount -= $request->amount;
            $userDetails->save();

            // Add Balance to admin user
            $adminData = User::with('userDetail')->where('role_id', Config::get('constant.SUPER_ADMIN_ROLE_ID'))->first();
            // Upgrade wallet balance
            $adminData->userDetail->balance_amount += $request->amount;
            $adminData->userDetail->save();

            $external_user = ExternalUser::where('user_id', $request->app_id)->first();

            // Save transaction history
            $transaction = new UserTransaction([
                'external_user_id' => $external_user->id,
                'amount' => $request->amount,
                'net_amount' => $request->amount,
                'description' => 'Paid To '.$external_user->name,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.SUCCESS_TRANSACTION_STATUS'),
                'transaction_type' => Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE'),
                'created_by' => $request->user()->id,
            ]);

            $user->senderTransaction()->save($transaction);

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $transaction,
            ], 200);


        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }
}
