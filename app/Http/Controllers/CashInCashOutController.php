<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\TransferMoneyRequest;
use App\User;
use App\UserDetail;
use App\UserTransaction;
use CommissionService;
use Config;
use DB;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Validator;
use App\AuditTransaction;

class CashInCashOutController extends Controller
{
    /**
     * Validate unregistered mobile number for cashout
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateUnregisteredUserOTPForCashout(Request $request)
    {
        try {
            $rule = [
                'otp' => 'required',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $transferMoneyRequest = TransferMoneyRequest::where('otp', $request->otp)->whereNotNull('unregistered_number')->first();

            // Invalid Authorization Code
            if (count($transferMoneyRequest) == 0) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.INVALID_AUTHORIZATION_CODE_OF_UNREGISTERED_USER'),
                ], 200);
            }

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'amount' => $transferMoneyRequest->amount,
                    'receiver_mobile_number' => $transferMoneyRequest->unregistered_number,
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
     * Cashout to unregistered mobile number with otp and amount verification
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cashoutToUnregisteredUser(Request $request)
    {
        try {
            $rule = [
                'amount' => 'required|numeric|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/',
                'otp' => 'required',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $transferMoneyRequest = TransferMoneyRequest::where('otp', $request->otp)->whereNotNull('unregistered_number')->first();

            // Request not found with this unregistered mobile number
            if ($transferMoneyRequest === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.no_transfer_money_request_with_this_unregistered_number_or_otp_mismatch'),
                ], 200);
            }

            // Amount with request is mismatch
            if ($transferMoneyRequest->amount != $request->amount) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.amount_mismatch_of_cashout_to_unregistered_user'),
                ], 200);
            }

            DB::beginTransaction();
            // Add amount to agent balance
            $agentDetail = $request->user()->userDetail()->lockForUpdate()->first();
            $agentDetail->balance_amount += $transferMoneyRequest->amount;
            $agentDetail->save();

            // Here sender user is guest user
            $guestUser = User::find($transferMoneyRequest->to_user_id);

            // Save transaction history
            $transaction = new UserTransaction([
                'to_user_id' => $guestUser->id,
                'amount' => $transferMoneyRequest->amount,
                'description' => $transferMoneyRequest->description,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.SUCCESS_TRANSACTION_STATUS'),
                'transaction_type' => Config::get('constant.CASH_OUT_TRANSACTION_TYPE'),
                'created_by' => $request->user()->id,
            ]);
            $request->user()->senderTransaction()->save($transaction);
            Log::channel('transaction')->info(strtr(trans('log_messages.cash'),[
                '<Action>' => 'Cash In',
                '<Amount>' => $transferMoneyRequest->amount,
                '<To User>' => $guestUser->mobile_number,
                '<By User>' => $request->user()->mobile_number
            ]));

            /**
             * @added on 23rd July, 2018
             * Following code is injected to create audit transaction for each user.
             * 
             * @Code injection started.
             */
            $auditTransaction = new AuditTransaction();
            $auditTransactionRecord = [
                'transaction_type_id' => Config::get('constant.AUDIT_TRANSACTION_TYPE_CASH_OUT'),
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

            // Message to sender user (Registered user)
            $senderMsg = strtr(trans('apimessages.SEND_MESSAGE_TO_SENDER_USER_AFTER_UNREGISTERED_USER_CASHOUT'), [
                '<Guest User Mobile Number>' => Helpers::maskString($guestUser->mobile_number),
                '<Value>' => number_format($transferMoneyRequest->amount, 2),
                '<Agent Name>' => $request->user()->full_name,
                '<Agent Mobile Number>' => Helpers::maskString($request->user()->mobile_number),
                '<Authorization ID>' => $transferMoneyRequest->otp,
                '<Transaction ID>' => $transaction->transaction_id,
            ]);
            $transactionMsgToSender = Helpers::sendMessage($transferMoneyRequest->fromUser->mobile_number, $senderMsg);
            // Save transaction message to notification table for sender user (Registered user)
            Helpers::saveNotificationMessage($transferMoneyRequest->fromUser, $senderMsg);

            // Message to un-registered user (Un-Registered user)
            $unregisteredUserMsg = strtr(trans('apimessages.SEND_MESSAGE_TO_UNREGISTERED_USER_AFTER_SUCCESSFUL_CASHOUT'), [
                '<Value>' => number_format($transferMoneyRequest->amount, 2),
                '<Agent Name>' => $request->user()->full_name,
                '<Agent Mobile Number>' => Helpers::maskString($request->user()->mobile_number),
                '<Sender Name>' => $transferMoneyRequest->fromUser->full_name,
                '<Sender Mobile Number>' => Helpers::maskString($transferMoneyRequest->fromUser->mobile_number),
                '<Authorization Code>' => $request->otp,
                '<Transaction ID>' => $transaction->transaction_id,
            ]);
            $transactionMsgToUnregisteredUser = Helpers::sendMessage($guestUser->mobile_number, $unregisteredUserMsg);
            // Save transaction message to notification table for un-registered user (Un-Registered user)
            Helpers::saveNotificationMessage($guestUser, $unregisteredUserMsg);

            // Message to agent user
            $agentMsg = strtr(trans('apimessages.AGENT_MESSAGE_AFTER_CASHOUT_FROM_UNREGISTERED_USER'), [
                '<Value>' => number_format($transferMoneyRequest->amount, 2),
                '<Authorization Code>' => $transferMoneyRequest->otp,
                '<Guest User Mobile Number>' => Helpers::maskString($guestUser->mobile_number),
                '<Agent Balance Amount>' => $agentDetail->balance_amount,
                '<Transaction ID>' => $transaction->transaction_id,
            ]);
            $transactionMsgToAgent = Helpers::sendMessage($request->user()->mobile_number, $agentMsg);
            // Save transaction message to notification table for agent user
            Helpers::saveNotificationMessage($request->user(), $agentMsg);

            // Delete processed request
            $transferMoneyRequest->delete();

            DB::commit();
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'created_at' => date_format($transaction->created_at, "Y-m-d H:i:s"),
                    'amount' => $transferMoneyRequest->amount,
                    'full_name' => '',
                    'mobile_number' => $guestUser->mobile_number,
                    'action' => Config::get('constant.CASH_OUT_ACTION'),
                    'agent_wallet_amount' => $agentDetail->balance_amount,
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
     * Calculate commission for cash-in / cash-out transaction
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function agentCashInCashOutCommission (Request $request) {
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
            } else {
                $senderUser = User::where('mobile_number', $request->mobile_number)->first();
                $senderDetail = $senderUser->userDetail()->first();
                // Insufficient message for user
                $insufficientBalanceErrorMsg = strtr(trans('apimessages.INSUFFICIENT_BALANCE_MESSAGE_FOR_CASHOUT'), [
                    '<Sender User>' => $senderUser->full_name,
                ]);
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

            // Get commission data
            $commissionData = CommissionService::calculateCommission($request->amount, $request->user()->id);

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
                    'receiver_full_name' => ($request->action == Config::get('constant.CASH_IN_ACTION')) ? $receiverUser->full_name : $senderUser->full_name,
                    'receiver_mobile_number' => ($request->action == Config::get('constant.CASH_IN_ACTION')) ? $receiverUser->mobile_number : $senderUser->mobile_number,
                    'total_amount' => number_format($request->amount, 2, '.', ''),
                    'amount' => number_format($request->amount, 2, '.', ''),
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
}
