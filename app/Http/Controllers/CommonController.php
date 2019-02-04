<?php

namespace App\Http\Controllers;

use App\AgentAddOrWithdrawMoneyRequest;
use App\CashInOrOutMoneyRequest;
use App\OTPManagement;
use App\Settings;
use App\TransferMoneyRequest;
use App\EvoucherRequest;
use App\User;
use Carbon\Carbon;
use Config;
use DB;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Validator;
use App\TrangloCommonCode;

class CommonController extends Controller
{
    public function __construct()
    {
        $this->otpExpireSeconds = Config::get('constant.OTP_EXPIRE_SECONDS');
        $this->resendOtpExpireSeconds = Config::get('constant.RESEND_OTP_EXPIRE_SECONDS');
        $this->userOriginalImagePath = Config::get('constant.USER_ORIGINAL_IMAGE_UPLOAD_PATH');
        $this->settingLogoOriginalImageUploadPath = Config::get('constant.SETTING_LOGO_ORIGINAL_IMAGE_UPLOAD_PATH');
    }

    /**
     *
     * To resend OTP
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendOTP(Request $request)
    {
        try
        {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'request_id' => 'required|integer|min:1',
                'type' => ['required', Rule::in([Config::get('constant.ADD_MONEY_ACTION'), Config::get('constant.WITHDRAW_MONEY_ACTION'), Config::get('constant.CASH_IN_ACTION'), Config::get('constant.CASH_OUT_ACTION'), Config::get('constant.TRANSFER_MONEY_ACTION'), Config::get('constant.E-VOUCHER_ACTION')])],
            ]);

            if ($validator->fails()) {
                DB::rollback();
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            if ($request->type == Config::get('constant.ADD_MONEY_ACTION') || $request->type == Config::get('constant.WITHDRAW_MONEY_ACTION')) {

                $addWithdrawRequest = AgentAddOrWithdrawMoneyRequest::find($request->request_id);

                // Invalid request id found
                if ($addWithdrawRequest === null) {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.empty_data_msg'),
                    ], 200);
                }

                // Invalid type found for request
                if ($addWithdrawRequest->action != $request->type) {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.invalid_action_found'),
                    ], 200);
                }

                // Resend OTP
                $secondsSinceAddOrWithdrawMoneyRequestOtpCreated = Carbon::now()->diffInSeconds($addWithdrawRequest->otp_created_at);

                $otp = $addWithdrawRequest->otp;
                $mobileNumber = $addWithdrawRequest->otp_sent_to;
                if ($secondsSinceAddOrWithdrawMoneyRequestOtpCreated > $this->resendOtpExpireSeconds || $otp == null) {
                    $otp = mt_rand(100000, 999999);
                    $addWithdrawRequest->otp = $otp;
                    $addWithdrawRequest->otp_created_at = Carbon::now();
                    $addWithdrawRequest->save();
                }

                // Re-send OTP message
                $message = ($request->type == Config::get('constant.ADD_MONEY_ACTION')) ? trans('apimessages.ADD_MONEY_OTP_MESSAGE_TO_AGENT') : trans('apimessages.WITHDRAW_MONEY_OTP_MESSAGE_TO_AGENT');
                $message = strtr($message, [
                    '<OTP>' => $otp,
                    '<Value>' => $addWithdrawRequest->amount,
                ]);
                $otp_o_type = ($request->type == Config::get('constant.ADD_MONEY_ACTION')) ? Config::get('constant.OTP_O_ADD_MONEY_VERIFICATION') : Config::get('constant.OTP_O_WITHDRAW_MONEY_VERIFICATION');
            } else if ($request->type == Config::get('constant.TRANSFER_MONEY_ACTION')) {
                $transferMoneyRequest = TransferMoneyRequest::find($request->request_id);

                // Invalid request id found
                if ($transferMoneyRequest === null || ($transferMoneyRequest !== null && $transferMoneyRequest->unregistered_number !== null)) {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.empty_data_msg'),
                    ], 200);
                }
                // Resend OTP
                $secondsSinceTransferMoneyRequestOtpCreated = Carbon::now()->diffInSeconds($transferMoneyRequest->otp_created_at);

                $otp = $transferMoneyRequest->otp;
                $mobileNumber = $transferMoneyRequest->otp_sent_to;
                if ($secondsSinceTransferMoneyRequestOtpCreated > $this->resendOtpExpireSeconds || $otp == null) {
                    $otp = mt_rand(100000, 999999);
                    $transferMoneyRequest->otp = $otp;
                    $transferMoneyRequest->otp_created_at = Carbon::now();
                    $transferMoneyRequest->save();
                }

                // Re-send OTP message
                $message = strtr(trans('apimessages.OTP_MESSAGE_TO_SENDER_TO_TRANSFER_MONEY'), [
                    '<OTP>' => $otp,
                    '<Value>' => $transferMoneyRequest->amount,
                    '<Receiver Name>' => $transferMoneyRequest->toUser->full_name,
                    '<Receiver Mobile Number>' => Helpers::maskString($transferMoneyRequest->toUser->mobile_number),
                ]);
                $otp_o_type = Config::get('constant.OTP_O_TRANSFER_MONEY_VERIFICATION');
            } else if ($request->type == Config::get('constant.E-VOUCHER_ACTION')) {
                $evoucherRequest = EvoucherRequest::find($request->request_id);

                // Invalid request id found
                if ($evoucherRequest === null) {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.empty_data_msg'),
                    ], 200);
                }
                // Resend OTP
                $secondsSinceEvoucherRequestOtpCreated = Carbon::now()->diffInSeconds($evoucherRequest->otp_created_at);

                $otp = $evoucherRequest->otp;
                $mobileNumber = $evoucherRequest->otp_sent_to;
                if ($secondsSinceEvoucherRequestOtpCreated > $this->resendOtpExpireSeconds || $otp == null) {
                    $otp = mt_rand(100000, 999999);
                    $evoucherRequest->otp = $otp;
                    $evoucherRequest->otp_created_at = Carbon::now();
                    $evoucherRequest->save();
                }

                // Re-send OTP message
                $message = strtr(trans('apimessages.EVOUCHER_OTP_MESSAGE_TO_SENDER'), [
                    '<OTP>' => $otp,
                    '<Value>' => $evoucherRequest->amount,
                    '<Receiver Mobile Number>' => ($evoucherRequest->to_user_id === null) ? Helpers::maskString($evoucherRequest->fromUser->mobile_number) : Helpers::maskString($evoucherRequest->toUser->mobile_number),
                ]);
                $otp_o_type = Config::get('constant.OTP_O_E_VOUCHER_SENT_VERIFICATION');
            } else {
                $cashInOutRequest = CashInOrOutMoneyRequest::find($request->request_id);

                // Invalid request id found
                if ($cashInOutRequest === null) {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.empty_data_msg'),
                    ], 200);
                }
                // Invalid type found for request
                if ($cashInOutRequest->action != $request->type) {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.invalid_action_found'),
                    ], 200);
                }
                // Resend OTP
                $secondsSinceCashInOutMoneyRequestOtpCreated = Carbon::now()->diffInSeconds($cashInOutRequest->otp_created_at);

                $otp = $cashInOutRequest->otp;
                $mobileNumber = $cashInOutRequest->otp_sent_to;
                if ($secondsSinceCashInOutMoneyRequestOtpCreated > $this->resendOtpExpireSeconds || $otp == null) {
                    $otp = mt_rand(100000, 999999);
                    $cashInOutRequest->otp = $otp;
                    $cashInOutRequest->otp_created_at = Carbon::now();
                    $cashInOutRequest->save();
                }

                // Re-send OTP message
                $message = ($request->type == Config::get('constant.CASH_IN_ACTION')) ? trans('apimessages.CASHIN_RESEND_OTP_MESSAGE_TO_AGENT') : trans('apimessages.CASHOUT_RESEND_OTP_MESSAGE_TO_USER');
                $message = strtr($message, [
                    '<OTP>' => $otp,
                    '<Value>' => $cashInOutRequest->amount,
                ]);
                $otp_o_type = ($request->type == Config::get('constant.CASH_IN_ACTION')) ? Config::get('constant.OTP_O_CASH_IN_VERIFICATION') : Config::get('constant.OTP_O_CASH_OUT_VERIFICATION');
            }

            $sendOtp = Helpers::sendMessage($mobileNumber, $message);
            if (!$sendOtp) {
                Log::channel('otp')->error(strtr(trans('log_messages.otp_error_action'),[
                    '<Mobile Number>' => $request->user()->mobile_number,
                    '<Action>' => $request->type
                ]));
                DB::rollback();
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.default_error_msg'),
                ], 200);
            }

            Log::channel('otp')->info(strtr(trans('log_messages.otp_success_action'),[
                '<Mobile Number>' => $mobileNumber,
                '<Action>' => $request->type
            ]));

            $om = new OTPManagement();
            $om->otp = $otp;
            $om->otp_sent_to = $mobileNumber;
            $om->operation = $otp_o_type;
            $om->message = $message;
            $om->save();


            // Manage response message - Start
            if (Config::get('constant.DISPLAY_OTP') == 1) {
                $responseMessage = strtr(trans('apimessages.OTP_SENT_SUCCESS_MESSAGE_FOR_DEV'),[
                    '<Mobile Number>' => $mobileNumber,
                    '<OTP>' => $otp
                ]);
            } else {
                $responseMessage = strtr(trans('apimessages.OTP_SENT_SUCCESS_MESSAGE_FOR_PROD'),[
                    '<Mobile Number>' => $mobileNumber,
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 1,
                'message' => $responseMessage,
                'data' => [],
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }
    
    /**
     * Retrive list of all uploaded documents for requested user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getDocumentTypeList(Request $request) {
        return response()->json([
            'status' => 1,
            'message' => trans('apimessages.default_success_msg'),
            'data' =>  Config::get('constant.DOCUMENT_TYPE_ARRAY')
        ], 200);
    }

    /**
     * To check mobile app vesion and force update status
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkAppVersion (Request $request) {
        try {
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'iOSAppVersion' => Config::get('constant.IOS_APP_VERSION'),
                    'iOSForceUpdate' => Config::get('constant.IOS_FORCE_UPDATE'),
                    'androidAppVersion' => Config::get('constant.ANDROID_APP_VERSION'),
                    'androidBuildVersionCode' => Config::get('constant.ANDROID_VERSION_CODE'),
                    'androidForceUpdate' => Config::get('constant.ANDROID_FORCE_UPDATE'),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.ERROR_WHILE_CHECKING_APP_VERSION'),
            ], 500);
        }
    }

    public function getNearByAgent(Request $request){
        try {
            $rule = [
                'page' => 'required',
                'limit' => 'required',
                'latitude' => 'required',
                'longitude' => 'required'
            ];

            $validator =  Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $latitude = $request->latitude;
            $longitude = $request->longitude;
            // This lat, lng co-ordinate is to find Agent between given bound - Start
            $northEastLat = (isset($request->northEastLat) && !empty($request->northEastLat)) ? $request->northEastLat : null;
            $northEastLng = (isset($request->northEastLng) && !empty($request->northEastLng)) ? $request->northEastLng : null;
            $southWestLat = (isset($request->southWestLat) && !empty($request->southWestLat)) ? $request->southWestLat : null;
            $southWestLng = (isset($request->southWestLng) && !empty($request->southWestLng)) ? $request->southWestLng : null;
            // This lat, lng co-ordinate is to find Agent between given bound - Ends

            // search like
            $searchByAgentName = (isset($request->agent_name_like) && !empty($request->agent_name_like)) ? $request->agent_name_like : null;
            $searchByAddress = (isset($request->address_like) && !empty($request->address_like)) ? $request->address_like : null;
            $searchByStreetAddress = (isset($request->street_address_like) && !empty($request->street_address_like)) ? $request->street_address_like : null;
            $searchByLocality = (isset($request->locality_like) && !empty($request->locality_like)) ? $request->locality_like : null;
            $searchByCountry = (isset($request->country_like) && !empty($request->country_like)) ? $request->country_like : null;
            $searchByCity = (isset($request->city_like) && !empty($request->city_like)) ? $request->city_like : null;
            $searchByState = (isset($request->state_like) && !empty($request->state_like)) ? $request->state_like : null;
            $searchByZipCode = (isset($request->zip_code_like) && !empty($request->zip_code_like)) ? $request->zip_code_like : null;

            // sort
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // Total agent Count
            $totalCount = User::getNearByAgentCount($northEastLat, $northEastLng, $southWestLat, $southWestLng, $searchByAgentName, $searchByAddress, $searchByStreetAddress, $searchByLocality, $searchByCountry, $searchByCity, $searchByState, $searchByZipCode);

            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalCount);

            $agentList = User::getNearByAgent($request->limit, $getPaginationData['offset'], $sort, $order, $latitude, $longitude, $northEastLat, $northEastLng, $southWestLat, $southWestLng, $searchByAgentName, $searchByAddress, $searchByStreetAddress, $searchByLocality, $searchByCountry, $searchByCity, $searchByState, $searchByZipCode);

            foreach ($agentList as $agent){
                $agent->photo = ($agent->photo !== null && $agent->photo != '') ? url('storage/' . $this->userOriginalImagePath . $agent->photo) : url('images/default_user_profile.png');
            }

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.nearby_agent_get_successfully'),
                'totalCount' => $totalCount,
                'noOfPages' => $getPaginationData['noOfPages'],
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'data' => $agentList
            ], 200);

        } catch (\Exception $e){
            // Log Message
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * get setting list
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function setting()
    {
        try {
            $settings = Settings::where('status', Config::get('constant.ACTIVE_FLAG'))->get()->each(function ($setting) {
                $setting->value = ($setting->slug == Config::get('constant.LOGO_SETTING_SLUG') ? ($setting->value != null && $setting->value != '' ? url('storage/' . $this->settingLogoOriginalImageUploadPath . $setting->value) : '') : $setting->value);
            });

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $settings,
            ]);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
        }
    }
    /**
     * Get List of following items
     *  1 => "Purpose",
     *  2 => "Source of fund",
     *  3 => "Sender and Beneficiary Relationship",
     *  4 => "Account Type",
     *  5 => "Sender Identification Type",
     *  6 => "Beneficiary Identification Type",
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getListOfTrangloCodes(Request $request) {
        try {
            $trangloCommonCode = new TrangloCommonCode();
            $objList = $trangloCommonCode->getListByType($request->code_type);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $objList,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }
}
