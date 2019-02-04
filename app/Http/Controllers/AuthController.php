<?php

namespace App\Http\Controllers;

use App\CountryCurrency;
use App\Mail\AdminLoginOTP;
use App\OTPManagement;
use App\User;
use App\UserDetail;
use App\UserLoginHistory;
use Auth;
use Carbon\Carbon;
use Config;
use DB;
use Hash;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Stevebauman\Location\Facades\Location;
use Validator;

class AuthController extends Controller
{
    public function __construct(AdminLoginOTP $adminLoginOTP)
    {
        $this->objUser = new User();
        $this->objUserDetail = new UserDetail();
        $this->objCountryCurrency = new CountryCurrency();
        $this->otpExpireSeconds = Config::get('constant.OTP_EXPIRE_SECONDS');
        $this->resendOtpExpireSeconds = Config::get('constant.RESEND_OTP_EXPIRE_SECONDS');
        $this->userOriginalImagePath = Config::get('constant.USER_ORIGINAL_IMAGE_UPLOAD_PATH');
        $this->adminLoginOTP = $adminLoginOTP;
    }

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try
        {
            $rule = [
                'name' => 'required',
                'password' => 'required|regex:/^[0-9]{4}$/',
                'mobile_number' => 'required',
                'user_type' => ['required', Rule::in([Config::get('constant.USER_ROLE_ID'), Config::get('constant.AGENT_ROLE_ID')])],
                'gender' => ['nullable', Rule::in([Config::get('constant.MALE'), Config::get('constant.FEMALE'), Config::get('constant.NON_BINARY')])],
            ];

            $userData = User::where('mobile_number', $request->mobile_number)->first();
            if ($userData === null || ($userData !== null && $userData->verification_status != Config::get('constant.PENDING_MOBILE_STATUS') && $userData->verification_status != Config::get('constant.UNREGISTERED_USER_STATUS'))) {
                $rule['mobile_number'] = 'required|min:10|max:17|regex:/^\+?\d+$/|unique:users,mobile_number';
            }

            // Customize error message
            $messages = [
                'password.required' => 'The pin field is required.',
                'password.regex' => 'The pin must have four digits.',
            ];

            $validator = Validator::make($request->all(), $rule, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            if ($userData !== null && $userData->verification_status_updater_role == Config::get('constant.SUPER_ADMIN_SLUG')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.inactive_account_found'),
                ], 200);
            }

            if ($userData !== null) {
                $data = [
                    'id' => $userData->id,
                    'role_id' => $request->user_type,
                    'full_name' => $request->name,
                    'password' => $request->password,
                    'gender' => $request->gender,
                ];

                // Send OTP
                $secondsSinceOtpCreated = Carbon::now()->diffInSeconds($userData->otp_created_date);
                $otp = $userData->otp;
                if ($secondsSinceOtpCreated > $this->resendOtpExpireSeconds || $otp == null) {
                    $otp = mt_rand(100000, 999999);
                    $data['otp'] = $otp;
                    $data['otp_created_date'] = Carbon::now();
                    $data['otp_date'] = Carbon::now();
                }
            } else {
                $otp = mt_rand(100000, 999999);
                $data = [
                    'role_id' => $request->user_type,
                    'full_name' => $request->name,
                    'mobile_number' => $request->mobile_number,
                    'password' => $request->password,
                    'gender' => $request->gender == null ? Config::get('constant.MALE') : $request->gender,
                    'otp' => $otp,
                    'otp_date' => Carbon::now(),
                    'otp_created_date' => Carbon::now(),
                ];

            }

            DB::beginTransaction();
            $user = $this->objUser->insertUpdate($data);

            if ($user) {
                if ($userData === null) {
                    // Save user detail
                    $userDetail = new UserDetail([
                        'balance_amount' => 0.00,
                        'country_code' => Config::get('constant.DEFAULT_COUNTRY'),
                    ]);
                    $user->userDetail()->save($userDetail);
                }

                $sendOtp = Helpers::sendMessage($request->mobile_number, trans('apimessages.your_otp_for_registering_in_helapay_is') . ' ' . $otp . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'));
                if (!$sendOtp) {
                    DB::rollback();
                    Log::channel('otp')->error(strtr(trans('log_messages.otp_error'), [
                        '<Mobile Number>' => $request->mobile_number,
                    ]));
                    return response()->json([
                        'status' => '0',
                        'message' => trans('apimessages.something_went_wrong'),
                    ], 200);
                }
                $om = new OTPManagement();
                $om->otp = $otp;
                $om->otp_sent_to = $request->mobile_number;
                $om->operation = Config::get('constant.OTP_O_REGISTER');
                $om->created_by = $user->id;
                $om->message = trans('apimessages.your_otp_for_registering_in_helapay_is') . ' ' . $otp . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS');
                $om->save();

                // Manage response message - Start
                if (Config::get('constant.DISPLAY_OTP') == 1) {
                    $responseMessage = strtr(trans('apimessages.OTP_SENT_SUCCESS_MESSAGE_FOR_DEV'), [
                        '<Mobile Number>' => $request->mobile_number,
                        '<OTP>' => $otp,
                    ]);
                } else {
                    $responseMessage = strtr(trans('apimessages.OTP_SENT_SUCCESS_MESSAGE_FOR_PROD'), [
                        '<Mobile Number>' => $request->mobile_number,
                    ]);
                }

                DB::commit();
                Log::channel('otp')->info(strtr(trans('log_messages.otp_success'), [
                    '<Otp>' => $otp,
                    '<Mobile Number>' => $request->mobile_number,
                ]));
                return response()->json([
                    'status' => 1,
                    'message' => $responseMessage,
                    'data' => [
                        'mobile_number' => $request->mobile_number,
                    ],
                ], 200);
            } else {
                DB::rollback();
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.error_registering_user'),
                ], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.error_registering_user'),
            ], 500);
        }
    }

    /**
     * Veryfy register OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyRegisterOTP(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required',
                'otp' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $userData = User::where('mobile_number', $request->mobile_number)->first();

            if ($userData === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.user_not_found'),
                ], 404);
            }

            if ($userData->verification_status == Config::get('constant.VERIFIED_MOBILE_STATUS')) {
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.user_already_verified'),
                ], 200);
            } elseif ($userData->verification_status == Config::get('constant.REJECTED_MOBILE_STATUS')) {
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.user_already_rejected'),
                ], 200);
            }

            if ($userData->otp_date === null || $userData->otp != $request->otp) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.invalid_otp'),
                ], 200);
            }

            $timeNow = Carbon::now();
            $totalDuration = $timeNow->diffInSeconds($userData->otp_date);

            if ($totalDuration <= $this->otpExpireSeconds) {
                $userData->otp = null;
                $userData->otp_date = null;
                $userData->otp_created_date = null;
                $userData->verification_status = Config::get('constant.VERIFIED_MOBILE_STATUS');
                $userData->kyc_comment = Config::get('constant.PENDING_KYC_COMMENT');
                $userData->save();
                // Sent welcome message to user
                $sendMessage = Helpers::sendMessage($request->mobile_number, trans('apimessages.welcome_to_helapay_message'));
                $token = auth()->login($userData);

                $userDetail = $userData->with('userDetail')->where('id', $userData->id)->first();

                // Split country code from mobile number
                $splitMobileNumber = Helpers::splitMobileNumber($userDetail->mobile_number);
                $userDetail->country_code = $splitMobileNumber['country_code'];
                $userDetail->mo_no = $splitMobileNumber['mo_no'];

                if ($request->input('platform') == 'WEB') {
                    $login_history = new UserLoginHistory();
                    $ip = $request->getClientIp();
                    $data = Location::get($ip);
                    $user_agent = $request->header('User-Agent');
                    $login_history->user_id = $userData->id;
                    $login_history->ip_address = $ip;
                    $login_history->platform = $request->input('platform');
                    $login_history->browser = $login_history->getBrowserDetail($user_agent);
                    $login_history->country_code = $data->countryCode;
                    $login_history->region_name = $data->regionName;
                    $login_history->city_name = $data->cityName;
                    $login_history->zip_code = $data->zipCode;
                    $login_history->latitude = $data->latitude;
                    $login_history->longitude = $data->longitude;
                    $login_history->status = Config::get('constant.LOGIN_SLUG');
                    $login_history->save();
                } elseif ($request->input('platform') == 'IOS' or $request->input('platform') == 'Android') {
                    $login_history = new UserLoginHistory();
                    $login_history->user_id = $userData->id;
                    $login_history->device_id = $request->input('device_id');
                    $login_history->platform = $request->input('platform');
                    $login_history->latitude = $request->input('latitude');
                    $login_history->longitude = $request->input('longitude');
                    $login_history->status = Config::get('constant.LOGIN_SLUG');
                    $login_history->save();
                }

                if (isset($userDetail->userDetail)) {
                    $userDetail->userDetail->photo = ($userDetail->userDetail->photo !== null && $userDetail->userDetail->photo != '') ? url('storage/' . $this->userOriginalImagePath . $userDetail->userDetail->photo) : url('images/default_user_profile.png');
                    $userDetail->wallet_balance = $userDetail->userDetail->balance_amount;
                    $userDetail->wallet_balance = $userDetail->userDetail->balance_amount;
                    $userDetail->commission_wallet_balance = $userDetail->userDetail->commission_wallet_balance;
                    $userDetail->country_code = $userDetail->userDetail->country_code;
                }

                Log::info(strtr(trans('log_messages.register_success'), [
                    '<User>' => $userDetail->email !== null ? $userDetail->email : $userDetail->mobile_number,
                ]));

                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.user_verified_suceessfully'),
                    'loginToken' => $token,
                    'data' => $userDetail,
                ], 200);
            } else {
                Log::error(strtr(trans('log_messages.register_error'), [
                    '<User>' => $request->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.invalid_otp'),
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.error_registering_user'),
            ], 500);
        }
    }

    /**
     * Get a authorization token via given credentials. (User)
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $token = null;

            $userData = User::where('mobile_number', $request->mobile_number)->whereNotIn('role_id', [Config::get('constant.SUPER_ADMIN_ROLE_ID'), Config::get('constant.COMPLIANCE_ROLE_ID')])->first();

            if ($userData) {
                $credentials = $request->only('mobile_number', 'password');
                if (!$token = $this->guard()->attempt($credentials)) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.invalid_login'),
                    ], 200);
                }

                if ($userData->verification_status == Config::get('constant.PENDING_MOBILE_STATUS')) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.invalid_login'),
                    ], 200);
                } else if ($userData->verification_status == Config::get('constant.REJECTED_MOBILE_STATUS')) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.user_already_rejected'),
                    ], 200);
                } else if ($userData->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.user_not_registered'),
                    ], 200);
                }

                $userData->update([
                    'last_activity_at' => Carbon::now(),
                ]);

                $userData = $userData->with('userDetail')->where('id', $userData->id)->first();

                // Split country code from mobile number
                $splitMobileNumber = Helpers::splitMobileNumber($userData->mobile_number);
                $userData->country_code = $splitMobileNumber['country_code'];
                $userData->mo_no = $splitMobileNumber['mo_no'];

                if ($request->input('platform') == 'WEB') {
                    $login_history = new UserLoginHistory();
                    $ip = $request->getClientIp();
                    $data = Location::get($ip);
                    $user_agent = $request->header('User-Agent');
                    $login_history->user_id = $userData->id;
                    $login_history->ip_address = $ip;
                    $login_history->platform = $request->input('platform');
                    $login_history->browser = $login_history->getBrowserDetail($user_agent);
                    $login_history->country_code = $data->countryCode;
                    $login_history->region_name = $data->regionName;
                    $login_history->city_name = $data->cityName;
                    $login_history->zip_code = $data->zipCode;
                    $login_history->latitude = $data->latitude;
                    $login_history->longitude = $data->longitude;
                    $login_history->status = Config::get('constant.LOGIN_SLUG');
                    $login_history->save();
                } elseif ($request->input('platform') == 'IOS' or $request->input('platform') == 'Android') {
                    $login_history = new UserLoginHistory();
                    $login_history->user_id = $userData->id;
                    $login_history->device_id = $request->input('device_id');
                    $login_history->platform = $request->input('platform');
                    $login_history->latitude = $request->input('latitude');
                    $login_history->longitude = $request->input('longitude');
                    $login_history->status = Config::get('constant.LOGIN_SLUG');
                    $login_history->save();
                }

                if (isset($userData->userDetail)) {
                    $userData->userDetail->photo = ($userData->userDetail->photo !== null && $userData->userDetail->photo != '') ? url('storage/' . $this->userOriginalImagePath . $userData->userDetail->photo) : url('images/default_user_profile.png');
                }

                Log::info(strtr(trans('log_messages.login_success'), [
                    '<User>' => $request->mobile_number,
                ]));

                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.logged_in'),
                    'loginToken' => $token,
                    'data' => $userData,
                ], 200);
            } else {
                Log::error(strtr(trans('log_messages.login_error'), [
                    '<User>' => $request->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.invalid_login'),
                ], 200);
            }
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
     * Get a OTP via given credentials.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try
        {
            // Customize error message
            $messages = [
                'password.required' => 'The pin field is required.',
                'password.regex' => 'The pin must have four digits.',
            ];

            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required',
                'password' => 'required|regex:/^[0-9]{4}$/',
            ], $messages);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $validateMobileNumber = Helpers::validateMobileNumber($request->mobile_number);

            if (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0) {
                return response()->json([
                    'status' => 0,
                    'message' => $validateMobileNumber['message'],
                ], 200);
            }
            $userData = $this->objUser->where('mobile_number', $request->mobile_number)->first();

//            if(!Auth::attempt(['mobile_number'=>$request->mobile_number,'password'=>$request->password])){
            //                return response()->json([
            //                    'status' => 1,
            //                    'message' => trans('apimessages.invalid_login')
            //                ], 200);
            //            }
            // if ($userData) {
            //     $credentials = $request->only('mobile_number', 'password');
            //     if (!$token = JWTAuth::attempt($credentials)) {
            //         return response()->json([
            //             'status' => 0,
            //             'message' => trans('apimessages.invalid_login'),
            //         ], 200);
            //     }
            // }

            $otp = mt_rand(100000, 999999);
            $userData->otp = $otp;
            $userData->otp_date = date('Y-m-d H:i:s');
            $userData->otp_created_date = date('Y-m-d H:i:s');
            $userData->save();

            $sendOtp = Helpers::sendMessage($userData->mobile_number, trans('apimessages.your_otp_for_login_in_helapay_is') . ' ' . $otp . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'));

            if (!$sendOtp) {
                Log::channel('otp')->error(strtr(trans('log_messages.otp_error'), [
                    '<Mobile Number>' => $request->mobile_number,
                ]));
                return response()->json([
                    'status' => '0',
                    'message' => trans('apimessages.something_went_wrong'),
                ], 200);
            }

            Log::channel('otp')->info(strtr(trans('log_messages.otp_success'), [
                '<Otp>' => $otp,
                '<Mobile Number>' => $request->mobile_number,
            ]));

            $om = new OTPManagement();
            $om->otp = $otp;
            $om->otp_sent_to = $userData->mobile_number;
            $om->operation = Config::get('constant.OTP_O_LOGIN');
            $om->created_by = $userData->id;
            $om->message = trans('apimessages.your_otp_for_login_in_helapay_is') . ' ' . $otp . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS');
            $om->save();

            // Manage response message - Start
            if (Config::get('constant.DISPLAY_OTP') == 1) {
                $responseMessage = strtr(trans('apimessages.OTP_SENT_SUCCESS_MESSAGE_FOR_DEV'), [
                    '<Mobile Number>' => $userData->mobile_number,
                    '<OTP>' => $otp,
                ]);
            } else {
                $responseMessage = strtr(trans('apimessages.OTP_SENT_SUCCESS_MESSAGE_FOR_PROD'), [
                    '<Mobile Number>' => $userData->mobile_number,
                ]);
            }

            return response()->json([
                'status' => '1',
                'message' => $responseMessage,
                'data' => [
                    'mobile_number' => $userData->mobile_number,
                ],
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

    /**
     * Get a JWT via given credentials.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyLoginOtp(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required',
                'otp' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $token = null;

            $userData = User::where('mobile_number', $request->mobile_number)->first();

            if ($userData === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.user_not_found'),
                ], 404);
            }

            if ($userData->otp_date === null || $userData->otp != $request->otp) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.invalid_otp'),
                ], 200);
            }

            $timeNow = Carbon::now();
            $totalDuration = $timeNow->diffInSeconds($userData->otp_date);

            if ($totalDuration <= $this->otpExpireSeconds) {
                $userData->otp = null;
                $userData->otp_date = null;
                $userData->otp_created_date = null;
                $userData->save();
                $token = auth()->login($userData);
                $userData = $userData->with('userDetail')->where('id', $userData->id)->first();

                if ($request->input('platform') == 'WEB') {
                    $login_history = new UserLoginHistory();
                    $ip = $request->getClientIp();
                    $data = Location::get($ip);
                    $user_agent = $request->header('User-Agent');
                    $login_history->user_id = $userData->id;
                    $login_history->ip_address = $ip;
                    $login_history->platform = $request->input('platform');
                    $login_history->browser = $login_history->getBrowserDetail($user_agent);
                    $login_history->country_code = $data->countryCode;
                    $login_history->region_name = $data->regionName;
                    $login_history->city_name = $data->cityName;
                    $login_history->zip_code = $data->zipCode;
                    $login_history->latitude = $data->latitude;
                    $login_history->longitude = $data->longitude;
                    $login_history->status = Config::get('constant.LOGIN_SLUG');
                    $login_history->save();
                } elseif ($request->input('platform') == 'IOS' or $request->input('platform') == 'Android') {
                    $login_history = new UserLoginHistory();
                    $login_history->user_id = $userData->id;
                    $login_history->device_id = $request->input('device_id');
                    $login_history->platform = $request->input('platform');
                    $login_history->latitude = $request->input('latitude');
                    $login_history->longitude = $request->input('longitude');
                    $login_history->status = Config::get('constant.LOGIN_SLUG');
                    $login_history->save();
                }
                Log::info(strtr(trans('log_messages.login_success'), [
                    '<User>' => $request->mobile_number,
                ]));

                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.logged_in'),
                    'loginToken' => $token,
                    'data' => $userData,
                ], 200);
            } else {
                Log::error(strtr(trans('log_messages.login_error'), [
                    '<User>' => $request->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.invalid_otp'),
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => '0',
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * Get a OTP for forgot password/ Resend OTP for register/login
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestOtp(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required',
                'type' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $token = null;

            $userData = User::where('mobile_number', $request->mobile_number)->first();

            // If user not found with this number
            if (is_null($userData)) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.USER_NOT_FOUND_WITH_THIS_MOBILE_NUMBER'),
                ]);
            }

            // If user is admin
            if ($userData->role_id == Config::get('constant.SUPER_ADMIN_ROLE_ID')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.INVALID_ACTION_FOUND'),
                ]);
            }

            if ($request->type == 'login') {
                $message = trans('apimessages.your_otp_for_signing_in_helapay_is');
                $otp_o_type = Config::get('constant.OTP_O_LOGIN');
            } else if ($request->type == 'register') {

                if ($userData->verification_status == Config::get('constant.VERIFIED_MOBILE_STATUS')) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.user_already_verified'),
                    ], 200);
                } else if ($userData->verification_status == Config::get('constant.REJECTED_MOBILE_STATUS')) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.user_already_rejected'),
                    ], 200);
                }

                $message = trans('apimessages.your_otp_for_registering_in_helapay_is');
                $otp_o_type = Config::get('constant.OTP_O_REGISTER');
            } else if ($request->type == 'forgotpassword') {
                if ($userData->verification_status == Config::get('constant.REJECTED_MOBILE_STATUS')) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.user_already_rejected'),
                    ], 200);
                } else if ($userData->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.user_not_registered'),
                    ], 200);
                }
                $message = trans('apimessages.your_otp_to_reset_pasword_in_helapay_is');
                $otp_o_type = Config::get('constant.OTP_O_FORGOT_PASSWORD');
            } else {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.type_not_valid'),
                ], 200);
            }

            $timeNow = Carbon::now();
            $totalDuration = $timeNow->diffInSeconds($userData->otp_created_date);

            $otp = $userData->otp;

            if ($totalDuration > $this->resendOtpExpireSeconds || $otp == null) {
                $otp = mt_rand(100000, 999999);
                $userData->otp = $otp;
                $userData->otp_created_date = date('Y-m-d H:i:s');
            }

            $userData->otp_date = date('Y-m-d H:i:s');
            $userData->save();

            $sendOtp = Helpers::sendMessage($userData->mobile_number, $message . ' ' . $otp . PHP_EOL . Config::get('constant.URL_TO_SEND_IN_SMS'));

            if (!$sendOtp) {
                Log::channel('otp')->error(strtr(trans('log_messages.otp_error'), [
                    '<Mobile Number>' => $request->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.something_went_wrong'),
                ], 200);
            }

            Log::channel('otp')->info(strtr(trans('log_messages.otp_success'), [
                '<Otp>' => $otp,
                '<Mobile Number>' => $request->mobile_number,
            ]));

            $om = new OTPManagement();
            $om->otp = $otp;
            $om->otp_sent_to = $request->mobile_number;
            $om->operation = $otp_o_type;
            $om->created_by = $userData->id;
            $om->message = $message . ' ' . $otp;
            $om->save();

            // Manage response message - Start
            if (Config::get('constant.DISPLAY_OTP') == 1) {
                $responseMessage = strtr(trans('apimessages.OTP_SENT_SUCCESS_MESSAGE_FOR_DEV'), [
                    '<Mobile Number>' => $userData->mobile_number,
                    '<OTP>' => $otp,
                ]);
            } else {
                $responseMessage = strtr(trans('apimessages.OTP_SENT_SUCCESS_MESSAGE_FOR_PROD'), [
                    '<Mobile Number>' => $userData->mobile_number,
                ]);
            }

            return response()->json([
                'status' => 1,
                'message' => $responseMessage,
                'data' => [
                    'mobile_number' => $userData->mobile_number,
                ],
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

    /**
     * Get a JWT via given credentials.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyResetPasswordOtp(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required',
                'otp' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $token = null;

            $userData = User::where('mobile_number', $request->mobile_number)->first();

            if ($userData === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.user_not_found'),
                ], 404);
            }

            if ($userData->otp_date === null || $userData->otp != $request->otp) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.invalid_otp'),
                ], 200);
            }

            $timeNow = Carbon::now();
            $totalDuration = $timeNow->diffInSeconds($userData->otp_date);

            if ($totalDuration <= $this->otpExpireSeconds) {
                return response()->json([
                    'status' => '1',
                    'message' => trans('apimessages.otp_verified_successfully'),
                    'otp' => $request->otp,
                    'otp_verification' => 1,
                ], 200);
            } else {
                Log::error(strtr(trans('log_messages.login_error'), [
                    '<User>' => $request->mobile_number,
                ]));
                return response()->json([
                    'status' => '0',
                    'message' => trans('apimessages.invalid_otp'),
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => '0',
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * Reset password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        try
        {
            // Customize error message
            $messages = [
                'password.required' => 'The pin field is required.',
                'password.regex' => 'The pin must have four digits.',
            ];

            $validator = Validator::make($request->all(), [
                'mobile_number' => 'required',
                'password' => 'required|regex:/^[0-9]{4}$/',
                'otp' => 'required',
            ], $messages);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $token = null;

            $userData = User::where('mobile_number', $request->mobile_number)->first();

            if ($userData === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.user_not_found'),
                ], 404);
            }

            if ($userData->otp_date === null || $userData->otp != $request->otp) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.invalid_otp'),
                ], 200);
            }

            $timeNow = Carbon::now();
            $totalDuration = $timeNow->diffInSeconds($userData->otp_date);

            if ($totalDuration <= $this->otpExpireSeconds) {
                $userData->otp = null;
                $userData->otp_date = null;
                $userData->otp_created_date = null;
                $userData->password = $request->password;
                $userData->save();
                $token = auth()->login($userData);
                Log::info(strtr(trans('log_messages.password_change_success'), [
                    '<Email>' => $request->mobile_number,
                ]));
                return response()->json([
                    'status' => '1',
                    'message' => trans('apimessages.change_password_successfully'),
                    'loginToken' => $token,
                    'data' => $userData,
                ], 200);
            } else {
                return response()->json([
                    'status' => '0',
                    'message' => trans('apimessages.invalid_otp'),
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => '0',
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * Get a user detail using mobile number.
     *
     */
    public function changePassword(Request $request)
    {
        try
        {
            // Customize error message
            $messages = [
                'password.required' => 'The PIN field is required.',
                'password.regex' => 'The PIN must have four digits.',
                'password.confirmed' => 'The PIN confirmation does not match.',
            ];

            $validator = Validator::make($request->all(), [
                'old_password' => 'required',
                'password' => 'required|regex:/^[0-9]{4}$/|confirmed',
            ], $messages);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $user = User::find($request->user()->id);

            if (!Hash::check($request->input('old_password'), $user->password)) {
                Log::error(strtr(trans('log_messages.old_password_wrong'), [
                    '<Email>' => $request->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.wrong_old_password'),
                ], 200);
            } else {
                $password = $request->input('password');
                $user->password = $password;
                $user->save();
                Log::info(strtr(trans('log_messages.password_change_success'), [
                    '<Email>' => $request->mobile_number,
                ]));
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.change_password_successfully'),
                ], 200);
            }
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
     * Get a user detail using mobile number.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserDetail(Request $request)
    {
        try {
            $user = $request->user()->with('userDetail')
                ->with('commissionDetail')
                ->with(['loginHistory' => function ($query) {
                    $query->where('status', Config::get('constant.LOGIN_SLUG'))
                        ->orderBy('id', 'DESC')
                        ->limit(1);
                }])
                ->where('id', $request->user()->id)
                ->first();

            // Split country code from mobile number
            $splitMobileNumber = Helpers::splitMobileNumber($user->mobile_number);
            $user->country_code = $splitMobileNumber['country_code'];
            $user->mo_no = $splitMobileNumber['mo_no'];

            if (isset($user->userDetail)) {
                $user->userDetail->photo = ($user->userDetail->photo !== null && $user->userDetail->photo != '') ? url('storage/' . $this->userOriginalImagePath . $user->userDetail->photo) : url('images/default_user_profile.png');
            }

            Log::info(strtr(trans('log_messages.get_user_detail'), [
                '<User>' => $user->mobile_number,
                '<Admin>' => $request->user()->mobile_number,
            ]));

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $user,
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

    public function resendAdminLoginOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.INVALID_INPUT_DATA_FOUND'),
                ], 200);
            }

            // Verify email
            $user = User::where('email', $request->email)->first();

            // If user not found with given mail
            if (is_null($user)) {
                Log::error("User doesn't found with email {$request->email} while requesting resend OTP for admin.");
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.INVALID_INPUT_DATA_FOUND'),
                ]);
            }

            if ($user->role_id != Config::get('constant.SUPER_ADMIN_ROLE_ID')) {
                Log::error("User with email {$request->email} trying to requesting resend OTP for admin.");
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.unauthorized_access'),
                ]);
            }

            // Create if OTP not exist or send same for given minutes
            $otp = $user->otp;
            if (Carbon::now()->diffInSeconds($user->otp_created_date) > $this->resendOtpExpireSeconds || $otp == null) {
                $otp = mt_rand(100000, 999999);
                $user->update([
                    'otp' => $otp,
                    'otp_created_date' => Carbon::now(),
                ]);
            }

            $this->sendAdminLoginOTP([
                'email' => $request->email,
                'otp' => $otp,
            ]);

            // Manage response message - Start
            if (Config::get('constant.DISPLAY_OTP') == 1) {
                $responseMessage = strtr(trans('apimessages.ADMIN_LOGIN_OTP_SUCCESS_MESSAGE_DEV'), [
                    '<Email>' => $user->email,
                    '<OTP>' => $otp,
                ]);
            } else {
                $responseMessage = strtr(trans('apimessages.ADMIN_LOGIN_OTP_SUCCESS_MESSAGE_PROD'), [
                    '<Email>' => $user->email,
                ]);
            }

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => $responseMessage,
                'data' => [
                    'mobile_number' => $user->mobile_number,
                    'email' => $user->email,
                ],
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

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard();
    }
}
