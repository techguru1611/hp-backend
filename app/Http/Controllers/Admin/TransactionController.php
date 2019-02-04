<?php

namespace App\Http\Controllers\Admin;

use App\AuditTransaction;
use App\Http\Controllers\Controller;
use App\UserTransaction;
use Config;
use DB;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Validator;

class TransactionController extends Controller
{
    /**
     * To get Transfer money transaction by admin for users
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transferMoneyHistory(Request $request)
    {
        try {
            $rule = [
                'page' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // Total Count parameter
            $totalCount = UserTransaction::where('transaction_type', Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE'))
                ->where('created_by', $request->user()->id)
                ->count();

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, Config::get('constant.TRANSFER_MONEY_TRANSACTION_LIMIT'), $totalCount);

            // Transfer Money History by Admin
            $requestData = UserTransaction::join('users AS from_user', 'from_user.id', '=', 'user_transactions.from_user_id')
                ->leftJoin('users AS to_user', 'to_user.id', '=', 'user_transactions.to_user_id')
                ->where('created_by', $request->user()->id)
                ->where('transaction_type', Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE'))
                ->orderBy($sort, $order)
                ->orderBy('id', 'DESC')
                ->take(Config::get('constant.TRANSFER_MONEY_TRANSACTION_LIMIT'))
                ->offset($getPaginationData['offset'])
                ->get([
                    'user_transactions.id',
                    'user_transactions.transaction_id',
                    'user_transactions.amount',
                    'user_transactions.description',
                    DB::raw('CASE WHEN from_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN from_user.mobile_number ELSE from_user.full_name END as from_user_name'),
                    DB::raw('CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN to_user.mobile_number ELSE to_user.full_name END as to_user_name'),
                    DB::raw('CASE WHEN user_transactions.transaction_status=' . Config::get('constant.PENDING_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.PENDING_TRANSACTION') . '" WHEN user_transactions.transaction_status=' . Config::get('constant.SUCCESS_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.SUCCESS_TRANSACTION') . '" ELSE "' . Config::get('constant.FAILED_TRANSACTION') . '" END as transaction_status'),
                ]);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => $totalCount,
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'noOfPages' => $getPaginationData['noOfPages'],
                'data' => $requestData,
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
     * To get Add / Withdraw money transaction by admin for users
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addWithdrawMoneyHistory(Request $request)
    {
        try {
            $rule = [
                'page' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // Total Count parameter
            $totalCount = UserTransaction::whereIn('transaction_type', [Config::get('constant.ADD_MONEY_TRANSACTION_TYPE'), Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE')])
                ->where('created_by', $request->user()->id)
                ->where('from_user_id', '<>', $request->user()->id)
                ->count();

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, Config::get('constant.ADD_WITHDRAW_MONEY_TRANSACTION_LIMIT'), $totalCount);

            // Add / Withdarw Money History by Admin
            $requestData = UserTransaction::join('users AS from_user', 'from_user.id', '=', 'user_transactions.from_user_id')
                ->leftJoin('users AS to_user', 'to_user.id', '=', 'user_transactions.to_user_id')
                ->where('created_by', $request->user()->id)
                ->where('from_user_id', '<>', $request->user()->id)
                ->whereIn('transaction_type', [Config::get('constant.ADD_MONEY_TRANSACTION_TYPE'), Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE')])
                ->orderBy($sort, $order)
                ->orderBy('id', 'DESC')
                ->take(Config::get('constant.ADD_WITHDRAW_MONEY_TRANSACTION_LIMIT'))
                ->offset($getPaginationData['offset'])
                ->get([
                    'user_transactions.id',
                    'user_transactions.transaction_id',
                    'user_transactions.amount',
                    'user_transactions.description',
                    'user_transactions.created_at',
                    DB::raw('CASE WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" ELSE CASE WHEN from_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN from_user.mobile_number ELSE from_user.full_name END END AS from_user_name'),
                    DB::raw('CASE WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN CASE WHEN from_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN from_user.mobile_number ELSE from_user.full_name END ELSE "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" END AS to_user_name'),
                    DB::raw('CASE WHEN user_transactions.transaction_status=' . Config::get('constant.PENDING_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.PENDING_TRANSACTION') . '" WHEN user_transactions.transaction_status=' . Config::get('constant.SUCCESS_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.SUCCESS_TRANSACTION') . '" ELSE "' . Config::get('constant.FAILED_TRANSACTION') . '" END as transaction_status'),
                    DB::raw('CASE WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.ADD_MONEY') . '" ELSE "' . Config::get('constant.MONEY_WITHDRAW') . '" END as transaction_type'),
                ]);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => $totalCount,
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'noOfPages' => $getPaginationData['noOfPages'],
                'data' => $requestData,
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
     * To get audit transaction by admin for users
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function allAuditTransactionHistory(Request $request)
    {
        try {
            $rule = [
                'page' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 400);
            }

            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // Total Count parameter
            $totalCount = AuditTransaction::where('modified_by', $request->user()->id)
                ->count();

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, Config::get('constant.AUDIT_TRANSACTION_LIMIT'), $totalCount);

            // Audit transaction History by Admin
            $requestData = AuditTransaction::where('transaction_user', $request->user()->id)
                ->orderBy($sort, $order)
                ->orderBy('id', 'DESC')
                ->take(Config::get('constant.AUDIT_TRANSACTION_LIMIT'))
                ->offset($getPaginationData['offset'])
                ->get([
                    'audit_transactions.id',
                    'audit_transactions.transaction_type_id',
                    'audit_transactions.transaction_date',
                    'audit_transactions.transaction_user',
                    'audit_transactions.action_model_id',
                    'audit_transactions.action_detail',
                    'audit_transactions.url',
                    'audit_transactions.ip_address',
                    'audit_transactions.user_agent',
                ]);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => $totalCount,
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'noOfPages' => $getPaginationData['noOfPages'],
                'data' => $requestData,
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

    public function printReceipt(Request $request)
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

            $userId = $request->user()->id;
            $checkAccess = 1;
            // Check if user trying to get receipt is authorized for it or not (Bypassed for admin user)
            if ($request->user()->role_id != Config::get('constant.SUPER_ADMIN_ROLE_ID')) {
                $checkAccess = UserTransaction::where('id', $request->id)
                    ->where(function ($query) use ($userId) {
                        $query->where('from_user_id', $userId)
                            ->orWhere('to_user_id', $userId)
                            ->orWhere('commission_agent_id', $userId);
                    })
                    ->count();
            }

            // Restrict user if not authorized
            if ($checkAccess != 1) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.unauthorized_access'),
                ], 200);
            }

            // Get Receipt detail of transaction
            $receiptDetail = UserTransaction::leftJoin('users AS from_user', 'from_user.id', '=', 'user_transactions.from_user_id')
                ->leftJoin('users AS to_user', 'to_user.id', '=', 'user_transactions.to_user_id')
                ->leftJoin('users AS commission_agent', 'commission_agent.id', '=', 'user_transactions.commission_agent_id')
                ->where('user_transactions.id', $request->id)
                ->get([
                    'user_transactions.id',
                    'user_transactions.transaction_id',
                    'user_transactions.created_at',
                    'user_transactions.description',
                    'user_transactions.description AS _description',
                    DB::raw('CASE
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . ' THEN to_user.full_name
                        ELSE from_user.full_name END AS from_user_name'
                    ),
                    DB::raw('CASE
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "-"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN "-"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . ' THEN to_user.mobile_number
                        ELSE from_user.mobile_number END AS from_mobile_number'
                    ),
                    DB::raw('CASE
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN from_user.full_name
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN from_user.full_name
                        WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . ' THEN from_user.full_name
                        WHEN user_transactions.transaction_type=' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.to_user_id IS NULL THEN "' . Config::get('constant.SELF_USER') . '"
                                ELSE to_user.full_name
                            END
                        WHEN user_transactions.transaction_type=' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.to_user_id IS NULL THEN "' . Config::get('constant.SELF_USER') . '"
                                ELSE to_user.full_name
                            END
                        WHEN user_transactions.transaction_type=' . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.to_user_id IS NULL THEN "' . Config::get('constant.SELF_USER') . '"
                                ELSE to_user.full_name
                            END
                        ELSE to_user.full_name
                    END AS to_user_name'),
                    DB::raw('CASE
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN from_user.mobile_number
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN from_user.mobile_number
                        WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN "-"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "-"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . ' THEN from_user.mobile_number
                        WHEN user_transactions.transaction_type=' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.to_user_id IS NULL THEN from_user.mobile_number
                                ELSE to_user.mobile_number
                            END
                        WHEN user_transactions.transaction_type = ' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.to_user_id IS NULL THEN from_user.mobile_number
                                ELSE to_user.mobile_number
                            END
                        WHEN user_transactions.transaction_type=' . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.to_user_id IS NULL THEN from_user.mobile_number
                                ELSE to_user.mobile_number
                            END
                        ELSE to_user.mobile_number
                    END AS to_mobile_number'),
                    'commission_agent.full_name as commission_agent_name',
                    'commission_agent.mobile_number as commission_agent_number',
                    DB::raw('CASE
                        WHEN user_transactions.transaction_status=' . Config::get('constant.PENDING_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.PENDING_TRANSACTION') . '"
                        WHEN user_transactions.transaction_status=' . Config::get('constant.SUCCESS_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.SUCCESS_TRANSACTION') . '"
                        WHEN user_transactions.transaction_status=' . Config::get('constant.REJECTED_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.REJECTED_TRANSACTION') . '"
                        ELSE "' . Config::get('constant.FAILED_TRANSACTION') . '"
                    END AS transaction_status'),
                    'user_transactions.net_amount',
                    'user_transactions.total_commission_amount',
                    'user_transactions.admin_commission_amount',
                    'user_transactions.agent_commission_amount',
                    'user_transactions.amount',
                    DB::raw('CASE
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.ADD_MONEY_RECEIPT_TITLE') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.WITHDRAW_MONEY_RECEIPT_TITLE') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.ADD_COMMISSION_RECEIPT_TITLE') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.WITHDRAW_COMMISSION_RECEIPT_TITLE') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . ' THEN 
                        CASE WHEN user_transactions.from_user_id=' . $userId . ' THEN "' . Config::get('constant.TRANSFER_MONEY_SENT_RECEIPT_TITLE') . '"
                                WHEN user_transactions.to_user_id=' . $userId . ' THEN "' . Config::get('constant.TRANSFER_MONEY_RECEIVED_RECEIPT_TITLE') . '"
                                ELSE "' . Config::get('constant.TRANSFER_MONEY_SENT_RECEIPT_TITLE') . '"
                            END
                        WHEN user_transactions.transaction_type=' . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.CASH_IN_RECEIPT_TITLE') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.CASH_OUT_RECEIPT_TITLE') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.from_user_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_SENT_RECEIPT_TITLE') . '"
                                WHEN user_transactions.to_user_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_RECEIVED_RECEIPT_TITLE') . '"
                                ELSE "' . Config::get('constant.E_VOUCHER_SENT_RECEIPT_TITLE') . '"
                            END
                        WHEN user_transactions.transaction_type=' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.from_user_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_SENT_RECEIPT_TITLE') . '"
                                WHEN user_transactions.to_user_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_RECEIVED_RECEIPT_TITLE') . '"
                                ELSE "' . Config::get('constant.E_VOUCHER_SENT_RECEIPT_TITLE') . '"
                            END
                        WHEN user_transactions.transaction_type=' . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.from_user_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_SENT_RECEIPT_TITLE') . '"
                                WHEN user_transactions.to_user_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_CASHED_OUT_RECEIPT_TITLE') . '"
                                WHEN user_transactions.commission_agent_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_CASH_OUT_RECEIPT_TITLE') . '"
                                ELSE "' . Config::get('constant.E_VOUCHER_SENT_RECEIPT_TITLE') . '"
                            END
                    END AS receipt_title'),
                    DB::raw('CASE 
                        WHEN user_transactions.transaction_type = ' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_ADDED_TO_WALLET_TYPE') . '"
                        WHEN user_transactions.transaction_type = ' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_WITHDRAW_FROM_WALLET_TYPE') . '"
                        WHEN user_transactions.transaction_type = ' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_ADDED_COMMISSION_TO_WALLET_TYPE') . '"
                        WHEN user_transactions.transaction_type = ' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_WITHDRAW_COMMISSION_FROM_WALLET_TYPE') . '"
                        WHEN user_transactions.transaction_type = ' . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_CASH_IN_TYPE') . '"
                        WHEN user_transactions.transaction_type = ' . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.from_user_id = ' . $userId . ' THEN "' . Config::get('constant.TRANSACTION_E_VOUCHER_CASHED_OUT_TYPE') . '"
                                WHEN user_transactions.to_user_id = ' . $userId . ' THEN "' . Config::get('constant.TRANSACTION_E_VOUCHER_CASHED_OUT_TYPE') . '"
                                ELSE "'.Config::get('constant.TRANSACTION_E_VOUCHER_CASHED_OUT_TYPE').'"
                            END
                        WHEN user_transactions.transaction_type = ' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.from_user_id = ' . $userId . ' THEN "' . Config::get('constant.TRANSACTION_E_VOUCHER_ADDED_TO_WALLET_TYPE') . '"
                                WHEN user_transactions.to_user_id = ' . $userId . ' THEN "' . Config::get('constant.TRANSACTION_E_VOUCHER_ADDED_TO_WALLET_TYPE') . '"
                                ELSE "' . Config::get('constant.TRANSACTION_E_VOUCHER_ADDED_TO_WALLET_TYPE') . '"
                            END
                        WHEN user_transactions.transaction_type = ' . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.from_user_id = ' . $userId . ' THEN "' . Config::get('constant.TRANSACTION_MONEY_SENT_TYPE') . '"
                                WHEN user_transactions.to_user_id = ' . $userId . ' THEN "' . Config::get('constant.TRANSACTION_MONEY_RECEIVED_TYPE') . '"
                                ELSE "' . Config::get('constant.TRANSACTION_MONEY_SENT_TYPE') . '"
                            END
                        WHEN user_transactions.transaction_type = ' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.from_user_id = ' . $userId . ' THEN "' . Config::get('constant.TRANSACTION_E_VOUCHER_SENT_TYPE') . '"
                                WHEN user_transactions.to_user_id = ' . $userId . ' THEN "' . Config::get('constant.TRANSACTION_E_VOUCHER_RECEIVED_TYPE') . '"
                                ELSE "' . Config::get('constant.TRANSACTION_E_VOUCHER_SENT_TYPE') . '"
                            END
                        ELSE "' . Config::get('constant.TRANSACTION_CASH_OUT_TYPE') . '"
                        END
                    AS transaction_type'),
                    DB::raw('CASE
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.ADD_MONEY_AMOUNT_LABEL') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.WITHDRAW_MONEY_AMOUNT_LABEL') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.ADDED_COMMISSION_AMOUNT_LABEL') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.WITHDRAW_COMMISSION_AMOUNT_LABEL') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.from_user_id=' . $userId . ' THEN "' . Config::get('constant.MONEY_SENT_AMOUNT_LABEL') . '"
                                WHEN user_transactions.to_user_id=' . $userId . ' THEN "' . Config::get('constant.MONEY_RECEIVED_AMOUNT_LABEL') . '"
                                ELSE "' . Config::get('constant.TRANSFER_MONEY_AMOUNT_LABEL') . '"
                            END
                        WHEN user_transactions.transaction_type=' . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.CASH_IN_AMOUNT_LABEL') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.CASH_OUT_AMOUNT_LABEL') . '"
                        WHEN user_transactions.transaction_type=' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.from_user_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_SENT_AMOUNT_LABEL') . '"
                                WHEN user_transactions.to_user_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_RECEIVED_AMOUNT_LABEL') . '"
                                ELSE "' . Config::get('constant.E_VOUCHER_SENT_AMOUNT_LABEL') . '"
                            END
                        WHEN user_transactions.transaction_type=' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.from_user_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_SENT_AMOUNT_LABEL') . '"
                                WHEN user_transactions.to_user_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_ADDED_TO_WALLET_AMOUNT_LABEL') . '"
                                ELSE "' . Config::get('constant.E_VOUCHER_SENT_AMOUNT_LABEL') . '"
                            END
                        WHEN user_transactions.transaction_type=' . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . ' THEN
                            CASE WHEN user_transactions.from_user_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_SENT_AMOUNT_LABEL') . '"
                                WHEN user_transactions.to_user_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_CASHED_OUT_AMOUNT_LABEL') . '"
                                WHEN user_transactions.commission_agent_id=' . $userId . ' THEN "' . Config::get('constant.E_VOUCHER_CASH_OUT_AMOUNT_LABEL') . '"
                                ELSE "' . Config::get('constant.E_VOUCHER_SENT_AMOUNT_LABEL') . '"
                            END
                    END AS amount_label'),
            ]);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'transaction' => $receiptDetail,
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

    /**
     * All transaction history made by admin
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminTransactionHistory(Request $request) {
        try {
            $rule = [
                'page' => 'required|integer|min:1',
                'limit' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $searchByUser = (isset($request->user_id) && !empty($request->user_id)) ? $request->user_id : null;
            $searchByTransactionID = (isset($request->transaction_id_like) && !empty($request->transaction_id_like)) ? $request->transaction_id_like : null;
            $searchByTransactionStatus = (isset($request->transaction_status_like) && !empty($request->transaction_status_like)) ? $request->transaction_status_like : null;
            $searchByTransactionCreatedAt = (isset($request->created_at_like) && !empty($request->created_at_like)) ? $request->created_at_like : null;
            $searchByTransactionType = (isset($request->transaction_type_like) && !empty($request->transaction_type_like)) ? $request->transaction_type_like : null;
            $searchByFromUserName = (isset($request->from_user_name_like) && !empty($request->from_user_name_like)) ? $request->from_user_name_like : null;
            $searchByToUserName = (isset($request->to_user_name_like) && !empty($request->to_user_name_like)) ? $request->to_user_name_like : null;

            // Total Count
            $totalCount = UserTransaction::allTransactionCount($searchByUser, $searchByTransactionID, $searchByTransactionStatus, $searchByTransactionType, $searchByFromUserName, $searchByToUserName, $searchByTransactionCreatedAt, $request->user()->id);

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalCount);

            // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // Add / Withdraw Request
            $requestData = UserTransaction::allTransactionHistory($request->limit, $getPaginationData['offset'], $sort, $order, $searchByUser, $searchByTransactionID, $searchByTransactionStatus, $searchByTransactionType, $searchByFromUserName, $searchByToUserName, $searchByTransactionCreatedAt, $request->user()->id);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => $totalCount,
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'noOfPages' => $getPaginationData['noOfPages'],
                'data' => $requestData,
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
