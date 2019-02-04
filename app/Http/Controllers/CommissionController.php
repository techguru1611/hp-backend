<?php

namespace App\Http\Controllers;

use App\User;
use App\UserDetail;
use App\UserTransaction;
use Config;
use DB;
use Helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Validator;
use App\AuditTransaction;

class CommissionController extends Controller
{

    /**
     * To get commission wallet balance of agent for add / withdraw commission balance
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalance(Request $request)
    {
        try {
            $rule = [
                'action' => ['required', Rule::in([Config::get('constant.ADD_COMMISSION_TO_WALLET_ACTION'), Config::get('constant.WITHDARW_COMMISSION_FROM_WALLET_ACTION')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $userDetail = $request->user()->userDetail()->first();

            // Data not found
            if ($userDetail === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.DO_NOT_ABLE_TO_FIND_USER_DETAIL'),
                ], 200);
            }

            // Do not have commission wallet balance
            if ($userDetail->commission_wallet_balance <= 0) {
                return response()->json([
                    'status' => 0,
                    'message' => ($request->action == Config::get('constant.ADD_COMMISSION_TO_WALLET_ACTION')) ? trans('apimessages.YOU_DO_NOT_HAVE_COMMISSION_BALANCE_TO_ADD') : trans('apimessages.YOU_DO_NOT_HAVE_COMMISSION_BALANCE_TO_WITHDRAW'),
                ], 200);
            }

            $messageText = ($request->action == Config::get('constant.ADD_COMMISSION_TO_WALLET_ACTION')) ? trans('apimessages.GET_COMMISSION_AMOUNT_TO_ADD_INTO_WALLET_MESSAGE') : trans('apimessages.GET_COMMISSION_AMOUNT_TO_WITHDTRAW_MESSAGE');
            $message = strtr($messageText, [
                '<Commission Wallet Balance>' => number_format($userDetail->commission_wallet_balance, 2),
            ]);

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => $message,
                'data' => [],
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
     * To Add commission wallet balance into wallet balance
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToWallet(Request $request)
    {
        try {
            $rule = [
                'amount' => 'required|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            DB::beginTransaction();
            $userDetail = $request->user()->userDetail()->lockForUpdate()->first();

            // Data not found
            if ($userDetail === null) {
                DB::rollback();
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.DO_NOT_ABLE_TO_FIND_USER_DETAIL'),
                ], 200);
            }

            // Do not have commission wallet balance
            if ($userDetail->commission_wallet_balance < $request->amount) {
                DB::rollback();
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                    '<Mobile Number>' => $request->user()->mobile_number
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.YOU_DO_NOT_HAVE_SUFFICIENT_COMMISSION_BALANCE_TO_ADD'),
                ], 200);
            }

            // Add commission wallet balance to user's wallet
            $commissionWallet = $request->amount;

            $userDetail->balance_amount += $commissionWallet;
            $userDetail->commission_wallet_balance -= $commissionWallet;
            $userDetail->save();

            // Save transaction history
            $transaction = new UserTransaction([
                'amount' => $commissionWallet,
                'net_amount' => $commissionWallet,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.SUCCESS_TRANSACTION_STATUS'),
                'transaction_type' => Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE'),
                'created_by' => $request->user()->id,
            ]);
            $request->user()->senderTransaction()->save($transaction);
            Log::channel('transaction')->info(strtr(trans('log_messages.commission'),[
                '<Action>' => 'Add to Wallet',
                '<Amount>' => $commissionWallet,
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
                'transaction_type_id' => Config::get('constant.AUDIT_TRANSACTION_TYPE_ADD_COMMISSION_TO_WALLET'),
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

            // Success message to agent
            $message = strtr(trans('apimessages.ADD_COMMISSION_TO_WALLET_SUCCESS_MESSAGE'), [
                '<Commission Wallet Balance>' => number_format($commissionWallet, 2),
            ]);

            DB::commit();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => $message,
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
     * To Add commission wallet balance into wallet balance
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function withdrawFromWallet(Request $request)
    {
        try {
            $rule = [
                'amount' => 'required|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            DB::beginTransaction();
            $userDetail = $request->user()->userDetail()->lockForUpdate()->first();

            // Data not found
            if ($userDetail === null) {
                DB::rollback();
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.DO_NOT_ABLE_TO_FIND_USER_DETAIL'),
                ], 200);
            }

            // Do not have commission wallet balance
            if ($userDetail->commission_wallet_balance < $request->amount) {
                DB::rollback();
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'),[
                    '<Mobile Number>' => $request->user()->mobile_number
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.YOU_DO_NOT_HAVE_SUFFICIENT_COMMISSION_BALANCE_TO_WITHDRAW'),
                ], 200);
            }

            // Add commission wallet balance to user's wallet
            $commissionWallet = $request->amount;

            // Save transaction history
            $transaction = new UserTransaction([
                'amount' => $commissionWallet,
                'net_amount' => $commissionWallet,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.PENDING_TRANSACTION_STATUS'),
                'transaction_type' => Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE'),
                'created_by' => $request->user()->id,
            ]);
            $request->user()->senderTransaction()->save($transaction);
            Log::channel('transaction')->info(strtr(trans('log_messages.commission'),[
                '<Action>' => 'Withdraw From Wallet',
                '<Amount>' => $commissionWallet,
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
                'transaction_type_id' => Config::get('constant.AUDIT_TRANSACTION_TYPE_WITHDRAW_MONEY_FROM_COMMISSION'),
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

            DB::commit();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.WITHDRAW_COMMISSION_FROM_WALLET_SUCCESS_MESSAGE'),
                'data' => [],
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            DB::rollback();
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * To get commission history of agent
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request)
    {
        try {
            $rule = [
                'page' => 'required|regex:/^[1-9][0-9]{0,15}$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            $userId = $request->user()->id;

            // To filter by transaction type
            $filter = (isset($request->filter) && !empty($request->filter)) ? $request->filter : '';

            // Total Count
            $totalCount = UserTransaction::agentCommissionTransactionCount($userId, $filter);

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, Config::get('constant.COMMISSION_TRANSACTION_HISTORY_PER_PAGE_LIMIT'), $totalCount);

            // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // Transaction History
            $commissionTransactionHistory = UserTransaction::agentCommissionTransaction($userId, Config::get('constant.COMMISSION_TRANSACTION_HISTORY_PER_PAGE_LIMIT'), $getPaginationData['offset'], $sort, $order, $filter);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'sign' => '+',
                'image' => url('/images/plus.png'),
                'totalCount' => $totalCount,
                'noOfPages' => $getPaginationData['noOfPages'],
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'filter' => Config::get('constant.COMMISSION_FILTER'),
                'data' => $commissionTransactionHistory,
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
