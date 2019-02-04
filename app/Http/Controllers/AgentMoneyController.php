<?php

namespace App\Http\Controllers;

use App\AgentAddOrWithdrawMoneyRequest;
use App\AuditTransaction;
use App\CashInOrOutMoneyRequest;
use App\Http\Controllers\Controller;
use App\OTPManagement;
use App\User;
use App\UserTransaction;
use Carbon\Carbon;
use CommissionService;
use Config;
use DB;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Mail;
use Validator;

class AgentMoneyController extends Controller
{

    public function __construct()
    {
        $this->objUser = new User();
        $this->otpExpireSeconds = Config::get('constant.OTP_EXPIRE_SECONDS');
        $this->resendOtpExpireSeconds = Config::get('constant.RESEND_OTP_EXPIRE_SECONDS');
    }

    /**
     * Agent cash in/out first check if this is valid mobile.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateCustomerMobileExist(Request $request)
    {
        try {
            $rule = [
                'mobile_number' => 'required',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            //$validateMobileNumber = Helpers::validateMobileNumberRoleWise($request->mobile_number, Helpers::getRoleId("user"));
            $validateMobileNumber = Helpers::validateMobileNumber($request->mobile_number, $request->user()->mobile_number);
            $action = '';
            if (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0) {
                // Transfer money to un-registered user
                // if ($validateMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) {

                //     // Get transfer request for this unregistered user
                //     $transferMoneyRequest = TransferMoneyRequest::where('unregistered_number', $request->mobile_number)->count();

                //     // Request not found with this unregistered mobile number
                //     if ($transferMoneyRequest == 0) {
                //         return response()->json([
                //             'status' => 0,
                //             'message' => trans('apimessages.no_transfer_money_request_with_this_unregistered_number'),
                //         ], 200);
                //     }
                //     $action = Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE');
                // } else {
                return response()->json([
                    'status' => 0,
                    'message' => $validateMobileNumber['message'],
                ], 200);
                // }
            }
            $userDetail = $this->objUser->where('mobile_number', $request->mobile_number)->first();

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'action' => $action,
                'data' => [
                    'full_name' => $userDetail->full_name,
                    'mobile_number' => $userDetail->mobile_number,
                    'balance_amount' => isset($userDetail->userDetail->balance_amount) ? $userDetail->userDetail->balance_amount : 0,
                    'country_code' => isset($userDetail->userDetail->country_code) ? $userDetail->userDetail->country_code : "",
                    'wallet_balance' => (isset($userDetail->userDetail->country_code) && isset($userDetail->userDetail->balance_amount)) ? $userDetail->userDetail->country_code . " " . $userDetail->userDetail->balance_amount : "",
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
     * Cash In or Out money to verified user by mobile number (one-to-one transaction)
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function agentCashInCashOutMoney(Request $request)
    {
        try {
            $rule = [
                'mobile_number' => 'required',
                'amount' => 'required|numeric|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/',
                'action' => ['required', Rule::in([Config::get('constant.CASH_IN_ACTION'), Config::get('constant.CASH_OUT_ACTION')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            // Validate customer mobile number
            $validateMobileNumber = Helpers::validateMobileNumber($request->mobile_number, $request->user()->mobile_number);

            if (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0) {
                return response()->json([
                    'status' => 0,
                    'message' => $validateMobileNumber['message'],
                ], 200);
            }

            // Check sender balance
            if ($request->action == Config::get('constant.CASH_IN_ACTION')) {
                // Here current logined agent is as a sender
                $senderUser = $request->user();
                $senderDetail = $request->user()->userDetail()->first();
                $receiverUser = User::where('mobile_number', $request->mobile_number)->first();
                $insufficientBalanceErrorMsg = trans('apimessages.insufficient_balance_msg');
                $otpMessageText = trans('apimessages.CASHIN_OTP_MESSAGE_TO_AGENT');
            } else {
                $senderUser = User::where('mobile_number', $request->mobile_number)->first();
                $senderDetail = $senderUser->userDetail()->first();
                $receiverUser = $request->user();
                // Insufficient message for user
                $insufficientBalanceErrorMsg = strtr(trans('apimessages.INSUFFICIENT_BALANCE_MESSAGE_FOR_CASHOUT'), [
                    '<Sender User>' => $senderUser->full_name,
                ]);
                $otpMessageText = trans('apimessages.CASHOUT_OTP_MESSAGE_TO_USER');
            }
            if ($senderDetail->balance_amount < $request->amount) {
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                    '<Mobile Number>' => $senderUser->mobile_number
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => $insufficientBalanceErrorMsg,
                ], 200);
            }

            DB::beginTransaction();
            // Save transaction history with not verified status

            $otp = mt_rand(100000, 999999);
            $mobileNumber = ($request->action == Config::get('constant.CASH_IN_ACTION') ? $request->user()->mobile_number : $request->mobile_number);
            $cashInOrOutMoneyRequest = new CashInOrOutMoneyRequest([
                'otp_sent_to' => $mobileNumber,
                'amount' => $request->amount,
                'action' => $request->action,
                'description' => (isset($request->description)) ? $request->description : null,
                'otp' => $otp,
                'otp_created_at' => Carbon::now(),
            ]);
            $request->user()->cashInOrOutMoneyRequest()->save($cashInOrOutMoneyRequest);

            // Send OTP message
            $message = strtr($otpMessageText, [
                '<OTP>' => $otp,
                '<Value>' => $request->amount,
                '<Receiver Name>' => $receiverUser->full_name,
                '<Receiver Mobile Number>' => Helpers::maskString($receiverUser->mobile_number),
            ]);

            $sendOtp = Helpers::sendMessage($mobileNumber, $message);
            Log::channel('otp')->info(strtr(trans('log_messages.otp_success_action'),[
                '<Mobile Number>' => $mobileNumber,
                '<Action>' => $request->action
            ]));

            $om = new OTPManagement();
            $om->otp = $otp;
            $om->from_user = $senderUser->id;
            $om->to_user = $receiverUser->id;
            $om->otp_sent_to = $mobileNumber;
            $om->amount = $request->amount;
            $om->operation = $request->action == Config::get('constant.CASH_IN_ACTION') ? Config::get('constant.OTP_O_CASH_IN_VERIFICATION') : Config::get('constant.OTP_O_CASH_OUT_VERIFICATION');
            $om->created_by = $request->user()->id;
            $om->message = $message;
            $om->save();

            if (!$sendOtp) {
                Log::channel('otp')->error(strtr(trans('log_messages.otp_error_action'),[
                    '<Mobile Number>' => $mobileNumber,
                    '<Action>' => $request->action
                ]));
                DB::rollback();
                return response()->json([
                    'status' => '0',
                    'message' => trans('apimessages.something_went_wrong'),
                ], 200);
            }
            $agentDetail = $request->user()->with('userDetail')->where('id', $request->user()->id)->first();

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

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => $responseMessage,
                'data' => [
                    'request_id' => $cashInOrOutMoneyRequest->id,
                    'otp_sent_to' => $mobileNumber,
                    'customer_mobile_number' => $request->mobile_number,
                    'action' => $request->action,
                    'wallet_balance' => (isset($agentDetail->userDetail->country_code) && isset($agentDetail->userDetail->balance_amount)) ? $agentDetail->userDetail->country_code . " " . $agentDetail->userDetail->balance_amount : "",
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
     * Verify cash in or out request otp
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyCashInOrOutOtp(Request $request)
    {
        try {
            $rule = [
                'mobile_number' => 'required',
                'otp' => 'required|integer',
                'action' => ['required', Rule::in([Config::get('constant.CASH_IN_ACTION'), Config::get('constant.CASH_OUT_ACTION')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // Get cash in or out request of agent
            $cashInOrOutRequest = $request->user()->cashInOrOutMoneyRequest()->where('otp', $request->otp)->where('action', $request->action)->first();

            // If request with this otp not found
            if ($cashInOrOutRequest === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.invalid_otp'),
                ], 200);
            }

            // Money request already proceed or invalid
            if ($cashInOrOutRequest->otp == null || $cashInOrOutRequest->otp_created_at == null) {
                return response()->json([
                    'status' => 0,
                    'message' => ($request->action == Config::get('constant.CASH_IN_ACTION')) ? trans('apimessages.cash_in_request_already_proceed_or_not_possible') : trans('apimessages.cash_out_request_already_proceed_or_not_possible'),
                ], 200);
            }

            $secondsSinceCashInOutRequestOtpCreated = Carbon::now()->diffInSeconds($cashInOrOutRequest->otp_created_date);

            if ($secondsSinceCashInOutRequestOtpCreated > $this->otpExpireSeconds) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.otp_expired'),
                ], 200);
            }

            DB::beginTransaction();
            // Check sender balance
            if ($request->action == Config::get('constant.CASH_IN_ACTION')) {
                // Here current logined agent is as a sender
                $senderUser = $request->user();
                $senderDetail = $request->user()->userDetail()->lockForUpdate()->first();

                $receiverUser = $this->objUser->where('mobile_number', $request->mobile_number)->first();
                $receiverDetail = $receiverUser->userDetail()->lockForUpdate()->first();
                $insufficientBalanceErrorMsg = trans('apimessages.insufficient_balance_msg');

            } else {
                // Here current logined agent is as a receiver
                $receiverUser = $request->user();
                $receiverDetail = $request->user()->userDetail()->lockForUpdate()->first();

                $senderUser = $this->objUser->where('mobile_number', $request->mobile_number)->first();
                $senderDetail = $senderUser->userDetail()->lockForUpdate()->first();
                // Insufficient message for user
                $insufficientBalanceErrorMsg = strtr(trans('apimessages.INSUFFICIENT_BALANCE_MESSAGE_FOR_CASHOUT'), [
                    '<Sender User>' => $senderUser->full_name,
                ]);
            }
            if ($senderDetail->balance_amount < $cashInOrOutRequest->amount) {
                DB::rollback();
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                    '<Mobile Number>' => $senderUser->mobile_number
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => $insufficientBalanceErrorMsg,
                ], 200);
            }

            // Get commission data
            $commissionData = CommissionService::calculateCommission($cashInOrOutRequest->amount, $request->user()->id);

            // Handle error
            if ($commissionData['status'] == 0) {
                return response()->json([
                    'status' => $commissionData['status'],
                    'message' => $commissionData['message'],
                ], $commissionData['code']); 
            }
            // validation for amount
            if ($commissionData['data']['totalCommission'] >= $cashInOrOutRequest->amount){
                return response()->json([
                    'status' => 0,
                    'message'=> trans('apimessages.insufficient_amount_for_commission')
                ]);
            }

            // Add amount to receiver balance
            $receiverDetail->balance_amount += $commissionData['data']['netAmount'];
            $receiverDetail->commission_wallet_balance += ($request->action == Config::get('constant.CASH_OUT_ACTION')) ? $commissionData['data']['agentCommission'] : 0;
            $receiverDetail->save();

            // Deduct from sender balance
            $senderDetail->balance_amount -= $cashInOrOutRequest->amount;
            $senderDetail->commission_wallet_balance += ($request->action == Config::get('constant.CASH_IN_ACTION')) ? $commissionData['data']['agentCommission'] : 0;
            $senderDetail->save();

            // Add commission to admin user
            $adminData = User::with('userDetail')->where('role_id', Config::get('constant.SUPER_ADMIN_ROLE_ID'))->first();
            // Upgrade commission wallet balance
            $adminData->userDetail->commission_wallet_balance += $commissionData['data']['helapayCommission'];
            $adminData->userDetail->save();
            // Save transaction history
            $transaction = new UserTransaction([
                'to_user_id' => ($request->action == Config::get('constant.CASH_IN_ACTION')) ? $receiverUser->id : $senderUser->id,
                'amount' => $commissionData['data']['amount'],
                'net_amount' => $commissionData['data']['netAmount'],
                'total_commission_amount' => $commissionData['data']['totalCommission'],
                'admin_commission_amount_from_receiver' => $commissionData['data']['helapayCommission'],
                'agent_commission_amount' => $commissionData['data']['agentCommission'],
                'agent_commission_in_percentage' => $commissionData['data']['agentCommissionPerc'],
                'commission_agent_id' => $request->user()->id,
                'receiver_commission_admin_id' => $adminData->id,
                'description' => $cashInOrOutRequest->description,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.SUCCESS_TRANSACTION_STATUS'),
                'transaction_type' => ($request->action == Config::get('constant.CASH_IN_ACTION')) ? Config::get('constant.CASH_IN_TRANSACTION_TYPE') : Config::get('constant.CASH_OUT_TRANSACTION_TYPE'),
                'created_by' => $request->user()->id,
            ]);

            $request->user()->senderTransaction()->save($transaction);
            Log::channel('transaction')->info(strtr(trans('log_messages.cashInOrOut'),[
                '<To User>' => ($request->action == Config::get('constant.CASH_IN_ACTION')) ? $receiverUser->mobile_number : $senderUser->mobile_number,
                '<By User>' => $request->user()->mobile_number,
                '<Amount>' => $cashInOrOutRequest->amount,
                '<Action>' => $request->action
            ]));

            /**
             * @added on 23rd July, 2018
             * Following code is injected to create audit transaction for each user.
             *
             * @Code injection started.
             */
            $auditTransaction = new AuditTransaction();
            $auditTransactionRecord = [
                'transaction_type_id' => ($request->action == Config::get('constant.CASH_IN_ACTION')) ? Config::get('constant.AUDIT_TRANSACTION_TYPE_CASH_IN') : Config::get('constant.AUDIT_TRANSACTION_TYPE_CASH_OUT'),
                'transaction_date' => Carbon::now(),
                'transaction_user' => $request->user()->id,
                'action_model_id' => $transaction->id,
                'action_detail' => $transaction,
            ];
            $auditTransaction->insertUpdate($auditTransactionRecord);
            /**
             * @added on 23rd July, 2018
             * @Code injection ended.
             */

            if ($request->action == Config::get('constant.CASH_IN_ACTION')) {
                $sender = $request->user()->with('userDetail')->where('id', $request->user()->id)->first();
                $transactionDetail = $transaction->with('receiveruser')->with('receiveruserdetail')->where('id', $transaction->id)->first();

                // Transfer alert to receiver
                $receiverMsg = strtr(trans('apimessages.transfer_money_to_wallet_msg_to_receiver'), [
                    '<Value>' => number_format($transaction->net_amount, 2),
                    '<Agent Name>' => $senderUser->full_name,
                    '<Agent Mobile Number>' => Helpers::maskString($senderUser->mobile_number),
                    '<Transaction ID>' => $transaction->transaction_id,
                    '<Fee>' => number_format($commissionData['data']['totalCommission'],2),
                    '<Receiver Balance Amount>' => number_format($receiverDetail->balance_amount, 2),
                ]);
                $transactionMsgToReceiver = Helpers::sendMessage($receiverUser->mobile_number, $receiverMsg);
                // Save transaction message to notification table for receiver
                Helpers::saveNotificationMessage($receiverUser, $receiverMsg);

                // Send Mail
                if ($receiverUser->email != '') {
                    $data = [];
                    $data['customerName'] = $receiverUser->full_name;
                    $data['value'] = $cashInOrOutRequest->amount;
                    $data['agentName'] = $senderUser->full_name;
                    $data['balance'] = number_format($receiverDetail->balance_amount, 2);

                    $subject = 'Transfer Money to Wallet';
                    $template = 'cashIn';

                    Helpers::sendMail($receiverUser->email, $subject, $template, $data);
                }

                // Message to agent user
                $agentMsg = strtr(trans('apimessages.agent_message_after_cashin'), [
                    '<Value>' => number_format($cashInOrOutRequest->amount, 2),
                    '<Customer Name>' => $receiverUser->full_name,
                    '<Customer Mobile Number>' => Helpers::maskString($receiverUser->mobile_number),
                    '<Transaction ID>' => $transaction->transaction_id,
                    '<Agent Current Balance>' => number_format($sender->userDetail->balance_amount, 2),
                ]);
                $transactionMsgToAgent = Helpers::sendMessage($request->user()->mobile_number, $agentMsg);
                // Save transaction message to notification table for agent
                Helpers::saveNotificationMessage($request->user(), $agentMsg);
            } else {
                $receiver = $request->user()->with('userDetail')->where('id', $request->user()->id)->first();
                $transactionDetail = $transaction->with('senderuser')->with('senderuserdetail')->where('id', $transaction->id)->first();

                // Transfer alert to sender
                $senderMsg = strtr(trans('apimessages.transfer_money_from_wallet_msg_to_receiver'), [
                    '<Value>' => number_format($transaction->net_amount, 2),
                    '<Agent Name>' => $request->user()->full_name,
                    '<Agent Mobile Number>' => Helpers::maskString($request->user()->mobile_number),
                    '<Transaction ID>' => $transaction->transaction_id,
                    '<Fee>' => number_format($commissionData['data']['totalCommission'],2),
                    '<Sender Balance Amount>' => number_format($senderDetail->balance_amount, 2),
                ]);
                $transactionMsgToSender = Helpers::sendMessage($senderUser->mobile_number, $senderMsg);
                // Save transaction message to notification table for sender
                Helpers::saveNotificationMessage($senderUser, $senderMsg);

                // Send Mail
                if ($senderUser->email != '') {
                    $data = [];
                    $data['customerName'] = $senderUser->full_name;
                    $data['value'] = $cashInOrOutRequest->amount;
                    $data['agentName'] = $request->user()->full_name;
                    $data['balance'] = number_format($senderDetail->balance_amount, 2);

                    $subject = 'Withdrawn Money from Wallet';
                    $template = 'cashOut';

                    Helpers::sendMail($senderUser->email, $subject, $template, $data);
                }

                // Message to agent user
                $agentMsg = strtr(trans('apimessages.agent_message_after_cashout'), [
                    '<Value>' => number_format($transaction->net_amount, 2),
                    '<User Name>' => $senderUser->full_name,
                    '<User Mobile Number>' => Helpers::maskString($senderUser->mobile_number),
                    '<Transaction ID>' => $transaction->transaction_id,
                    '<Agent Balance Amount>' => number_format($receiver->userDetail->balance_amount, 2),
                ]);
                $transactionMsgToUnregisteredUser = Helpers::sendMessage($request->user()->mobile_number, $agentMsg);
                // Save transaction message to notification table for agent
                Helpers::saveNotificationMessage($request->user(), $agentMsg);
            }
            $cashInOrOutRequest->delete();
            DB::commit();
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'id' => $transactionDetail->id,
                    'transaction_id' => $transactionDetail->transaction_id,
                    'created_at' => date_format($transactionDetail->created_at, "Y-m-d H:i:s"),
                    'amount' => number_format($transactionDetail->amount, 2, '.', ''),
                    'full_name' => ($request->action == Config::get('constant.CASH_IN_ACTION') ? $transactionDetail->receiveruser->full_name : $transactionDetail->senderuser->full_name),
                    'mobile_number' => ($request->action == Config::get('constant.CASH_IN_ACTION') ? $transactionDetail->receiveruser->mobile_number : $transactionDetail->senderuser->mobile_number),
                    'action' => $request->action,
                    'agent_wallet_amount' => ($request->action == Config::get('constant.CASH_IN_ACTION') ? $sender->userDetail->country_code . " " . $sender->userDetail->balance_amount : $receiver->userDetail->country_code . " " . $receiver->userDetail->balance_amount),
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
     * Add or withdraw money request from agent to admin
     * To Do: Need to update response message
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addOrWithdrawMoney(Request $request)
    {
        try {
            $rule = [
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

            $otpMessageText = trans('apimessages.ADD_MONEY_OTP_MESSAGE_TO_AGENT');
            if ($request->action == Config::get('constant.WITHDRAW_MONEY_ACTION')) {
                $userDetail = $request->user()->userDetail()->first();
                if ($userDetail->balance_amount < $request->amount) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.insufficient_balance_msg'),
                    ], 200);
                }
                $otpMessageText = trans('apimessages.WITHDRAW_MONEY_OTP_MESSAGE_TO_AGENT');
            }

            DB::beginTransaction();
            // Save transaction history with not verified status

            $otp = mt_rand(100000, 999999);
            $addOrWithdrawMoneyRequest = new AgentAddOrWithdrawMoneyRequest([
                'otp_sent_to' => $request->user()->mobile_number,
                'amount' => $request->amount,
                'action' => $request->action,
                'otp' => $otp,
                'otp_created_at' => Carbon::now(),
            ]);
            $request->user()->addMoneyRequest()->save($addOrWithdrawMoneyRequest);

            // Send OTP to agent
            $message = strtr($otpMessageText, [
                '<OTP>' => $otp,
                '<Value>' => $request->amount,
            ]);

            $sendOtp = Helpers::sendMessage($request->user()->mobile_number, $message);
            Log::channel('otp')->info(strtr(trans('log_messages.otp_success_action'),[
                '<Mobile Number>' => $request->user()->mobile_number,
                '<Action>' => $request->action
            ]));

            $om = new OTPManagement();
            $om->otp = $otp;
            $om->from_user = $request->action == Config::get('constant.WITHDRAW_MONEY_ACTION') ? $request->user()->id : null;
            $om->to_user = $request->action == Config::get('constant.ADD_MONEY_ACTION') ? $request->user()->id : null;
            $om->otp_sent_to = $request->user()->mobile_number;
            $om->amount = $request->amount;
            $om->operation = $request->action == Config::get('constant.ADD_MONEY_ACTION') ? Config::get('constant.OTP_O_ADD_MONEY_VERIFICATION') : Config::get('constant.OTP_O_WITHDRAW_MONEY_VERIFICATION');
            $om->created_by = $request->user()->id;
            $om->message = $message;
            $om->save();

            if (!$sendOtp) {
                DB::rollback();
                Log::channel('otp')->error(strtr(trans('log_messages.otp_error_action'),[
                    '<Mobile Number>' => $request->user()->mobile_number,
                    '<Action>' => $request->action
                ]));
                return response()->json([
                    'status' => '0',
                    'message' => trans('apimessages.something_went_wrong'),
                ], 200);
            }
            $agentDetail = $addOrWithdrawMoneyRequest->user()->with('userDetail')->first();
            $agentDetail->action = $request->action;
            $agentDetail->request_id = $addOrWithdrawMoneyRequest->id;

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
                'status' => '1',
                'message' => $responseMessage,
                'data' => [
                    'agentDetail' => $agentDetail,
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
     * Verify add or withdraw money request otp and send mail to admin
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyAddOrWithdrawMoneyOtp(Request $request)
    {
        try {
            $rule = [
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

            // Get add money request of agent
            $addOrWithdrawMoneyRequest = $request->user()->addMoneyRequest()->where('otp', $request->otp)->where('action', $request->action)->first();

            // If agent with this otp not found
            if ($addOrWithdrawMoneyRequest === null) {
                return response()->json([
                    'status' => 0,
                    'message' => ($request->action == Config::get('constant.ADD_MONEY_ACTION')) ? trans('apimessages.add_money_request_not_found') : trans('apimessages.withdraw_money_request_not_found'),
                ], 200);
            }

            // If not have sufficient balanace
            if ($request->action == Config::get('constant.WITHDRAW_MONEY_ACTION')) {
                $userDetail = $request->user()->userDetail()->first();
                if ($userDetail->balance_amount < $addOrWithdrawMoneyRequest->amount) {
                    Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                        '<Mobile Number>' => $request->user()->mobile_number
                    ]));
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.insufficient_balance_msg'),
                    ], 200);
                }
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
            // Save transaction history
            $transaction = new UserTransaction([
                'amount' => $addOrWithdrawMoneyRequest->amount,
                'net_amount' => $addOrWithdrawMoneyRequest->amount,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.PENDING_TRANSACTION_STATUS'),
                'transaction_type' => ($request->action == Config::get('constant.ADD_MONEY_ACTION')) ? Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') : Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE'),
                'created_by' => $request->user()->id,
            ]);
            $request->user()->senderTransaction()->save($transaction);
            Log::channel('transaction')->info(strtr(trans('log_messages.add_or_withdraw'),[
                '<Action>' => $request->action,
                '<Amount>' => $addOrWithdrawMoneyRequest->amount,
                '<Transaction Id>' => $transaction->transaction_id,
                '<User>' => $request->user()->mobile_number
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
                'action_detail' => $transaction,
            ];
            $auditTransaction->insertUpdate($auditTransactionRecord);
            /**
             * @added on 23rd July, 2018
             * @Code injection ended.
             */

            // Delete record from add_money_request
            $addOrWithdrawMoneyRequest->delete();

            // Send request mail to admin from agent
            $user = $request->user()->with('userDetail')->first();
            $data = [
                'user' => $user,
            ];
            if (isset($user->email) && !empty($user->email)) {
                Mail::send('emails.addMoneyRequest', $data, function ($message) use ($user) {
                    $message->from($user->email, $user->full_name);
                    $message->to(env('MAIL_FROM_ADDRESS'));
                    $message->subject(trans('mail.add_money_request_to_admin_subject'));
                });
            }
            DB::commit();

            // Transaction alert to receiver
            $agentUser = $request->user();
            $agentNotificationTemplate = trans('apimessages.add_money_request_agent_notification');
            $agentMailSubject = trans('mail.add_money_request_to_admin_subject');
            if ($request->action == "withdraw") {
                $agentMailSubject = trans('mail.withdraw_money_request_to_admin_subject');
                $agentNotificationTemplate = trans('apimessages.withdraw_money_request_agent_notification');
            }
            $agentMsg = strtr($agentNotificationTemplate, [
                '<Value>' => number_format($addOrWithdrawMoneyRequest->amount, 2),
                '<Transaction ID>' => $transaction->transaction_id,
                '<Agent Current Balance>' => $user->userDetail->balance_amount,
            ]);
            $transactionMsgToAgent = Helpers::sendMessage($agentUser->mobile_number, $agentMsg);

            // Save transaction message to notification table for receiver
            Helpers::saveNotificationMessage($agentUser, $agentMsg);

            if (!empty($agentUser->email)) {
                Mail::send('emails.addandWithdrawMoneyRequest', ['content' => $agentMsg, 'name' => $agentUser->full_name], function ($message) use ($agentUser, $agentMailSubject) {
                    $message->to($agentUser->email);
                    $message->subject($agentMailSubject);
                });
            }

            // Transaction alert to admin
            $admin = User::where('role_id', 1)->first();
            $adminNotificationTemplate = trans('apimessages.add_money_request_admin_notification');
            if (isset($request->action) && $request->action == "withdraw") {
                $adminNotificationTemplate = trans('apimessages.withdraw_money_request_admin_notification');
            }
            $adminMsg = strtr($adminNotificationTemplate, [
                '<Agent Name>' => $agentUser->full_name,
                '<value>' => $addOrWithdrawMoneyRequest->amount,
                '<request id>' => $transaction->transaction_id,
            ]);
            $transactionMsgToAdmin = Helpers::sendMessage($admin->mobile_number, $adminMsg);

            // Save transaction message to notification table for admin
            Helpers::saveNotificationMessage($admin, $adminMsg);

            if (!empty($agentUser->email)) {
                Mail::send('emails.addandWithdrawMoneyRequest', ['content' => $adminMsg, 'name' => 'Admin'], function ($message) use ($agentUser, $agentMailSubject) {
                    $message->from($agentUser->email, $agentUser->full_name);
                    $message->to(env('MAIL_FROM_ADDRESS'));
                    $message->subject($agentMailSubject);
                });
            }

            $transactionDetail = $transaction->with('senderuser')->with('senderuserdetail')->where('id', $transaction->id)->first();
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $transactionDetail,
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
}
