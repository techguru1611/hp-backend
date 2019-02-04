<?php

namespace App\Http\Controllers;

use App\EvoucherRequest;
use App\Http\Controllers\Controller;
use App\OTPManagement;
use App\User;
use App\UserDetail;
use App\UserTransaction;
use Carbon\Carbon;
use Config;
use CommissionService;
use DB;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Validator;
use Illuminate\Validation\Rule;
use App\AuditTransaction;

class EvoucherController extends Controller
{
    public function __construct()
    {
        $this->objUser = new User();
        $this->otpExpireSeconds = Config::get('constant.OTP_EXPIRE_SECONDS');
        $this->resendOtpExpireSeconds = Config::get('constant.RESEND_OTP_EXPIRE_SECONDS');
    }

    /**
     * To send OTP to sender while send e-voucher to another user / agent
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendOTP(Request $request)
    {
        try {
            $rule = [
                'mobile_number' => 'required|min:10|max:17|regex:/^\+?\d+$/',
                'amount' => 'required|numeric|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // Restrict Admin to do this action
            if ($request->user()->role_id == Config::get('constant.SUPER_ADMIN_ROLE_ID')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.unauthorized_access'),
                ], 200);
            }
            // Validate customer mobile number
            $validateMobileNumber = Helpers::validateMobileNumber($request->mobile_number);

            // Start database transaction
            DB::beginTransaction();
            if (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0) {
                // Transfer money to un-registered user
                if ($validateMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) {
                    // Create unregistered user
                    $guestUser = $this->objUser->where('mobile_number', $request->mobile_number)->first();
                    if ($guestUser === null) {
                        $guestUser = $this->objUser->insertUpdate([
                            'full_name' => Config::get('constant.GUEST_NAME'),
                            'mobile_number' => $request->mobile_number,
                            'role_id' => Config::get('constant.USER_ROLE_ID'),
                            'verification_status' => Config::get('constant.UNREGISTERED_USER_STATUS'),
                        ]);
                        $userDetail = new UserDetail([
                            'balance_amount' => 0.00,
                            'country_code' => Config::get('constant.DEFAULT_COUNTRY'),
                        ]);
                        $guestUser->userDetail()->save($userDetail);
                    }
                } else {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => $validateMobileNumber['message'],
                    ], 200);
                }
            }

            // Check sender balance
            $senderUser = $request->user();
            $senderDetail = $request->user()->userDetail()->first();
            $receiverUser = User::where('mobile_number', $request->mobile_number)->first();
            $otpMessageText = trans('apimessages.EVOUCHER_OTP_MESSAGE_TO_SENDER');

            if ($senderDetail->balance_amount < $request->amount) {
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                    '<Mobile Number>' => $senderUser->mobile_number
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.insufficient_balance_msg'),
                ], 200);
            }

            // Save evoucher request
            $otp = mt_rand(100000, 999999);
            $evoucherRequest = new EvoucherRequest([
                'otp_sent_to' => $request->user()->mobile_number,
                'to_user_id' => ($request->user()->mobile_number == $request->mobile_number) ? null : $receiverUser->id, // If user send e-voucher to self
                'amount' => $request->amount,
                'description' => (isset($request->description)) ? $request->description : null,
                'otp' => $otp,
                'otp_created_at' => Carbon::now(),
                'created_by' => $request->user()->id,
                'unregistered_number' => (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0 && $validateMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) ? $request->mobile_number : null,
            ]);
            $senderUser->evoucherRequest()->save($evoucherRequest);
            // Send OTP message
            $message = strtr($otpMessageText, [
                '<OTP>' => $otp,
                '<Value>' => number_format($request->amount, 2),
                '<Receiver Mobile Number>' => Helpers::maskString($receiverUser->mobile_number),
            ]);

            $sendOtp = Helpers::sendMessage($request->user()->mobile_number, $message);
            Log::channel('otp')->info(strtr(trans('log_messages.otp_success_action'),[
                '<Mobile Number>' => $request->user()->mobile_number,
                '<Action>' => 'E Voucher'
            ]));

            $om = new OTPManagement();
            $om->otp = $otp;
            $om->from_user = $senderUser->id;
            $om->to_user = $receiverUser->id;
            $om->otp_sent_to = $request->user()->mobile_number;
            $om->amount = $request->amount;
            $om->operation = Config::get('constant.OTP_O_E_VOUCHER_SENT_VERIFICATION');
            $om->created_by = $request->user()->id;
            $om->message = $message;
            $om->save();

            if (!$sendOtp) {
                DB::rollback();
                Log::channel('otp')->error(strtr(trans('log_messages.otp_error_action'),[
                    '<Mobile Number>' => $request->user()->mobile_number,
                    '<Action>' => 'E-Voucher'
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.something_went_wrong'),
                ], 200);
            }
            $userDetail = $request->user()->with('userDetail')->where('id', $request->user()->id)->first();

            // Manage response message - Start
            if (Config::get('constant.DISPLAY_OTP') == 1) {
                $responseMessage = strtr(trans('apimessages.OTP_SENT_SUCCESS_MESSAGE_FOR_DEV'),[
                    '<Mobile Number>' => $request->user()->mobile_number,
                    '<OTP>' => $otp
                ]);
            } else {
                $responseMessage = strtr(trans('apimessages.OTP_SENT_SUCCESS_MESSAGE_FOR_PROD'),[
                    '<Mobile Number>' => $request->user()->mobile_number,
                ]);
            }

            DB::commit();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => $responseMessage,
                'data' => [
                    'request_id' => $evoucherRequest->id,
                    'otp_sent_to' => $request->user()->mobile_number,
                    'sender_mobile_number' => $request->user()->mobile_number,
                    'wallet_balance' => (isset($userDetail->userDetail->country_code) && isset($userDetail->userDetail->balance_amount)) ? $userDetail->userDetail->country_code . " " . $userDetail->userDetail->balance_amount : "",
                ],
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
     * To verify OTP of sender while send e-voucher to another user / agent
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOTP(Request $request)
    {
        try {
            $rule = [
                'mobile_number' => 'required',
                'otp' => 'required|integer',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // To check that evoucher request already proceed or invalid
            $senderUser = User::where('mobile_number', $request->mobile_number)->first();
            $evoucherRequest = $senderUser->evoucherRequest()->where('otp', $request->otp)->first();

            // If request with this otp not found
            if ($evoucherRequest === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.invalid_otp'),
                ], 200);
            }

            if ($evoucherRequest->otp == null || $evoucherRequest->otp_created_at == null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_REQUEST_ALREADY_PROCCED_NOT_POSSIBLE'),
                ], 200);
            }

            $secondsSinceEvoucherRequestOtpCreated = Carbon::now()->diffInSeconds($evoucherRequest->otp_created_at);

            if ($secondsSinceEvoucherRequestOtpCreated > $this->otpExpireSeconds) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.otp_expired'),
                ], 200);
            }

            DB::beginTransaction();
            // Check sender balance
            $senderDetail = $senderUser->userDetail()->lockForUpdate()->first();

            if ($senderDetail->balance_amount < $evoucherRequest->amount) {
                DB::rollback();
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                    '<Mobile Number>' => $senderUser->mobile_number
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.insufficient_balance_msg'),
                ], 200);
            }

            // Receiver User
            $receiverUser = ($evoucherRequest->to_user_id === null) ? null : User::find($evoucherRequest->to_user_id); // null if sender has sent e-voucher to self

            $otp = mt_rand(100000, 999999);

            // Deduct from sender balance
            $senderDetail->balance_amount -= $evoucherRequest->amount;
            $senderDetail->save();

            // Save transaction history
            $transaction = new UserTransaction([
                'to_user_id' => ($receiverUser === null) ? null : $receiverUser->id, // If sender had sent e-voucher to self
                'amount' => $evoucherRequest->amount,
                'net_amount' => $evoucherRequest->amount,
                'description' => $evoucherRequest->description,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.SUCCESS_TRANSACTION_STATUS'),
                'transaction_type' => Config::get('constant.E_VOUCHER_TRANSACTION_TYPE'),
                'otp' => $otp,
                'created_by' => $request->user()->id,
            ]);
            $senderUser->senderTransaction()->save($transaction);
            Log::channel('transaction')->info(strtr(trans('log_messages.e_voucher'),[
                '<To User>' => $request->mobile_number,
                '<Form User>' => $request->user()->mobile_number,
                '<Amount>' => $evoucherRequest->amount
            ]));

            /**
             * @added on 23rd July, 2018
             * Following code is injected to create audit transaction for each user.
             * 
             * @Code injection started.
             */
            $auditTransaction = new AuditTransaction();
            $auditTransactionRecord = [
                'transaction_type_id' => Config::get('constant.AUDIT_TRANSACTION_TYPE_E_VOUCHER'),
                'transaction_date' => Carbon::now(),
                'transaction_user' => $request->user()->id,
                'action_model_id' => $transaction->id,
                'action_detail' => $transaction
            ];
            $auditTransaction->insertUpdate($auditTransactionRecord);
            /**
             * @added on 23rd July, 2018
             * @Code injection ended.
             */

            $evoucherRequest->delete(); // Delete evoucher request

            // E-Voucher alert to receiver user
            $receiverMsg = strtr(trans('apimessages.E_VOUCHER_TEXT_MESSAGE_TO_RECEIVER_USER'), [
                '<User Name>' => $senderUser->full_name,
                '<User Mobile Number>' => Helpers::maskString($senderUser->mobile_number),
                '<Value>' => number_format($transaction->amount, 2),
                '<Authorization Code>' => implode('-', str_split($otp, 3)),
                // '<Transaction ID>' => $transaction->transaction_id,
            ]);
            $evoucherMsgToReceiver = Helpers::sendMessage(($receiverUser === null) ? $request->user()->mobile_number : $receiverUser->mobile_number, $receiverMsg);

            // Save transaction message to notification table for e-voucher
            Helpers::saveNotificationMessage(($receiverUser === null) ? $request->user() : $receiverUser, $receiverMsg);

            if (!$evoucherMsgToReceiver) {
                DB::rollback();
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.SOMETHING_WENT_WRONG_WHILE_SENDING_EVOUCHER'),
                ], 200);
            }

            // Send text message to sender
            if ($receiverUser !== null) {
                // Transfer alert to sender
                if ($receiverUser->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) {
                    $senderMsg = strtr(trans('apimessages.MESSAGE_TO_SENDER_AFTER_SENT_EVOUCHER_TO_UNREGISTERED_USER'), [
                        '<Sender Name>' => Config::get('constant.SELF'),
                        '<Value>' => number_format($transaction->amount, 2),
                        '<Receiver Mobile Number>' => Helpers::maskString($receiverUser->mobile_number),
                        '<Transaction ID>' => $transaction->transaction_id,
                        '<Sender Balance Amount>' => number_format($senderDetail->balance_amount, 2),
                    ]);
                } else {
                    $senderMsg = strtr(trans('apimessages.MESSAGE_TO_SENDER_AFTER_SENT_EVOUCHER_TO_USER'), [
                        '<Sender Name>' => Config::get('constant.SELF'),
                        '<Value>' => number_format($transaction->amount, 2),
                        '<Receiver Name>' => $receiverUser->full_name,
                        '<Receiver Mobile Number>' => Helpers::maskString($receiverUser->mobile_number),
                        '<Transaction ID>' => $transaction->transaction_id,
                        '<Sender Balance Amount>' => number_format($senderDetail->balance_amount, 2),
                    ]);
                }
                $evoucherMsgToSender = Helpers::sendMessage($senderUser->mobile_number, $senderMsg);

                // Save transaction message to notification table for e-voucher to sender
                Helpers::saveNotificationMessage($senderUser, $senderMsg);
            }

            DB::commit();
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'authorizationCode' => $otp, // Testing purpose only (Need to remove after testing)
                'data' => [
                    'id' => $transaction->id,
                    'sender_name' => $senderUser->full_name,
                    'receiver_name' => ($receiverUser === null) ? null : $receiverUser->full_name,
                    'receiver_mobile_number' => ($receiverUser === null) ? null : $receiverUser->mobile_number,
                    'transaction_id' => $transaction->transaction_id,
                    'created_at' => date_format($transaction->created_at, 'Y-m-d H:i:s'),
                    'transaction_amount' => $transaction->amount,
                    'sender_country_code' => $senderDetail->country_code,
                ],
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
     * To send e-voucher without OTP verification
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send (Request $request) {
        try {
            $rule = [
                'mobile_number' => 'required|min:10|max:17|regex:/^\+?\d+$/',
                'amount' => 'required|numeric|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // Restrict Admin to do this action
            if ($request->user()->role_id == Config::get('constant.SUPER_ADMIN_ROLE_ID')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.unauthorized_access'),
                ], 200);
            }
            // Validate customer mobile number
            $validateMobileNumber = Helpers::validateMobileNumber($request->mobile_number);

            // Start database transaction
            DB::beginTransaction();
            if (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0) {
                // Transfer money to un-registered user
                if ($validateMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) {
                    // Create unregistered user
                    $guestUser = $this->objUser->where('mobile_number', $request->mobile_number)->first();
                    if ($guestUser === null) {
                        $guestUser = $this->objUser->insertUpdate([
                            'full_name' => Config::get('constant.GUEST_NAME'),
                            'mobile_number' => $request->mobile_number,
                            'role_id' => Config::get('constant.USER_ROLE_ID'),
                            'verification_status' => Config::get('constant.UNREGISTERED_USER_STATUS'),
                        ]);
                        $userDetail = new UserDetail([
                            'balance_amount' => 0.00,
                            'country_code' => Config::get('constant.DEFAULT_COUNTRY'),
                        ]);
                        $guestUser->userDetail()->save($userDetail);
                    }
                } else {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => $validateMobileNumber['message'],
                    ], 200);
                }
            }

            // Check sender balance
            $senderUser = $request->user();
            $senderDetail = $request->user()->userDetail()->first();

            $feeData = CommissionService::calculateFee($request->amount, Config::get('constant.SEND_E_VOUCHER_FEE'));

            // Handle error
            if ($feeData['status'] == 0) {
                DB::rollback();
                return response()->json([
                    'status' => $feeData['status'],
                    'message' => $feeData['message'],
                ], $feeData['code']);
            }

            if ($senderDetail->balance_amount < ($request->amount + $feeData['data']['totalCommission'])) {
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                    '<Mobile Number>' => $senderUser->mobile_number
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.insufficient_balance_msg'),
                ], 200);
            }

            // Receiver User
            $receiverUser = ($request->mobile_number == $request->user()->mobile_number) ? null : User::where('mobile_number', $request->mobile_number)->first(); // null if sender has sent e-voucher to self

            $otp = mt_rand(100000, 999999);

            // Deduct from sender balance
            $senderDetail->balance_amount -= ($request->amount + $feeData['data']['totalCommission']);
            $senderDetail->save();

            // Add fee to admin user
            $adminData = User::with('userDetail')->where('role_id', Config::get('constant.SUPER_ADMIN_ROLE_ID'))->first();
            // Upgrade commission wallet balance
            $adminData->userDetail->commission_wallet_balance += $feeData['data']['helapayCommission'];
            $adminData->userDetail->save();

            // Save transaction history
            $transaction = new UserTransaction([
                'to_user_id' => ($receiverUser === null) ? null : $receiverUser->id, // If sender had sent e-voucher to self
                'amount' => ($request->amount + $feeData['data']['totalCommission']),
                'net_amount' => $request->amount,
                'total_commission_amount' => $feeData['data']['totalCommission'],
                'admin_commission_amount' => $feeData['data']['totalCommission'],
                'description' => $request->description,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.SUCCESS_TRANSACTION_STATUS'),
                'transaction_type' => Config::get('constant.E_VOUCHER_TRANSACTION_TYPE'),
                'otp' => $otp,
                'created_by' => $request->user()->id,
            ]);
            $senderUser->senderTransaction()->save($transaction);
            Log::channel('transaction')->info(strtr(trans('log_messages.e_voucher'),[
                '<To User>' => $request->mobile_number,
                '<Form User>' => $request->user()->mobile_number,
                '<Amount>' => $request->amount
            ]));

            /**
             * @added on 23rd July, 2018
             * Following code is injected to create audit transaction for each user.
             * 
             * @Code injection started.
             */
            $auditTransaction = new AuditTransaction();
            $auditTransactionRecord = [
                'transaction_type_id' => Config::get('constant.AUDIT_TRANSACTION_TYPE_E_VOUCHER'),
                'transaction_date' => Carbon::now(),
                'transaction_user' => $request->user()->id,
                'action_model_id' => $transaction->id,
                'action_detail' => $transaction
            ];
            $auditTransaction->insertUpdate($auditTransactionRecord);
            /**
             * @added on 23rd July, 2018
             * @Code injection ended.
             */

            // E-voucher alert to receiver user
            $receiverMsg = strtr(trans('apimessages.E_VOUCHER_TEXT_MESSAGE_TO_RECEIVER_USER'), [
                '<User Name>' => $senderUser->full_name,
                '<User Mobile Number>' => Helpers::maskString($senderUser->mobile_number),
                '<Value>' => number_format($transaction->net_amount, 2),
                '<Authorization Code>' => implode('-', str_split($otp, 3)),
                // '<Transaction ID>' => $transaction->transaction_id,
            ]);
            $evoucherMsgToReceiver = Helpers::sendMessage(($receiverUser === null) ? $request->user()->mobile_number : $receiverUser->mobile_number, $receiverMsg);

            $om = new OTPManagement();
            $om->otp = $otp;
            $om->from_user = $senderUser->id;
            $om->to_user = ($receiverUser === null) ? $request->user()->id : $receiverUser->id;
            $om->otp_sent_to = ($receiverUser === null) ? $request->user()->mobile_number : $receiverUser->mobile_number;
            $om->amount = $request->amount;
            $om->operation = Config::get('constant.OTP_O_E_VOUCHER_AUTHORIZATION_CODE');
            $om->created_by = $request->user()->id;
            $om->message = $receiverMsg;
            $om->save();

            // Save transaction message to notification table for e-voucher
            Helpers::saveNotificationMessage(($receiverUser === null) ? $request->user() : $receiverUser, $receiverMsg);

            if (!$evoucherMsgToReceiver) {
                DB::rollback();
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.SOMETHING_WENT_WRONG_WHILE_SENDING_EVOUCHER'),
                ], 200);
            }

            // Send text message to sender
            if ($receiverUser !== null) {
                // Transfer alert to sender
                if ($receiverUser->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) {
                    $senderMsg = strtr(trans('apimessages.MESSAGE_TO_SENDER_AFTER_SENT_EVOUCHER_TO_UNREGISTERED_USER'), [
                        '<Sender Name>' => Config::get('constant.SELF'),
                        '<Value>' => number_format($transaction->net_amount, 2),
                        '<Receiver Mobile Number>' => Helpers::maskString($receiverUser->mobile_number),
                        '<Transaction ID>' => $transaction->transaction_id,
                        '<Fee>' => number_format($feeData['data']['totalCommission'],2),
                        '<Sender Balance Amount>' => number_format($senderDetail->balance_amount, 2),
                    ]);
                } else {
                    $senderMsg = strtr(trans('apimessages.MESSAGE_TO_SENDER_AFTER_SENT_EVOUCHER_TO_USER'), [
                        '<Sender Name>' => Config::get('constant.SELF'),
                        '<Value>' => number_format($transaction->amount, 2),
                        '<Receiver Name>' => $receiverUser->full_name,
                        '<Receiver Mobile Number>' => Helpers::maskString($receiverUser->mobile_number),
                        '<Transaction ID>' => $transaction->transaction_id,
                        '<Fee>' => number_format($feeData['data']['totalCommission'],2),
                        '<Sender Balance Amount>' => number_format($senderDetail->balance_amount, 2),
                    ]);
                }
                $evoucherMsgToSender = Helpers::sendMessage($senderUser->mobile_number, $senderMsg);

                // Save transaction message to notification table for e-voucher to sender
                Helpers::saveNotificationMessage($senderUser, $senderMsg);
            }

            DB::commit();
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'authorizationCode' => $otp, // Testing purpose only (Need to remove after testing)
                'data' => [
                    'id' => $transaction->id,
                    'sender_name' => $senderUser->full_name,
                    'receiver_name' => ($receiverUser === null) ? null : $receiverUser->full_name,
                    'receiver_mobile_number' => ($receiverUser === null) ? null : $receiverUser->mobile_number,
                    'transaction_id' => $transaction->transaction_id,
                    'created_at' => date_format($transaction->created_at, 'Y-m-d H:i:s'),
                    'transaction_amount' => $transaction->net_amount,
                    'sender_country_code' => $senderDetail->country_code,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * To make action on e-voucher history
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function action(Request $request)
    {
        try {
            $rule = [
                'id' => 'required|regex:/^[1-9][0-9]{0,15}$/',
                'action' => ['required', Rule::in([Config::get('constant.RESEND_EVOUCHER_CODE_ACTION'), Config::get('constant.REDEEM_EVOUCHER_TO_WALLET')])],
            ];

            // Customize error message
            $messages = [
                'id.regex' => 'Invalid input parameter found.',
            ];

            $validator = Validator::make($request->all(), $rule, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // Resend evoucher code
            if ($request->action == Config::get('constant.RESEND_EVOUCHER_CODE_ACTION')) {
                $response = $this->resendEvoucherCode($request->user(), $request->id);
            } else {
                $response = $this->redeemVoucherToWallet($request->user(), $request->id);
            }
            // All good so return the response
            return response()->json([
                'status' => $response['status'],
                'message' => $response['message'],
                'action' => (isset($response['action'])) ? $response['action'] : '',
                'authorizationCode' => (isset($response['authorizationCode'])) ? $response['authorizationCode'] : '',
            ], $response['statusCode']);
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
     * To resend evoucher authorization code to receiver
     *
     * @param Object [$user] [User Object]
     * @param Integer [$transactionId] [Transaction id of e-voucher]
     * @return array
     */
    public function resendEvoucherCode($user, $transactionId)
    {
        try {
            $transaction = $user->senderTransaction()->where('id', $transactionId)->first();

            // Transaction not found
            if ($transaction === null) {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_DOESNT_EXIST'),
                    'statusCode' => 200,
                ];
            }
            // If e-voucher already redeemed by receiver
            if ($transaction->transaction_type == Config::get('constant.REDEEMED_TRANSACTION_TYPE')) {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_ALREADY_REDEEMED_BY_RECEIVER'),
                    'statusCode' => 200,
                ];
            }
            // If transaction doesn't belongs to e-voucher
            if ($transaction->transaction_type != Config::get('constant.E_VOUCHER_TRANSACTION_TYPE')) {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_DOESNT_EXIST'),
                    'statusCode' => 200,
                ];
            }

            // Check if e-voucher expired or not
            if ($transaction->transaction_status == Config::get('constant.EXPIRED_TRANSACTION_STATUS')) {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_EXPIRED_MESSAGE_WHILE_RESEND'),
                    'statusCode' => 200,
                ];
            }

            $receiver = User::find($transaction->to_user_id);

            if ($receiver === null && $transaction->to_user_id != null) {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.RECEIVER_USER_DOESNT_EXIST_OR_DELETED'),
                    'statusCode' => 200,
                ];
            }
            // E-voucher alert to receiver user
            $receiverMsg = strtr(trans('apimessages.E_VOUCHER_TEXT_MESSAGE_TO_RECEIVER_USER'), [
                '<User Name>' => $user->full_name,
                '<User Mobile Number>' => Helpers::maskString($user->mobile_number),
                '<Value>' => number_format($transaction->amount, 2),
                '<Authorization Code>' => implode('-', str_split($transaction->otp, 3)),
                // '<Transaction ID>' => $transaction->transaction_id,
            ]);
            $evoucherMsgToReceiver = Helpers::sendMessage(($transaction->to_user_id === null) ? $user->mobile_number : $receiver->mobile_number, $receiverMsg);
            // Code not send successfully
            if (!$evoucherMsgToReceiver) {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.SOMETHING_WENT_WRONG_WHILE_SENDING_EVOUCHER'),
                    'statusCode' => 200,
                ];
            }
            return [
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'action' => Config::get('constant.RESEND_EVOUCHER_CODE_ACTION'),
                'authorizationCode' => $transaction->otp, // Testing purpose only (Need to remove after testing)
                'statusCode' => 200,
            ];
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return [
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'statusCode' => 500,
            ];
        }
    }

    /**
     * To resend evoucher authorization code to receiver
     *
     * @param Object [$user] [User Object]
     * @param Integer [$transactionId] [Transaction id of e-voucher]
     * @return array
     */
    public function redeemVoucherToWallet($user, $transactionId)
    {
        try {
            $transaction = UserTransaction::find($transactionId);
            // Transaction not found
            if ($transaction === null) {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_DOESNT_EXIST'),
                    'statusCode' => 200,
                ];
            }

            if ($transaction->to_user_id === null) {
                if ($transaction->from_user_id != $user->id) {
                    return [
                        'status' => 0,
                        'message' => trans('apimessages.EVOUCHER_DOESNT_EXIST'),
                        'statusCode' => 200,
                    ];
                }
            } else {
                $transactionDetail = $user->receiverTransaction()->where('id', $transactionId)->first();
                if ($transactionDetail === null) {
                    return [
                        'status' => 0,
                        'message' => trans('apimessages.EVOUCHER_DOESNT_EXIST'),
                        'statusCode' => 200,
                    ];
                }
            }
            
            // If e-voucher already redeemed by receiver
            if ($transaction->transaction_type == Config::get('constant.REDEEMED_TRANSACTION_TYPE') || $transaction->transaction_type == Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE')) {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_ALREADY_REDEEMED'),
                    'statusCode' => 200,
                ];
            }
            // If transaction doesn't belongs to e-voucher
            if ($transaction->transaction_type != Config::get('constant.E_VOUCHER_TRANSACTION_TYPE')) {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_DOESNT_EXIST'),
                    'statusCode' => 200,
                ];
            }

            // Check if e-voucher expired or not
            if ($transaction->transaction_status == Config::get('constant.EXPIRED_TRANSACTION_STATUS')) {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_EXPIRED_MESSAGE_WHILE_ADD_TO_WALLET'),
                    'statusCode' => 200,
                ];
            }

            DB::beginTransaction();

            // Add evoucher to receiver's wallet
            $userDetail = $user->userDetail()->lockForUpdate()->first();
            $userDetail->balance_amount += $transaction->net_amount;
            $userDetail->save();

            // Update transaction status to redeemed
            $transaction->update([
                'transaction_type' => Config::get('constant.REDEEMED_TRANSACTION_TYPE'),
                'otp' => null,
                'voucher_redeemed_at' => Carbon::now(),
            ]);
            Log::channel('transaction')->info(strtr(trans('log_messages.redeem_e_voucher'),[
                '<Transaction>' => $transaction->transaction_id,
                '<Amount>' => $transaction->amount,
                '<Action>' => 'Add to Wallet'
            ]));

            DB::commit();
            return [
                'status' => 1,
                'action' => Config::get('constant.REDEEM_EVOUCHER_TO_WALLET'),
                'message' => trans('apimessages.default_success_msg'),
                'statusCode' => 200,
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return [
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'statusCode' => 500,
            ];
        }
    }

    /**
     * To verify e-voucher code by agent
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEvoucherCode(Request $request)
    {
        try {
            $rule = [
                'authorizationCode' => 'required',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            $userTransaction = UserTransaction::where('otp', $request->authorizationCode)->where('transaction_type', Config::get('constant.E_VOUCHER_TRANSACTION_TYPE'))->first();
            
            // E-voucher with this authorization code not found
            if ($userTransaction === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.NO_VOUCHER_FOUND_WITH_THIS_AUTHORIZATION_CODE'),
                ], 200);
            }

            // Check if e-voucher expired or not
            if ($userTransaction->transaction_status == Config::get('constant.EXPIRED_TRANSACTION_STATUS')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_EXPIRED_MESSAGE_WHILE_REDEEM'),
                ], 200);
            }

            // If agent is receiver of e-voucher
            if ($userTransaction->to_user_id == $request->user()->id) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.USE_ADD_TO_WALLET_TO_REDEEM_VOUCHER'),
                ], 200);
            }

            // Get commission data
            $commissionData = CommissionService::calculateCommission($userTransaction->net_amount, $request->user()->id);

            // Handle error
            if ($commissionData['status'] == 0) {
                return response()->json([
                    'status' => $commissionData['status'],
                    'message' => $commissionData['message'],
                ], $commissionData['code']);
            }

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'amount' => number_format(($userTransaction->net_amount), 2, '.', ''),
                    'receiver_full_name' => ($userTransaction->receiveruser->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) ? null : $userTransaction->receiveruser->full_name,
                    'receiver_mobile_number' => $userTransaction->receiveruser->mobile_number,
                    'netAmount' => number_format($commissionData['data']['netAmount'], 2, '.', ''),
                    'totalCommission' => number_format($commissionData['data']['totalCommission'], 2, '.', ''),
                    'agentCommission' => number_format($commissionData['data']['agentCommission'], 2, '.', ''),
                    'helapayCommission' => number_format($commissionData['data']['helapayCommission'], 2, '.', ''),
                ],
            ], 200);
        } catch (\Exception $e) {
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
     * To redeem voucher of user and cashout to that user
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function redeemVoucher(Request $request)
    {
        try {
            $rule = [
                'authorizationCode' => 'required',
                'amount' => 'required|numeric|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $userTransaction = UserTransaction::where('otp', $request->authorizationCode)->where('transaction_type', Config::get('constant.E_VOUCHER_TRANSACTION_TYPE'))->first();
            
            // E-voucher with this authorization code not found
            if ($userTransaction === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.NO_VOUCHER_FOUND_WITH_THIS_AUTHORIZATION_CODE'),
                ], 200);
            }

            // If agent is receiver of e-voucher
            if ($userTransaction->to_user_id == $request->user()->id) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.USE_ADD_TO_WALLET_TO_REDEEM_VOUCHER'),
                ], 200);
            }

            // Amount with request is mismatch
            if ($userTransaction->net_amount != $request->amount) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.INCORRECT_AMOUNT_WHILE_REDEEM_EVOUCHER'),
                ], 200);
            }

            // Check if e-voucher expired or not
            if ($userTransaction->transaction_status == Config::get('constant.EXPIRED_TRANSACTION_STATUS')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_EXPIRED_MESSAGE_WHILE_REDEEM'),
                ], 200);
            }

            // Get commission data
            $commissionData = CommissionService::calculateCommission($userTransaction->net_amount, $request->user()->id);

            // Handle error
            if ($commissionData['status'] == 0) {
                return response()->json([
                    'status' => $commissionData['status'],
                    'message' => $commissionData['message'],
                ], $commissionData['code']);
            }

            // validation for amount
            if ($commissionData['data']['totalCommission'] >= $userTransaction->amount){
                return response()->json([
                    'status' => 0,
                    'message'=> trans('apimessages.insufficient_amount_for_commission')
                ]);
            }

            DB::beginTransaction();

            // Add commission to admin user
            $adminData = User::with('userDetail')->where('role_id', Config::get('constant.SUPER_ADMIN_ROLE_ID'))->first();
            // Upgrade commission wallet balance
            $adminData->userDetail->commission_wallet_balance += $commissionData['data']['helapayCommission'];
            $adminData->userDetail->save();

            $totalCommissionAmount = $userTransaction->total_commission_amount + $commissionData['data']['totalCommission'];
            $userTransaction->update([
                'transaction_type' => Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE'),
                'otp' => null,
                'voucher_redeemed_from' => $request->user()->id,
                'voucher_redeemed_at' => Carbon::now(),
                'net_amount' => $commissionData['data']['netAmount'],
                'total_commission_amount' => $totalCommissionAmount,
                'admin_commission_amount_from_receiver' => $commissionData['data']['helapayCommission'],
                'agent_commission_amount' => $commissionData['data']['agentCommission'],
                'agent_commission_in_percentage' => $commissionData['data']['agentCommissionPerc'],
                'commission_agent_id' => $request->user()->id,
                'receiver_commission_admin_id' => $adminData->id,
            ]);
            Log::channel('transaction')->info(strtr(trans('log_messages.redeem_e_voucher'),[
                '<Transaction>' => $userTransaction->transaction_id,
                '<Amount>' => $userTransaction->amount,
                '<Action>' => 'Cash Out'
            ]));

            /**
             * @added on 23rd July, 2018
             * Following code is injected to create audit transaction for each user.
             * 
             * @Code injection started.
             */
            $auditTransaction = new AuditTransaction();
            $auditTransactionRecord = [
                'transaction_type_id' => Config::get('constant.AUDIT_TRANSACTION_TYPE_E_VOUCHER_CASHOUT'),
                'transaction_date' => Carbon::now(),
                'transaction_user' => $request->user()->id,
                'action_model_id' => $userTransaction->id,
                'action_detail' => $userTransaction
            ];
            $auditTransaction->insertUpdate($auditTransactionRecord);
            /**
             * @added on 23rd July, 2018
             * @Code injection ended.
             */

             // Add voucher amount to agent wallet balance
            $userDetail = $request->user()->userDetail()->first();
            $userDetail->balance_amount += $commissionData['data']['netAmount'];
            $userDetail->commission_wallet_balance += $commissionData['data']['agentCommission'];
            $userDetail->save();

            DB::commit();

            // Send message to agent after cashout to user for e-voucher
            $message = strtr(trans('apimessages.MESSAGE_TO_AGENT_AFTER_CASHOUT_TO_USER_FOR_EVOUCHER'), [
                '<Receiver Mobile Number>' => Helpers::maskString($userTransaction->receiveruser->mobile_number),
                '<Sender Balance Amount>' => $userDetail->balance_amount,
                '<Transaction ID>' => $userTransaction->transaction_id,
            ]);
            $sendOtp = Helpers::sendMessage($request->user()->mobile_number, $message);

            // Save transaction message to notification table for agent
            Helpers::saveNotificationMessage($request->user(), $message);

            // Send message to sender after cashout to user for e-voucher
            $message = strtr(trans('apimessages.MESSAGE_TO_SENDER_AFTER_CASHOUT_TO_USER_FOR_EVOUCHER'), [
                '<Receiver Name>' => $userTransaction->receiveruser->full_name,
                '<Receiver Mobile Number>' => Helpers::maskString($userTransaction->receiveruser->mobile_number),
                '<Value>' => $request->amount,
                '<Agent Name>' => $request->user()->full_name,
                '<Agent Mobile Number>' => Helpers::maskString($request->user()->mobile_number),
                '<Authorization Code>' => $request->authorizationCode,
                '<Transaction ID>' => $userTransaction->transaction_id,
                '<Fee>' => number_format($commissionData['data']['totalCommission'],2),
            ]);
            $sendOtp = Helpers::sendMessage($userTransaction->senderuser->mobile_number, $message);

            // Save transaction message to notification table for sender
            Helpers::saveNotificationMessage($userTransaction->senderuser, $message);

            // Send message to receiver after cashout to user for e-voucher
            $message = strtr(trans('apimessages.MESSAGE_TO_RECEIVER_AFTER_CASHOUT_TO_USER_FOR_EVOUCHER'), [
                '<Value>' => $request->amount,
                '<Agent Name>' => $request->user()->full_name,
                '<Agent Mobile Number>' => Helpers::maskString($request->user()->mobile_number),
                '<Authorization Code>' => $request->authorizationCode,
                '<Transaction ID>' => $userTransaction->transaction_id,
                '<Sender Name>' => $userTransaction->senderuser->full_name,
                '<Recieve Amount>' => $userTransaction->net_amount,
                '<Fee>' => number_format($commissionData['data']['totalCommission'],2),
                '<Sender Mobile Number>' => Helpers::maskString($userTransaction->senderuser->mobile_number),
            ]);
            $sendOtp = Helpers::sendMessage($userTransaction->receiveruser->mobile_number, $message);

            // Save transaction message to notification table for receiver
            Helpers::saveNotificationMessage($userTransaction->receiveruser, $message);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'id' => $userTransaction->id,
                    'transaction_id' => $userTransaction->transaction_id,
                    'created_at' => date_format($userTransaction->voucher_redeemed_at, "Y-m-d H:i:s"),
                    'amount' => ($userTransaction->amount - $userTransaction->admin_commission_amount),
                    'full_name' => $userTransaction->receiveruser->full_name,
                    'mobile_number' => $userTransaction->receiveruser->mobile_number,
                    'agent_wallet_amount' => $userDetail->country_code . " " . $userDetail->balance_amount,
                ],
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
     * Calculate helapay fee while add evoucher to wallet
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function evoucherAddToWalletFee (Request $request)
    {
        try {
            $rule = [
                'id' => 'required|regex:/^[1-9][0-9]{0,15}$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $transaction = UserTransaction::find($request->id);

            // Transaction not found
            if ($transaction === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_DOESNT_EXIST'),
                ], 200);
            }

            if ($transaction->to_user_id === null) {
                if ($transaction->from_user_id != $request->user()->id) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.EVOUCHER_DOESNT_EXIST'),
                    ], 200);
                }
            } else {
                $transactionDetail = $request->user()->receiverTransaction()->where('id', $request->id)->first();
                if ($transactionDetail === null) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.EVOUCHER_DOESNT_EXIST'),
                    ], 200);
                }
            }
            
            // If e-voucher already redeemed by receiver
            if ($transaction->transaction_type == Config::get('constant.REDEEMED_TRANSACTION_TYPE') || $transaction->transaction_type == Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_ALREADY_REDEEMED'),
                ], 200);
            }
            // If transaction doesn't belongs to e-voucher
            if ($transaction->transaction_type != Config::get('constant.E_VOUCHER_TRANSACTION_TYPE')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.EVOUCHER_DOESNT_EXIST'),
                ], 200);
            }

            // Get commission data
            $feeData = CommissionService::calculateFee($transaction->net_amount, Config::get('constant.E_VOUCHER_ADD_TO_WALLET_FEE'));

            // Handle error
            if ($feeData['status'] == 0) {
                DB::rollback();
                return response()->json([
                    'status' => $feeData['status'],
                    'message' => $feeData['message'],
                ], $feeData['code']); 
            }

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'sender_full_name' => $transaction->senderuser->full_name,
                    'sender_mobile_number' => $transaction->senderuser->mobile_number,
                    'receiver_full_name' => $request->user()->full_name,
                    'receiver_mobile_number' => $request->user()->mobile_number,
                    'total_amount' => number_format($transaction->net_amount, 2, '.', ''),
                    'amount' => number_format($transaction->net_amount, 2, '.', ''),
                    'netAmount' => number_format($transaction->net_amount - $feeData['data']['totalCommission'], 2, '.', ''),
                    'totalCommission' => number_format($feeData['data']['totalCommission'], 2, '.', ''),
                    'agentCommission' => number_format($feeData['data']['agentCommission'], 2, '.', ''),
                    'helapayCommission' => number_format($feeData['data']['helapayCommission'], 2, '.', ''),
                ],
            ], 200);
        } catch (\Exception $e) {
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
     * Calculate helapay fee while send e-voucher
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendEvoucherFee(Request $request)
    {
        try {
            $rule = [
                'mobile_number' => 'required|min:10|max:17|regex:/^\+?\d+$/',
                'amount' => 'required|numeric|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // Restrict Admin to do this action
            if ($request->user()->role_id == Config::get('constant.SUPER_ADMIN_ROLE_ID')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.unauthorized_access'),
                ], 200);
            }
            // Validate customer mobile number
            $validateMobileNumber = Helpers::validateMobileNumber($request->mobile_number);

            // Start database transaction
            DB::beginTransaction();
            if (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0) {
                // Transfer money to un-registered user
                if ($validateMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) {
                    // Create unregistered user
                    $guestUser = $this->objUser->where('mobile_number', $request->mobile_number)->first();
                    if ($guestUser === null) {
                        $guestUser = $this->objUser->insertUpdate([
                            'full_name' => Config::get('constant.GUEST_NAME'),
                            'mobile_number' => $request->mobile_number,
                            'role_id' => Config::get('constant.USER_ROLE_ID'),
                            'verification_status' => Config::get('constant.UNREGISTERED_USER_STATUS'),
                        ]);
                        $userDetail = new UserDetail([
                            'balance_amount' => 0.00,
                            'country_code' => Config::get('constant.DEFAULT_COUNTRY'),
                        ]);
                        $guestUser->userDetail()->save($userDetail);
                    }
                } else {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => $validateMobileNumber['message'],
                    ], 200);
                }
            }

            // Check sender balance
            $senderUser = $request->user();
            $senderDetail = $request->user()->userDetail()->first();

            // Get fee data
            $feeData = CommissionService::calculateFee($request->amount, Config::get('constant.SEND_E_VOUCHER_FEE'));

            // Handle error
            if ($feeData['status'] == 0) {
                DB::rollback();
                return response()->json([
                    'status' => $feeData['status'],
                    'message' => $feeData['message'],
                ], $feeData['code']);
            }

            DB::commit();

            // Check sender balance
            if ($senderDetail->balance_amount < ($request->amount + $feeData['data']['totalCommission'])) {
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                    '<Mobile Number>' => $senderUser->mobile_number
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.insufficient_balance_msg'),
                ], 200);
            }

            // Receiver User
            $receiverUser = ($request->mobile_number == $request->user()->mobile_number) ? $request->user() : User::where('mobile_number', $request->mobile_number)->first();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'receiver_full_name' => $receiverUser->full_name,
                    'receiver_mobile_number' => $receiverUser->mobile_number,
                    'total_amount' => number_format($request->amount + $feeData['data']['totalCommission'], 2, '.', ''),
                    'amount' => number_format($request->amount + $feeData['data']['totalCommission'], 2, '.', ''),
                    'netAmount' => number_format($request->amount, 2, '.', ''),
                    'totalCommission' => number_format($feeData['data']['totalCommission'], 2, '.', ''),
                    'agentCommission' => number_format($feeData['data']['agentCommission'], 2, '.', ''),
                    'helapayCommission' => number_format($feeData['data']['helapayCommission'], 2, '.', ''),
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
}
