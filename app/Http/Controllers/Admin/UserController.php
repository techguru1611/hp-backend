<?php

namespace App\Http\Controllers\Admin;

use App\AgentCommission;
use App\Helpers\ImageUpload;
use App\Http\Controllers\Controller;
use App\Mail\AdminLoginOTP;
use App\Mail\ForgotPassword;
use App\OTPManagement;
use App\User;
use App\UserDetail;
use App\UserLoginHistory;
use Carbon\Carbon;
use Config;
use DB;
use Hash;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use JWTAuth;
use Stevebauman\Location\Facades\Location;
use Validator;

class UserController extends Controller
{
    public function __construct(AdminLoginOTP $adminLoginOTP)
    {
        $this->objUser = new User();
        $this->objUserLoginHistory = new UserLoginHistory();
        $this->otpExpireSeconds = Config::get('constant.OTP_EXPIRE_SECONDS');
        $this->resendOtpExpireSeconds = Config::get('constant.RESEND_OTP_EXPIRE_SECONDS');
        $this->userOriginalImageUploadPath = Config::get('constant.USER_ORIGINAL_IMAGE_UPLOAD_PATH');
        $this->userThumbImageUploadPath = Config::get('constant.USER_THUMB_IMAGE_UPLOAD_PATH');
        $this->userThumbImageHeight = Config::get('constant.USER_THUMB_IMAGE_HEIGHT');
        $this->userThumbImageWidth = Config::get('constant.USER_THUMB_IMAGE_WIDTH');
        $this->kycOriginalDocumentGetUploadPath = Config::get('constant.USER_KYC_DOCUMENT_GET_UPLOAD_PATH');

        $this->adminLoginOTP = $adminLoginOTP;
    }

    /**
     * Admin login
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $token = null;

            $userData = User::where('email', $request->email)->first();

            if ($userData) {

                $credentials = $request->only('email', 'password');
                if (!$token = JWTAuth::attempt($credentials)) {
                    Log::error(strtr(trans('log_messages.invalid_login'), [
                        '<Email>' => $request->email,
                    ]));
                    return response()->json([
                        'status' => 1,
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

                if ($userData->role_id == config('constant.SUPER_ADMIN_ROLE_ID')) {
                    $otp = mt_rand(100000, 999999);
                    $userData->otp = $otp;
                    $userData->otp_date = date('Y-m-d H:i:s');
                    $userData->otp_created_date = date('Y-m-d H:i:s');
                    $userData->save();

                    // Send login OTP to admin
                    $this->sendAdminLoginOTP([
                        'email' => $request->email,
                        'otp' => $otp,
                    ]);
                    Log::info(strtr(trans('log_messages.login'), [
                        '<User>' => 'Admin',
                        '<Email>' => $userData->email,
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
                        $responseMessage = strtr(trans('apimessages.ADMIN_LOGIN_OTP_SUCCESS_MESSAGE_DEV'), [
                            '<Email>' => $userData->email,
                            '<OTP>' => $otp,
                        ]);
                    } else {
                        $responseMessage = strtr(trans('apimessages.ADMIN_LOGIN_OTP_SUCCESS_MESSAGE_PROD'), [
                            '<Email>' => $userData->email,
                        ]);
                    }

                    return response()->json([
                        'status' => 1,
                        'message' => $responseMessage,
                        'data' => [
                            'mobile_number' => $userData->mobile_number,
                            'email' => $userData->email,
                        ],
                    ], 200);
                } else if ($userData->role_id == config('constant.COMPLIENCE_ROLE_ID')) {
                    $token = auth()->login($userData);
                    $userData = $userData->where('id', $userData->id)->first();
                    $userData->update([
                        'last_activity_at' => Carbon::now(),
                    ]);

                    // Split country code from mobile number
                    $splitMobileNumber = Helpers::splitMobileNumber($userData->mobile_number);
                    $userData->country_code = $splitMobileNumber['country_code'];
                    $userData->mo_no = $splitMobileNumber['mo_no'];

                    // Get data from login request
                    if ($request->input('platform') == Config::get('constant.WEB_PLATFORM')) { // If request from WEB
                        // Get IP Address
                        $ip = $request->getClientIp();
                        // Get Location data from IP Address
                        $data = Location::get($ip);
                        // Get user agent from header to get browser detail
                        $user_agent = $request->header('User-Agent');

                        // Save login History
                        $loginHistory = new UserLoginHistory([
                            'ip_address' => $ip,
                            'platform' => $request->input('platform'),
                            'browser' => $this->objUserLoginHistory->getBrowserDetail($user_agent),
                            'country_code' => $data->countryCode,
                            'region_name' => $data->regionName,
                            'city_name' => $data->cityName,
                            'zip_code' => $data->zipCode,
                            'latitude' => $data->latitude,
                            'longitude' => $data->longitude,
                            'status' => Config::get('constant.LOGIN_SLUG'),
                        ]);
                        $request->user()->loginHistory()->save($loginHistory);

                    } elseif ($request->input('platform') == Config::get('constant.IOS_PLATFORM') or $request->input('platform') == Config::get('constant.ANDROID_PLATFORM')) { // If request from mobile app
                        // Save login History
                        $loginHistory = new UserLoginHistory([
                            'device_id' => $request->input('device_id'),
                            'platform' => $request->input('platform'),
                            'latitude' => $request->input('latitude'),
                            'longitude' => $request->input('longitude'),
                            'status' => Config::get('constant.LOGIN_SLUG'),
                        ]);
                        $request->user()->loginHistory()->save($loginHistory);
                    }

                    Log::info(strtr(trans('log_messages.login'), [
                        '<User>' => 'Compliance',
                        '<Email>' => $userData->email,
                    ]));

                    return response()->json([
                        'status' => 1,
                        'message' => trans('apimessages.logged_in'),
                        'loginToken' => $token,
                        'data' => $userData,
                    ], 200);
                } else {
                    Log::error(strtr(trans('log_messages.role_not_match'), [
                        '<Email>' => $request->email,
                    ]));
                    return response()->json([
                        'status' => '0',
                        'message' => trans('apimessages.admin_role_not_match'),
                    ], 200);
                }

            } else {
                Log::error(strtr(trans('log_messages.invalid_login'), [
                    '<Email>' => $request->email,
                ]));
                return response()->json([
                    'status' => '0',
                    'message' => trans('apimessages.invalid_login'),
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.error_admin_login'),
            ], 500);
        }
    }

    public function verifyLoginOtp(Request $request)
    {
        try {
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
                $userData->save();

                $token = auth()->login($userData);
                $userData = $userData->where('id', $userData->id)->first();
                $userData->update([
                    'last_activity_at' => Carbon::now(),
                ]);

                // Split country code from mobile number
                $splitMobileNumber = Helpers::splitMobileNumber($userData->mobile_number);
                $userData->country_code = $splitMobileNumber['country_code'];
                $userData->mo_no = $splitMobileNumber['mo_no'];

                $ip = $request->getClientIp();
                $data = Location::get($ip);
                $user_agent = $request->header('User-Agent');

                $login_history = new UserLoginHistory();
                $login_history->user_id = $userData->id;
                $login_history->ip_address = $ip;
                $login_history->platform = $login_history->getPlatformDetail($user_agent);
                $login_history->browser = $login_history->getBrowserDetail($user_agent);
                $login_history->country_code = $data->countryCode;
                $login_history->region_name = $data->regionName;
                $login_history->city_name = $data->cityName;
                $login_history->zip_code = $data->zipCode;
                $login_history->latitude = $data->latitude;
                $login_history->longitude = $data->longitude;
                $login_history->status = 1;
                $login_history->save();

                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.logged_in'),
                    'loginToken' => $token,
                    'data' => $userData,
                ], 200);
            } else {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.otp_expired'),
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
     * Get a register user listing.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserList(Request $request)
    {
        try {

            $userData = $this->objUser->with('userDetail')->with('commissionDetail');

            // Get all user data including login user
            if (!isset($request->type) || (isset($request->type) && $request->type != Config::get('constant.ALL_USER_DETAIL_SLUG'))) {
                $userData = $userData->where('id', '<>', $request->user()->id);
            }

            $userData = $userData->get();

            //Log Message
            Log::info(strtr(trans('log_messages.user_list_success'), [
                '<User Email>' => $request->user()->email,
            ]));

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $userData,
            ], 200);
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

    public function getAgentList(Request $request)
    {
        try {
            $userData = $this->objUser->with('userDetail')->with('commissionDetail')->where('id', '<>', $request->user()->id)->where('role_id', config('constant.AGENT_ROLE_ID'))->get();
            if ($userData) {
                Log::info(strtr(trans('log_messages.agent_list_success'), [
                    '<User Email>' => $request->user()->email,
                ]));
                return response()->json([
                    'status' => '1',
                    'message' => trans('apimessages.default_success_msg'),
                    'data' => $userData,
                ], 200);
            } else {
                return response()->json([
                    'status' => '1',
                    'message' => trans('apimessages.empty_data_msg'),
                ], 200);
            }
        } catch (JWTAuthException $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => '0',
                'message' => trans('apimessages.error_registering_user'),
                'code' => $e->getStatusCode(),
            ]);
        }
    }

    /**
     * Add or Update user.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addOrUpdateUser(Request $request)
    {
        try {
            $rule = [
                'full_name' => 'required',
                'email' => 'nullable|email|unique:users,email,' . $request->id,
                'mobile_number' => 'required|min:10|max:17|regex:/^\+?\d+$/|unique:users,mobile_number,' . $request->id,
                'verification_status' => ['required', Rule::in([Config::get('constant.PENDING_MOBILE_STATUS'), Config::get('constant.VERIFIED_MOBILE_STATUS'), Config::get('constant.REJECTED_MOBILE_STATUS')])],
                'user_type' => ['required', Rule::in([Config::get('constant.USER_ROLE_ID'), Config::get('constant.AGENT_ROLE_ID'), Config::get('constant.COMPLIANCE_ROLE_ID')])],
                'dob' => 'nullable|date_format:"Y-m-d"',
                'gender' => ['nullable', Rule::in([Config::get('constant.MALE'), Config::get('constant.FEMALE'), Config::get('constant.NON_BINARY')])],
            ];

            if (!isset($request->id) || (isset($request->id) && ($request->id == 0 || empty($request->id)))) {
                // If role is complience than password must have atleast 8 character
                if ($request->user_type == Config::get('constant.COMPLIANCE_ROLE_ID')) {
                    $rule['password'] = 'required|min:8|max:20|confirmed';
                } else { // If role is not complience than password must have 4 digit
                    $rule['password'] = 'required|regex:/^[0-9]{4}$/|confirmed';
                }
            } else {
                if ($request->user_type == Config::get('constant.COMPLIANCE_ROLE_ID')) {
                    if (isset($request->password) && !empty($request->password)) {
                        $rule['password'] = 'min:8|max:20';
                    }
                } else { // If role is not complience than password must have 4 digit
                    if (isset($request->password) && !empty($request->password)) {
                        $rule['password'] = 'regex:/^[0-9]{4}$/';
                    }
                }
            }
            // If role is complience than email is required
            if ($request->user_type == Config::get('constant.COMPLIANCE_ROLE_ID')) {
                $rule['email'] = 'required|email|unique:users,email,' . $request->id;
            }

            if ($request->user_type == Config::get('constant.AGENT_ROLE_ID')) {
                $rule['latitude'] = ['required', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'];
                $rule['longitude'] = ['required', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'];
            }

            // Customize error message
            $messages = [
                'password.required' => 'The pin field is required.',
                'password.regex' => 'The pin must have four digits.',
                'password.confirmed' => 'The pin confirmation does not match.',
            ];

            // If role is complience than don't require to apply custom validation message
            if ($request->user_type == Config::get('constant.COMPLIANCE_ROLE_ID')) {
                $validator = Validator::make($request->all(), $rule);
            } else {
                // If role is not complience than require to apply custom validation message
                $validator = Validator::make($request->all(), $rule, $messages);
            }

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            if (isset($request->email) && $request->email !== null) {
                if (!Helpers::validateEmail($request->email)) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.INVALID_EMAIL_ERROR'),
                    ], 200);
                }
            }

            // If user is an agent then select agent commission
            if ($request->user_type == Config::get('constant.AGENT_ROLE_ID')) {
                if (!isset($request->default) && !isset($request->agent_commission)) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.AGENT_COMMISSION_DATA_REQUIRED'),
                    ], 200);
                }
            }

            $postData = $request->only('full_name', 'email', 'mobile_number', 'verification_status', 'id', 'language', 'nationality', 'dob');
            $postData['role_id'] = $request->user_type;

            if (!isset($request->gender) && $request->gender == null) {
                $postData['gender'] = Config::get('constant.MALE');
            } else {
                $postData['gender'] = $request->gender;
            }

            if (isset($request->password) && $request->password != null) {
                $postData['password'] = $request->password;
            }

            if ($request->user_type == Config::get('constant.AGENT_ROLE_ID')) {
                $postData['latitude'] = $request->latitude;
                $postData['longitude'] = $request->longitude;
                $postData['address'] = (isset($request->address) && !empty($request->address)) ? $request->address : null;
                $postData['street_address'] = (isset($request->street_address) && !empty($request->street_address)) ? $request->street_address : null;
                $postData['locality'] = (isset($request->locality) && !empty($request->locality)) ? $request->locality : null;
                $postData['country'] = (isset($request->country) && !empty($request->country)) ? $request->country : null;
                $postData['state'] = (isset($request->state) && !empty($request->state)) ? $request->state : null;
                $postData['city'] = (isset($request->city) && !empty($request->city)) ? $request->city : null;
                $postData['zip_code'] = (isset($request->zip_code) && !empty($request->zip_code)) ? $request->zip_code : null;
            }

            if (isset($request->id) && $request->id > 0) {
                $userDetail = $this->objUser->find($request->id);
                // If trying to update super admin detail
                if ($userDetail->role_id == Config::get('constant.SUPER_ADMIN_ROLE_ID')) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.unauthorized_access'),
                    ], 401);
                }
                // If update verification status
                if ($userDetail->verification_status != $request->verification_status) {
                    $postData['verification_status_updated_by'] = $request->user()->id;
                    $postData['verification_status_updater_role'] = Config::get('constant.SUPER_ADMIN_SLUG');
                }
            }

            DB::beginTransaction();
            $user = $this->objUser->insertUpdate($postData);
            if ($user) {
                if (!isset($request->id) || $request->id == 0 || empty($request->id)) {
                    // Save user detail
                    $userData = new UserDetail([
                        'balance_amount' => 0.00,
                        'country_code' => Config::get('constant.DEFAULT_COUNTRY'),
                        'nationality' => isset($request->nationality) ? $request->nationality : '',
                        'dob' => isset($request->dob) ? $request->dob : null,
                    ]);
                    $user->userDetail()->save($userData);
                    // Sent welcome message to user
                    $sendMessage = Helpers::sendMessage($request->mobile_number, trans('apimessages.welcome_to_helapay_message'));
                    //Log Message
                    Log::info(strtr(trans('log_messages.user_add_success'), [
                        '<User>' => $request->email,
                        '<Admin>' => $request->user()->email,
                    ]));
                } else {
                    // Update user detail
                    $user->userDetail()->update([
                        'nationality' => isset($request->nationality) ? $request->nationality : '',
                        'dob' => isset($request->dob) ? $request->dob : null,
                    ]);
                    //Log Message
                    Log::info(strtr(trans('log_messages.user_update_success'), [
                        '<User>' => $user->email !== null ? $user->email : $user->mobile_number,
                        '<Admin>' => $request->user()->email,
                    ]));
                }

                // Get agent custom commission data
                $agentCommission = $user->commissionDetail()->first();
                // Save agent commission
                if (isset($request->agent_commission) && !empty($request->agent_commission) && $request->default == Config::get('constant.NOT_DEFAULT_COMMISSION_FLAG')) {
                    // Insert agent commission
                    if ($agentCommission === null) {
                        // Save agent commission detail
                        $agentCommission = new AgentCommission([
                            'commission' => $request->agent_commission,
                        ]);
                        $user->commissionDetail()->save($agentCommission);
                        Log::info(strtr(trans('log_messages.add_agent_commission'), [
                            '<User>' => $user->email !== null ? $user->email : $user->mobile_number,
                            '<Admin>' => $request->user()->email,
                        ]));
                    } else {
                        // Update agent commission detail
                        $agentCommission->update([
                            'commission' => $request->agent_commission,
                        ]);
                        Log::info(strtr(trans('log_messages.update_agent_commission'), [
                            '<User>' => $user->email ? $user->email : $user->mobile_number,
                            '<Admin>' => $request->user()->email,
                        ]));
                    }
                } else {
                    if ($agentCommission !== null) {
                        // If custom commission set than delete
                        $agentCommission->delete();
                    }
                }
                $msg = (isset($request->id) && $request->id > 0) ? trans('apimessages.user_updated_suceessfully') : trans('apimessages.user_added_suceessfully');
                DB::commit();
                $user = Helpers::convertNullToEmptyString($user->toArray());

                return response()->json([
                    'status' => 1,
                    'message' => $msg,
                    'data' => [
                        'userDetail' => $user,
                    ],
                ], 200);
            } else {
                $errorMsg = (isset($request->id) && $request->id > 0) ? trans('apimessages.error_updating_user') : trans('apimessages.error_adding_user');
                DB::rollback();
                Log::error(strtr(trans('log_messages.error_update_user'), [
                    '<User>' => $user->email !== null ? $user->email : $user->mobile_number,
                    '<Admin>' => $request->user()->email,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => $errorMsg,
                ], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
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
     * Delete user.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyUser(Request $request, $id)
    {
        $user = $this->objUser->with('userDetail')->where('id', $id)->first();

        try {
            if ($user === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.user_not_found'),
                ], 404);
            } else if ($user->role_id == Config::get('constant.SUPER_ADMIN_ROLE_ID')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.unauthorized_access'),
                ], 401);
            } else {
                if ($user->userDetail->balance_amount != 0) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.can_not_delete_user_with_balance'),
                    ]);
                }
                $user->delete();
                Log::info(strtr(trans('log_messages.user_delete_success'), [
                    '<User>' => $user->email !== null ? $user->email : $user->mobile_number,
                    '<Admin>' => $request->user()->email,
                ]));
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.user_deleted_successfully'),
                ]);
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
     * Change user role from user to agent.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function makeAgent(Request $request)
    {
        $id = (isset($request->id) && !empty($request->id) && $request->id > 0) ? $request->id : 0;
        $user = $this->objUser->where('id', $id)->first();
        try {
            if ($user === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.user_not_found'),
                ], 404);
            } else if ($user->role_id != Config::get('constant.USER_ROLE_ID')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.unauthorized_access'),
                ], 401);
            } else {
                $user->fill(array_filter([
                    'role_id' => Config::get('constant.AGENT_ROLE_ID'),
                ]));
                $user->save();
                Log::info(strtr(trans('log_messages.make_agent_success'), [
                    '<User>' => $user->email !== null ? $user->email : $user->mobile_number,
                    '<Admin>' => $request->user()->email,
                ]));
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.user_role_changed_successfully'),
                    'data' => [
                        'userDetail' => $user,
                    ],
                ]);
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
     * Expire an authorization token when user log out from App / Admin
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        if ($request->has('token') && !empty($request->token)) {
            $userData = $request->user();
            if (JWTAuth::invalidate($request->token)) {

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
                    $login_history->status = Config::get('constant.LOGOUT_SLUG');
                    $login_history->save();
                } elseif ($request->input('platform') == 'IOS' or $request->input('platform') == 'Android') {
                    $login_history = new UserLoginHistory();
                    $login_history->user_id = $userData->id;
                    $login_history->device_id = $request->input('device_id');
                    $login_history->platform = $request->input('platform');
                    $login_history->latitude = $request->input('latitude');
                    $login_history->longitude = $request->input('longitude');
                    $login_history->status = Config::get('constant.LOGOUT_SLUG');
                    $login_history->save();
                }

                Log::info(strtr(trans('log_messages.logout_success'), [
                    '<User Email>' => $userData->email !== null ? $userData->email : $userData->mobile_number,
                ]));

                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.logout_success'),
                ], 200);
            } else {
                Log::info(trans('log_messages.logout_error'));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.default_error_msg'),
                ], 500);
            }
        } else {
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.token_not_found'),
            ], 404);
        }
    }

    /**
     * Forgot Password / Admin
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        DB::beginTransaction();
        try {
            $rule = [
                'email' => 'required|email',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $findUser = User::where('email', $request->input('email'))
                ->where('role_id', 1)
                ->first();

            if ($findUser) {
                $forgotPasswordToken = str_random(32);
                $resetLink = url('api/admin/resetPassword') . '/' . $forgotPasswordToken;
                $findUser->forgot_password_token = $forgotPasswordToken;
                $findUser->save();

                Mail::to($request->input('email'))
                    ->send(new ForgotPassword(['resetLink' => $resetLink, 'email' => $request->input('email')]));

                DB::commit();
                Log::info(strtr(trans('log_messages.reset_token_sent'), [
                    '<Email>' => $request->email,
                ]));
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.admin_reset_password_token_sent'),
                ], 200);
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.user_not_found'),
                ], 404);
            }

        } catch (\Exception $e) {
            DB::rollBack();
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
     * Reset Password / Admin
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request, $resetToken)
    {
        DB::beginTransaction();
        try {
            $rule = [
                'newPassword' => 'required',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $findUserWithResetToken = User::where('forgot_password_token', $resetToken)->first();
            if ($findUserWithResetToken) {
                $findUserWithResetToken->password = $request->input('newPassword');
                $findUserWithResetToken->forgot_password_token = null;
                $findUserWithResetToken->save();
                DB::commit();
                Log::info(strtr(trans('log_messages.password_reset_success'), [
                    '<Email>' => $findUserWithResetToken->email,
                ]));
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.admin_password_reset_success'),
                ], 200);
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.empty_data_msg'),
                ], 404);
            }

        } catch (\Exception $e) {
            DB::rollBack();
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
     * Update profile.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        try {
            $rule = [
                'full_name' => 'required|max:100',
                'mobile_number' => 'required|min:10|max:17|regex:/^\+?\d+$/|unique:users,mobile_number,' . $request->user()->id,
                'dob' => 'nullable|date_format:"Y-m-d"',
                'gender' => ['required', Rule::in([Config::get('constant.MALE'), Config::get('constant.FEMALE'), Config::get('constant.NON_BINARY')])],
            ];

            if ($request->user()->role_id == Config::get('constant.SUPER_ADMIN_ROLE_ID') || (isset($request->email) && !empty($request->email))) {
                $rule['email'] = 'required|email|max:100|unique:users,email,' . $request->user()->id;
            }

            if ($request->user()->role_id == Config::get('constant.AGENT_ROLE_ID') && isset($request->longitude) && isset($request->latitude)) {
                $rule['latitude'] = ['nullable', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'];
                $rule['longitude'] = ['nullable', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'];
            }

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            if (isset($request->email) && $request->email !== null) {
                if (!Helpers::validateEmail($request->email)) {
                    Log::info(strtr(trans('log_messages.INVALID_EMAIL_ERROR'), [
                        '<User Email>' => $request->email,
                    ]));
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.INVALID_EMAIL_ERROR'),
                    ], 200);
                }
            }

            DB::beginTransaction();
            $userData = $request->only('full_name', 'mobile_number', 'language', 'gender');
            $userData['email'] = (isset($request->email) && !empty($request->email)) ? $request->email : null;
            $userData['id'] = $request->user()->id;
            if ($request->user()->role_id == Config::get('constant.AGENT_ROLE_ID')) {
                $userData['latitude'] = (isset($request->latitude) ? $request->latitude : null);
                $userData['longitude'] = (isset($request->longitude) ? $request->longitude : null);
                $userData['address'] = (isset($request->address) && !empty($request->address)) ? $request->address : null;
                $userData['street_address'] = (isset($request->street_address) && !empty($request->street_address)) ? $request->street_address : null;
                $userData['locality'] = (isset($request->locality) && !empty($request->locality)) ? $request->locality : null;
                $userData['country'] = (isset($request->country) && !empty($request->country)) ? $request->country : null;
                $userData['state'] = (isset($request->state) && !empty($request->state)) ? $request->state : null;
                $userData['city'] = (isset($request->city) && !empty($request->city)) ? $request->city : null;
                $userData['zip_code'] = (isset($request->zip_code) && !empty($request->zip_code)) ? $request->zip_code : null;
            }
            // Update user data
            $this->objUser->insertUpdate($userData);

            // Get user detail
            $userDetail = $request->user()->userDetail()->first();

            $previousImage = null;
            if ($userDetail !== null) {
                $previousImage = $userDetail->photo;

                // Update user detail
                $userDetail->update([
                    'nationality' => isset($request->nationality) ? $request->nationality : '',
                    'dob' => isset($request->dob) ? $request->dob : null,
                ]);
            }
            // upload user profile picture
            if (!empty($request->file('photo')) && $request->file('photo')->isValid()) {
                /**
                 * @dev notes:
                 * originalPath & thumbPath these two path MUST be start with public folder otherwise file will not saved.
                 */
                $params = [
                    // 'originalPath' => public_path($this->userOriginalImageUploadPath),
                    // 'thumbPath' => public_path($this->userThumbImageUploadPath),
                    'originalPath' => 'public/' . ($this->userOriginalImageUploadPath),
                    'thumbPath' => 'public/' . ($this->userThumbImageUploadPath),
                    'thumbHeight' => $this->userThumbImageHeight,
                    'thumbWidth' => $this->userThumbImageWidth,
                    'previousImage' => $previousImage,
                ];

                // $userPhoto = ImageUpload::uploadWithThumbImage($request->file('photo'), $params);
                $userPhoto = ImageUpload::storageUploadWithThumbImage($request->file('photo'), $params);
                if ($userPhoto === false) {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.image_upload_error'),
                    ], 200);
                }
                // Update user detail
                $userDetail->update([
                    'photo' => $userPhoto['imageName'],
                ]);
            }
            DB::commit();

            $user = $request->user()->with('userDetail')->where('id', $userData['id'])->first();

            // Split country code from mobile number
            $splitMobileNumber = Helpers::splitMobileNumber($user->mobile_number);
            $user->country_code = $splitMobileNumber['country_code'];
            $user->mo_no = $splitMobileNumber['mo_no'];

            if (isset($user->userDetail)) {
                /**
                 * When retriving files, we must needs to specify STORAGE folder. Because it's create symboli link to public folder.
                 */
                $user->userDetail->photo = ($user->userDetail->photo !== null && $user->userDetail->photo != '') ? url('storage/' . $this->userOriginalImageUploadPath . $user->userDetail->photo) : url('images/default_user_profile.png');
            }

            // Converting NULL to EMPTY STRING
            $user = Helpers::convertNullToEmptyString($user->toArray());

            //Log Message
            Log::info(strtr(trans('log_messages.update_profile_success'), [
                '<User Email>' => $request->user()->email,
            ]));

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.profile_updated_successfully'),
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.ERROR_UPDATING_PROFILE'),
            ], 500);
        }
    }

    /**
     * To change admin password.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'old_password' => 'required',
                'password' => 'required|min:8|max:20|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $user = User::find($request->user()->id);

            if (!Hash::check($request->input('old_password'), $user->password)) {
                Log::info(strtr(trans('log_messages.old_password_wrong'), [
                    '<Email>' => $user->email !== null ? $user->email : $user->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.WRONG_OLD_PASSWORD'),
                ], 200);
            } else {
                $password = $request->input('password');
                $user->password = $password;
                $user->save();
                Log::info(strtr(trans('log_messages.password_change_success'), [
                    '<Email>' => $user->email !== null ? $user->email : $user->mobile_number,
                ]));
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.PASSWORD_CHANGE_SUCCESS_MESSAGE'),
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.ERROR_WHILE_CHANGE_PASSWORD'),
            ], 500);
        }
    }

    public function makeAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|regex:/^[1-9][0-9]{0,15}$/',
                'email' => 'required|email|unique:users,email,' . $request->id,
                'password' => 'required|min:8|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $id = (isset($request->id) && !empty($request->id) && $request->id > 0) ? $request->id : 0;
            $user = $this->objUser->where('id', $id)->first();

            if ($user === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.user_not_found'),
                ], 404);
            } else if ($user->role_id == Config::get('constant.SUPER_ADMIN_ROLE_ID')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.ALREADY_HAVE_SUPER_ADMIN_ROLE'),
                ], 200);
            } else {
                $user->fill(array_filter([
                    'role_id' => Config::get('constant.SUPER_ADMIN_ROLE_ID'),
                    'email' => $request->email,
                    'password' => $request->password,
                ]));
                $user->save();
                Log::info(strtr(trans('log_messages.make_admin_success'), [
                    '<User>' => $user->email !== null ? $user->email : $user->mobile_number,
                    '<Admin>' => $request->user()->email,
                ]));
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.user_role_changed_successfully'),
                    'data' => [
                        'userDetail' => $user,
                    ],
                ]);
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
            $rule = [
                'user_id' => 'required',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all(),
                ], 200);
            }

            $userId = $request->user_id;
            $user = $request->user()->with('userDetail')
                ->with('commissionDetail')
                ->with(['loginHistory' => function ($query) {
                    $query->where('status', Config::get('constant.LOGIN_SLUG'))
                        ->orderBy('id', 'DESC')
                        ->limit(1);
                }])
                ->with('kycDocument')
                ->where('id', $userId)
                ->first();

            // Split country code from mobile number
            $splitMobileNumber = Helpers::splitMobileNumber($user->mobile_number);
            $user->country_code = $splitMobileNumber['country_code'];
            $user->mo_no = $splitMobileNumber['mo_no'];

            Log::info(strtr(trans('log_messages.get_user_detail'), [
                '<User>' => $user->email !== null ? $user->email : $user->mobile_number,
                '<Admin>' => $request->user()->email,
            ]));
            $user = Helpers::convertNullToEmptyString($user->toArray());

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'profile_pic_base_path' => url('storage/' . $this->userOriginalImageUploadPath) . '/',
                'kyc_document_base_path' => url($this->kycOriginalDocumentGetUploadPath) . '/',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'error' => $e->getMessage() . ' = ' . $e->getTraceAsString(),
            ], 500);
        }
    }
}
