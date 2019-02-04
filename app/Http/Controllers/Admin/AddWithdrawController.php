<?php

namespace App\Http\Controllers\Admin;

use App\AgentAddOrWithdrawMoneyRequest;
use App\Http\Controllers\Controller;
use App\OTPManagement;
use App\User;
use App\UserTransaction;
use Carbon\Carbon;
use Config;
use DB;
use Illuminate\Support\Facades\Log;
use Mail;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;
use App\AuditTransaction;

class AddWithdrawController extends Controller
{
    public function __construct()
    {
        $this->otpExpireSeconds = Config::get('constant.OTP_EXPIRE_SECONDS');
        $this->resendOtpExpireSeconds = Config::get('constant.RESEND_OTP_EXPIRE_SECONDS');
    }

    /**
     *
     * To validate user mobile number while add or withdraw money from user balance
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateUserMobileNumber(Request $request)
    {
        try {
            $rule = [
                'mobile_number' => 'required',
                'action' => ['required', Rule::in([Config::get('constant.ADD_MONEY_ACTION'), Config::get('constant.WITHDRAW_MONEY_ACTION')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // Validate mobile numbers
            $validateMobileNumber = Helpers::validateMobileNumber($request->mobile_number, $request->user()->mobile_number);

            if (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0) {
                return response()->json([
                    'status' => 0,
                    'message' => $validateMobileNumber['message'],
                ], 200);
            }

            $userDetail = User::with('userDetail')->with('role')->where('mobile_number', $request->mobile_number)->first();
            $userDetail->action = $request->action;
            // All good so return the response
            Log::info(strtr(trans('log_messages.validate_user_mobile'),[
                '<Mobile Number>' => $request->mobile_number,
                '<Action>' => $request->action
            ]));
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'userDetail' => $userDetail,
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
     * Add or withdraw money request to deduct or add balance directly from user balance
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addWithdrawMoneyRequestFromUserBalance(Request $request)
    {
        try {
            $rule = [
                'mobile_number' => 'required',
                'amount' => 'required|numeric|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/',
                'action' => ['required', Rule::in([Config::get('constant.ADD_MONEY_ACTION'), Config::get('constant.WITHDRAW_MONEY_ACTION')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // Validate mobile numbers
            $validateMobileNumber = Helpers::validateMobileNumber($request->mobile_number, $request->user()->mobile_number);

            if (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0) {
                return response()->json([
                    'status' => 0,
                    'message' => $validateMobileNumber['message'],
                ], 200);
            }

            DB::beginTransaction();

            // Check sender balance
            if ($request->action == Config::get('constant.ADD_MONEY_ACTION')) {
                // Here current logined admin is as a sender
                $senderUser = $request->user();
                $senderDetail = $request->user()->userDetail()->lockForUpdate()->first();

                $user = $receiverUser = User::where('mobile_number', $request->mobile_number)->first();
                $receiverDetail = $receiverUser->userDetail()->lockForUpdate()->first();
                $insufficientBalanceErrorMsg = trans('apimessages.insufficient_balance_msg');
                $otp_o_type = Config::get('constant.OTP_O_ADD_MONEY_VERIFICATION');
            } else {
                // Here current logined admin is as a receiver
                $receiverUser = $request->user();
                $receiverDetail = $request->user()->userDetail()->lockForUpdate()->first();

                $user = $senderUser = User::where('mobile_number', $request->mobile_number)->first();
                $senderDetail = $senderUser->userDetail()->lockForUpdate()->first();
                $insufficientBalanceErrorMsg = $senderUser->full_name . trans('apimessages.insufficient_balance');
                $otp_o_type = Config::get('constant.OTP_O_WITHDRAW_MONEY_VERIFICATION');
            }
            if ($senderDetail->balance_amount < $request->amount) {
                DB::rollback();
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                    '<Mobile Number>' => $senderUser->mobile_number
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => $insufficientBalanceErrorMsg,
                ], 200);
            }
            // Save transaction history with not verified status
            $otp = mt_rand(100000, 999999);
            $addOrWithdrawMoneyRequest = new AgentAddOrWithdrawMoneyRequest([
                'otp_sent_to' => $request->user()->mobile_number,
                'amount' => $request->amount,
                'description' => (isset($request->description)) ? $request->description : null,
                'action' => $request->action,
                'otp' => $otp,
                'otp_created_at' => Carbon::now(),
            ]);
            $user->addMoneyRequest()->save($addOrWithdrawMoneyRequest);

            // $mobileNumber = ($request->action == Config::get('constant.ADD_MONEY_ACTION')) ? $request->user()->mobile_number : $request->mobile_number;
            // Send OTP to agent
            
            $sendOtp = Helpers::sendMessage($request->user()->mobile_number, strtr(trans('apimessages.ADD_WITHDRAW_MONEY_OTP_MESSAGE'),[
                '<Action>' => $request->action,
                '<OTP>' => $otp
            ]));

            if (!$sendOtp) {
                Log::channel('otp')->error(strtr(trans('log_messages.otp_error_action'),[
                    '<Mobile Number>' => $request->user()->mobile_number,
                    '<Action>' => $request->action
                ]));
                DB::rollback();
                return response()->json([
                    'status' => '0',
                    'message' => trans('apimessages.something_went_wrong'),
                ], 200);
            }

            Log::channel('otp')->info(strtr(trans('log_messages.otp_success_action'),[
                '<Mobile Number>' => $request->user()->mobile_number,
                '<Action>' => $request->action
            ]));

            $om = new OTPManagement();
            $om->otp = $otp;
            $om->from_user = $request->action == Config::get('constant.WITHDRAW_MONEY_ACTION') ? $senderUser->id : null;
            $om->to_user = $request->action == Config::get('constant.ADD_MONEY_ACTION') ? $receiverUser->id : null;
            $om->otp_sent_to = $request->user()->mobile_number;
            $om->amount = $request->amount;
            $om->operation = $otp_o_type;
            $om->created_by = $request->user()->id;
            $om->message = strtr(trans('apimessages.ADD_WITHDRAW_MONEY_OTP_MESSAGE'),[
                '<Action>' => $request->action,
                '<OTP>' => $otp
            ]);
            $om->save();
            
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
            // Manage response message - Ends

            DB::commit();
            // All good so return the response
            return response()->json([
                'status' => '1',
                'message' => $responseMessage,
                'data' => [
                    'otp_sent_to' => $request->user()->mobile_number, //$mobileNumber,
                    'mobile_number' => $request->mobile_number,
                    'action' => $request->action,
                    'request_id' => $addOrWithdrawMoneyRequest->id,
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
     * Validate add or withdraw money request by OTP to deduct or add balance directly from user balance
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyAddWithdrawMoneyFromUserBalanceOtp(Request $request)
    {
        try {
            $rule = [
                'mobile_number' => 'required',
                'otp' => 'required|integer',
                'action' => ['required', Rule::in([Config::get('constant.ADD_MONEY_ACTION'), Config::get('constant.WITHDRAW_MONEY_ACTION')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // Validate mobile numbers
            $validateMobileNumber = Helpers::validateMobileNumber($request->mobile_number, $request->user()->mobile_number);

            if (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0) {
                return response()->json([
                    'status' => 0,
                    'message' => $validateMobileNumber['message'],
                ], 200);
            }

            // Get add money request of agent
            $user = User::where('mobile_number', $request->mobile_number)->first();
            $addOrWithdrawMoneyRequest = $user->addMoneyRequest()->where('otp', $request->otp)->where('action', $request->action)->first();

            // If request with this otp not found
            if ($addOrWithdrawMoneyRequest === null) {
                return response()->json([
                    'status' => 0,
                    'message' => ($request->action == Config::get('constant.ADD_MONEY_ACTION')) ? trans('apimessages.add_money_request_not_found') : trans('apimessages.withdraw_money_request_not_found'),
                ], 200);
            }

            // Money request already proceed or invalid
            if ($addOrWithdrawMoneyRequest->otp == null || $addOrWithdrawMoneyRequest->otp_created_at == null) {
                return response()->json([
                    'status' => 0,
                    'message' => ($request->action == Config::get('constant.ADD_MONEY_ACTION')) ? trans('apimessages.add_money_request_already_proceed_or_not_possible') : trans('apimessages.withdraw_money_request_already_proceed_or_not_possible'),
                ], 200);
            }

            $secondsSinceAddOrWithdrawMoneyRequestOtpCreated = Carbon::now()->diffInSeconds($addOrWithdrawMoneyRequest->otp_created_date);

            if ($secondsSinceAddOrWithdrawMoneyRequestOtpCreated > $this->otpExpireSeconds) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.otp_expired'),
                ], 200);
            }

            DB::beginTransaction();
            // Check sender balance
            if ($request->action == Config::get('constant.ADD_MONEY_ACTION')) {
                // Here current logined admin is as a sender
                $senderUser = $request->user();
                $senderDetail = $request->user()->userDetail()->lockForUpdate()->first();

                $user = $receiverUser = User::where('mobile_number', $request->mobile_number)->first();
                $receiverDetail = $receiverUser->userDetail()->lockForUpdate()->first();
                $agentBalanceAfterSuccess = $receiverDetail->balance_amount + $addOrWithdrawMoneyRequest->amount;
                $insufficientBalanceErrorMsg = trans('apimessages.insufficient_balance_msg');
            } else {
                // Here current logined admin is as a receiver
                $receiverUser = $request->user();
                $receiverDetail = $request->user()->userDetail()->lockForUpdate()->first();

                $user = $senderUser = User::where('mobile_number', $request->mobile_number)->first();
                $senderDetail = $senderUser->userDetail()->lockForUpdate()->first();
                $agentBalanceAfterSuccess = $senderDetail->balance_amount - $addOrWithdrawMoneyRequest->amount;
                $insufficientBalanceErrorMsg = $senderUser->full_name . trans('apimessages.insufficient_balance');
            }
            if ($senderDetail->balance_amount < $addOrWithdrawMoneyRequest->amount) {
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                    '<Mobile Number>' => $senderUser->mobile_number
                ]));
                DB::rollback();
                return response()->json([
                    'status' => 0,
                    'message' => $insufficientBalanceErrorMsg,
                ], 200);
            }

            // Add amount to receiver balance
            $receiverDetail->balance_amount += $addOrWithdrawMoneyRequest->amount;
            $receiverDetail->save();

            // Deduct from sender balance
            $senderDetail->balance_amount -= $addOrWithdrawMoneyRequest->amount;
            $senderDetail->save();

            // Save transaction history
            $transaction = new UserTransaction([
                'amount' => $addOrWithdrawMoneyRequest->amount,
                'net_amount' => $addOrWithdrawMoneyRequest->amount,
                'description' => $addOrWithdrawMoneyRequest->description,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.SUCCESS_TRANSACTION_STATUS'),
                'transaction_type' => ($request->action == Config::get('constant.ADD_MONEY_ACTION')) ? Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') : Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE'),
                'created_by' => $request->user()->id,
                'approved_by' => $request->user()->id,
                'approved_at' => Carbon::now()
            ]);

            $user->senderTransaction()->save($transaction);
            Log::channel('transaction')->info(strtr(trans('log_messages.add_or_withdraw_money'),[
                '<From Mobile Number>' => $senderUser->mobile_number,
                '<To Mobile Number>' => $receiverUser->mobile_number,
                '<Action>' => $request->action,
                '<Amount>' => $addOrWithdrawMoneyRequest->amount
            ]));

            /**
             * @added on 23rd July, 2018
             * Following code is injected to create audit transaction for each user.
             * 
             * @Code injection started.
             */
            $auditTransaction = new AuditTransaction();
            $auditTransactionRecord = [
                'transaction_type_id' => ($request->action == Config::get('constant.ADD_MONEY_ACTION')) ? Config::get('constant.AUDIT_TRANSACTION_TYPE_ADD_MONEY') : Config::get('constant.AUDIT_TRANSACTION_TYPE_WITHDRAW_MONEY'),
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

            if ($request->action == Config::get('constant.ADD_MONEY_ACTION')) {
                $userDetail = $request->user()->with('userDetail')->where('id', $request->user()->id)->first();
            } else {
                $userDetail = $user->with('userDetail')->where('id', $user->id)->first();
            }
            $addOrWithdrawMoneyRequest->delete();
            DB::commit();
            
            // Transfer alert to receiver
            $agentUser = User::where('mobile_number', $request->mobile_number)->first();
            $agentNotificationTemplate = trans('apimessages.add_money_by_admin_request_agent_notification');
            $agentMailSubject = trans('mail.add_money_request_to_admin_subject');
            if ($request->action == "withdraw") {
                $agentMailSubject = trans('mail.withdraw_money_request_to_admin_subject');
                $agentNotificationTemplate = trans('apimessages.withdraw_money_by_admin_request_agent_notification');
            }
            $agentMsg = strtr($agentNotificationTemplate, [
                '<Value>' => number_format($addOrWithdrawMoneyRequest->amount, 2),
                '<Transaction ID>' => $transaction->transaction_id,
                '<Agent Current Balance>' => $agentBalanceAfterSuccess,
            ]);
            $transactionMsgToAgent = Helpers::sendMessage($agentUser->mobile_number, $agentMsg);
            // Save transaction message to notification table for agent
            Helpers::saveNotificationMessage($agentUser, $agentMsg);

            if( !empty($agentUser->email) ){
                Mail::send('emails.addandWithdrawMoneyRequest', ['content' => $agentMsg, 'name' => $agentUser->full_name], function($message) use ($agentUser,$agentMailSubject) {
                    $message->to($agentUser->email);
                    $message->subject($agentMailSubject);
                });
            }

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'created_at' => date_format($transaction->created_at, 'Y-m-d H:i:s'),
                    'from_user_full_name' => ($request->action == Config::get('constant.ADD_MONEY_ACTION') ? $request->user()->full_name : $user->full_name),
                    'to_user_full_name' => ($request->action == Config::get('constant.ADD_MONEY_ACTION') ? $user->full_name : $request->user()->full_name),
                    'action' => $request->action,
                    'amount' => $userDetail->userDetail->country_code . ' ' . $transaction->amount,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg')
            ], 500);
        }
    }

    /**
     * Update add / withdraw request
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $rule = [
                'id' => 'required|integer|min:1',
                'amount' => 'required|numeric|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/',
                'transaction_status' => ['required', Rule::in([Config::get('constant.PENDING_TRANSACTION_STATUS'), Config::get('constant.SUCCESS_TRANSACTION_STATUS'), Config::get('constant.FAILED_TRANSACTION_STATUS'), Config::get('constant.REJECTED_TRANSACTION_STATUS')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $transaction = UserTransaction::whereIn('transaction_type', [Config::get('constant.ADD_MONEY_TRANSACTION_TYPE'), Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE'), Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE')])->where('id', $request->id)->first();

            if ($transaction === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.empty_data_msg'),
                ], 200);
            }

            // If transaction already processed
            if ($transaction->transaction_status == Config::get('constant.SUCCESS_TRANSACTION_STATUS')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.transaction_already_done'),
                ], 200);
            }

            DB::beginTransaction();
            // Check sender balance
            if ($transaction->transaction_type == Config::get('constant.ADD_MONEY_TRANSACTION_TYPE')) {
                // Here current logined admin is as a sender
                $senderUser = $request->user();
                $senderDetail = $request->user()->userDetail()->lockForUpdate()->first();

                // Here Agent is receiver
                $agentUser = $receiverUser = User::find($transaction->from_user_id);
                $receiverDetail = $receiverUser->userDetail()->lockForUpdate()->first();

                $transactionMessageAlert = trans('apimessages.NOTIFICATION_MESSAGE_TO_AGENT_AFTER_ADD_REQUEST_APPROVED_BY_ADMIN');
                $rejectMessageAlert = trans('apimessages.NOTIFICATION_MESSAGE_TO_AGENT_AFTER_ADD_REQUEST_REJECTED_BY_ADMIN');
                $transactionMessageToNumber = $receiverUser->mobile_number;
                
                $insufficientBalanceErrorMsg = trans('apimessages.insufficient_balance_msg');

                // Agent balance after add money (If admin approve request)
                $agentBalance = $receiverDetail->balance_amount + $request->amount;
                $agentBalanceIfReject = $receiverDetail->balance_amount;
            } else {
                // Here current logined admin is as a receiver
                $receiverUser = $request->user();
                $receiverDetail = $request->user()->userDetail()->lockForUpdate()->first();

                // Here agent user is as a sender
                $agentUser = $senderUser = User::find($transaction->from_user_id);
                $senderDetail = $senderUser->userDetail()->lockForUpdate()->first();

                $transactionMessageAlert = trans('apimessages.NOTIFICATION_MESSAGE_TO_AGENT_AFTER_WITHDARW_REQUEST_APPROVED_BY_ADMIN');
                $rejectMessageAlert = trans('apimessages.NOTIFICATION_MESSAGE_TO_AGENT_AFTER_WITHDARW_REQUEST_REJECTED_BY_ADMIN');
                $transactionMessageToNumber = $senderUser->mobile_number;

                // Insufficient wallet balance
                $insufficientBalanceErrorMsg = strtr(trans('apimessages.INSUFFICIENT_WALLET_BALANCE_MESSAGE_OF_AGENT'), [
                    '<Sender User>' => $senderUser->full_name,
                ]);

                // Agent balance after withdraw money (If admin approve request)
                $agentBalance = $senderDetail->balance_amount - $request->amount;
                $agentBalanceIfReject = $senderDetail->balance_amount;

                if ($transaction->transaction_type == Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE')) {
                    $transactionMessageAlert = trans('apimessages.NOTIFICATION_MESSAGE_TO_AGENT_AFTER_WITHDARW_COMMISSION_REQUEST_APPROVED_BY_ADMIN');
                    $rejectMessageAlert = trans('apimessages.NOTIFICATION_MESSAGE_TO_AGENT_AFTER_WITHDARW_COMMISSION_REQUEST_REJECTED_BY_ADMIN');
                    // Insufficient commission wallet balance
                    $insufficientBalanceErrorMsg = strtr(trans('apimessages.INSUFFICIENT_COMMISSION_WALLET_BALANCE_MESSAGE_OF_AGENT'), [
                        '<Sender User>' => $senderUser->full_name,
                    ]);
                    // Agent balance after withdraw commission money (If admin approve request)
                    $agentBalance = $senderDetail->balance_amount;
                    $agentBalanceIfReject = $senderDetail->balance_amount;
                }
            }

            // Insufficient commission wallet balance of agent
            if ($transaction->transaction_type == Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE')) {
                if ($senderDetail->commission_wallet_balance < $request->amount) {
                    DB::rollback();
                    Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                        '<Mobile Number>' => $senderUser->mobile_number
                    ]));
                    return response()->json([
                        'status' => 0,
                        'message' => $insufficientBalanceErrorMsg,
                    ], 200);
                }
            } else { // Insufficient wallet balance of sender
                if ($senderDetail->balance_amount < $request->amount) {
                    DB::rollback();
                    Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                        '<Mobile Number>' => $senderUser->mobile_number
                    ]));
                    return response()->json([
                        'status' => 0,
                        'message' => $insufficientBalanceErrorMsg,
                    ], 200);
                }
            }
            
            // Transaction history data to update
            $data = [
                'amount' => $request->amount,
                'net_amount' => $request->amount,
                'description' => (isset($request->description) && $request->description != null) ? $request->description : null,
                'transaction_status' => $request->transaction_status,
            ];

            // If admin approve request
            if ($request->transaction_status == Config::get('constant.SUCCESS_TRANSACTION_STATUS')) {

                // Add amount to receiver balance
                $receiverDetail->balance_amount += $request->amount;
                $receiverDetail->save();

                // Deduct from sender balance
                if ($transaction->transaction_type == Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE')) { // If withdraw from commission wallet request to admin
                    $senderDetail->commission_wallet_balance -= $request->amount;
                } else {
                    $senderDetail->balance_amount -= $request->amount;
                }
                $senderDetail->save();

                $data['approved_by'] = $request->user()->id;
                $data['approved_at'] = Carbon::now();
            } else if ($request->transaction_status == Config::get('constant.REJECTED_TRANSACTION_STATUS')) { // If admin reject request
                $data['rejected_by'] = $request->user()->id;
                $data['rejected_at'] = Carbon::now();
            }
            // Update transaction history
            $transaction->fill(array_filter($data));
            $transaction->save();
            Log::channel('transaction')->info(strtr(trans('log_messages.add_or_withdraw'),[
                '<Uesr>' => $request->user()->mobile_number,
                '<Transaction Id>' => $transaction->transaction_id,
                '<Action>' => $request->transaction_status,
                '<Amount>' => $request->amount
            ]));

            /**
             * @added on 3rd August, 2018
             * Following code is injected to create audit transaction for each user.
             * 
             * @Code injection started.
             */
            $auditTransaction = new AuditTransaction();
            $transactionTypeId = Config::get('constant.ADD_MONEY_TRANSACTION_TYPE');
            switch ($transaction->transaction_type) {
                case Config::get('constant.ADD_MONEY_TRANSACTION_TYPE'):
                    $transactionTypeId = Config::get('constant.AUDIT_TRANSACTION_TYPE_ADD_MONEY');
                    break;
                case Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE'):
                    $transactionTypeId = Config::get('constant.AUDIT_TRANSACTION_TYPE_WITHDRAW_MONEY');
                    break;
                case Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE'):
                    $transactionTypeId = Config::get('constant.AUDIT_TRANSACTION_TYPE_WITHDRAW_MONEY_FROM_COMMISSION');
                    break;
            }

            $auditTransactionRecord = [
                'transaction_type_id' => $transactionTypeId,
                'transaction_date' => Carbon::now(),
                'transaction_user' => $request->user()->id,
                'action_model_id' => $transaction->id,
                'action_detail' => $transaction
            ];
            $auditTransaction->insertUpdate($auditTransactionRecord);
            /**
             * @added on 3rd August, 2018
             * @Code injection ended.
             */

            // Send message to agent after request approved by admin
            if ($request->transaction_status == Config::get('constant.SUCCESS_TRANSACTION_STATUS')) {
                $message = strtr($transactionMessageAlert, [
                    '<Value>' => number_format($transaction->amount, 2),
                    '<Transaction ID>' => $transaction->transaction_id,
                    '<Agent Current Balance>' => number_format($agentBalance, 2),
                ]);
                Helpers::sendMessage($transactionMessageToNumber, $message);
                // Save transaction message to notification table for agent
                Helpers::saveNotificationMessage($agentUser, $message);
            } else if($request->transaction_status == Config::get('constant.REJECTED_TRANSACTION_STATUS')){
                $message = strtr($rejectMessageAlert, [
                    '<Value>' => number_format($transaction->amount, 2),
                    '<Transaction ID>' => $transaction->transaction_id,
                    '<Agent Current Balance>' => number_format($agentBalanceIfReject, 2),
                ]);
                Helpers::sendMessage($transactionMessageToNumber, $message);
                // Save transaction message to notification table for agent
                Helpers::saveNotificationMessage($agentUser, $message);
            }

            DB::commit();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'data' => $transaction
                ]
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

    public function destroy(Request $request, $id)
    {
        try {
            $transaction = UserTransaction::whereIn('transaction_type', [Config::get('constant.ADD_MONEY_TRANSACTION_TYPE'), Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE')])->where('id', $id)->first();

            if ($transaction === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.empty_data_msg'),
                ], 200);
            }

            // If transaction already processed
            if ($transaction->transaction_status == Config::get('constant.SUCCESS_TRANSACTION_STATUS')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.transaction_already_done'),
                ], 200);
            }

            // If transaction rejected before
            if ($transaction->transaction_status == Config::get('constant.REJECTED_TRANSACTION_STATUS')) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.TRANSACTION_ALREADY_REJECTED_MESSAGE'),
                ], 200);
            }
            
            // Reject add / withdraw request
            $transaction->update([
                'rejected_by' => $request->user()->id,
                'rejected_at' => Carbon::now(),
                'transaction_status' => Config::get('constant.REJECTED_TRANSACTION_STATUS')
            ]);
            Log::info(strtr(trans('log_messages.reject_transaction'),[
                '<Transaction Type>' => Helpers::getTransactionType($transaction->transaction_type),
                '<Transaction Id>' => $transaction->transaction_id,
                '<User>' => $request->user()->mobile_number
            ]));
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.add_withdraw_request_deleted_successfully'),
            ]);
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
}