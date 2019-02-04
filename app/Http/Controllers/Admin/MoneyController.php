<?php

namespace App\Http\Controllers\Admin;

use App\AuditTransaction;
use App\Http\Controllers\Controller;
use App\OTPManagement;
use App\TransferMoneyRequest;
use App\User;
use App\UserDetail;
use App\UserTransaction;
use Carbon\Carbon;
use CommissionService;
use Config;
use DB;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Validator;

class MoneyController extends Controller
{
    public function __construct()
    {
        $this->objUser = new User();
        $this->otpExpireSeconds = Config::get('constant.OTP_EXPIRE_SECONDS');
        $this->resendOtpExpireSeconds = Config::get('constant.RESEND_OTP_EXPIRE_SECONDS');
    }

    /**
     * To get add / withdraw request from agent
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addWithdrawRequest(Request $request)
    {
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

            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // Total Count with search parameter
            $totalCount = UserTransaction::join('users', 'users.id', '=', 'user_transactions.from_user_id')
                ->leftJoin('user_details', 'user_details.user_id', '=', 'users.id')
                ->whereIn('transaction_type', [Config::get('constant.ADD_MONEY_TRANSACTION_TYPE'), Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE'), Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE')]);

            if (isset($request->from_user_id_like) && !empty($request->from_user_id_like)) {
                $totalCount = $totalCount->where('users.id', 'LIKE', "%$request->from_user_id_like%");
            }
            if (isset($request->full_name_like) && !empty($request->full_name_like)) {
                $totalCount = $totalCount->where('users.full_name', 'LIKE', "%$request->full_name_like%");
            }
            // if (isset($request->amount_like) && !empty($request->amount_like)) {
            //     $totalCount = $totalCount->where('user_transactions.amount', 'LIKE', "%$request->amount_like%");
            // }
            if (isset($request->start_amount_like) && !empty($request->start_amount_like)) {
                $totalCount = $totalCount->where('user_transactions.amount', '>=', $request->start_amount_like);
            }
            if (isset($request->end_amount_like) && !empty($request->end_amount_like)) {
                $totalCount = $totalCount->where('user_transactions.amount', '<=', $request->end_amount_like);
            }
            if (isset($request->transaction_type_like) && !empty($request->transaction_type_like)) {
                $totalCount = $totalCount->where('user_transactions.transaction_type', $request->transaction_type_like);
            }
            if (isset($request->transaction_status_like) && !empty($request->transaction_status_like)) {
                $totalCount = $totalCount->where('user_transactions.transaction_status', $request->transaction_status_like);
            }
            $totalCount = $totalCount->count();

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalCount);

            // Add / Withdraw Request
            $requestData = UserTransaction::join('users', 'users.id', '=', 'user_transactions.from_user_id')
                ->leftJoin('user_details', 'user_details.user_id', '=', 'users.id')
                ->whereIn('transaction_type', [Config::get('constant.ADD_MONEY_TRANSACTION_TYPE'), Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE'), Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE')]);
            if (isset($request->from_user_id_like) && !empty($request->from_user_id_like)) {
                $requestData = $requestData->where('user_transactions.from_user_id', 'LIKE', "%$request->from_user_id_like%");
            }
            if (isset($request->full_name_like) && !empty($request->full_name_like)) {
                $requestData = $requestData->where('users.full_name', 'LIKE', "%$request->full_name_like%");
            }
            // if (isset($request->amount_like) && !empty($request->amount_like)) {
            //     $requestData = $requestData->where('user_transactions.amount', 'LIKE', "%$request->amount_like%");
            // }
            if (isset($request->start_amount_like) && !empty($request->start_amount_like)) {
                $requestData = $requestData->where('user_transactions.amount', '>=', $request->start_amount_like);
            }
            if (isset($request->end_amount_like) && !empty($request->end_amount_like)) {
                $requestData = $requestData->where('user_transactions.amount', '<=', $request->end_amount_like);
            }
            if (isset($request->transaction_type_like) && !empty($request->transaction_type_like)) {
                $requestData = $requestData->where('user_transactions.transaction_type', $request->transaction_type_like);
            }
            if (isset($request->transaction_status_like) && !empty($request->transaction_status_like)) {
                $requestData = $requestData->where('user_transactions.transaction_status', $request->transaction_status_like);
            }
            $requestData = $requestData->orderBy($sort, $order)
                ->take($request->limit)
                ->offset($getPaginationData['offset'])
                ->get([
                    'user_transactions.id',
                    'user_transactions.amount',
                    'user_transactions.from_user_id',
                    DB::raw('CASE WHEN user_transactions.transaction_status=' . Config::get('constant.PENDING_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.PENDING_TRANSACTION') . '" WHEN user_transactions.transaction_status=' . Config::get('constant.SUCCESS_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.SUCCESS_TRANSACTION') . '" WHEN user_transactions.transaction_status=' . Config::get('constant.REJECTED_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.REJECTED_TRANSACTION') . '" ELSE "' . Config::get('constant.FAILED_TRANSACTION') . '" END as transaction_status'),
                    DB::raw('CASE WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.ADD_MONEY') . '" WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.COMMISSION_MONEY_WITHDRAW') . '" ELSE "' . Config::get('constant.MONEY_WITHDRAW') . '" END as transaction_type'),
                    'users.full_name',
                    'user_details.country_code',
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
     * To approve add or withdraw money request
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveAddOrWithdrawRequest(Request $request)
    {
        try {
            $rule = [
                'id' => 'required|integer',
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
                    'message' => trans('apimessages.invalid_input_parameter'),
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
                $transactionMessageToNumber = $receiverUser->mobile_number;

                $insufficientBalanceErrorMsg = trans('apimessages.insufficient_balance_msg');
                // Agent balance after add money (If admin approve request)
                $agentBalance = $receiverDetail->balance_amount + $transaction->amount;
            } else {
                // Here current logined admin is as a receiver
                $receiverUser = $request->user();
                $receiverDetail = $request->user()->userDetail()->lockForUpdate()->first();

                // Here agent user is as a sender
                $agentUser = $senderUser = User::find($transaction->from_user_id);
                $senderDetail = $senderUser->userDetail()->lockForUpdate()->first();

                $transactionMessageAlert = trans('apimessages.NOTIFICATION_MESSAGE_TO_AGENT_AFTER_WITHDARW_REQUEST_APPROVED_BY_ADMIN');
                $transactionMessageToNumber = $senderUser->mobile_number;

                // Insufficient wallet balance
                $insufficientBalanceErrorMsg = strtr(trans('apimessages.INSUFFICIENT_WALLET_BALANCE_MESSAGE_OF_AGENT'), [
                    '<Sender User>' => $senderUser->full_name,
                ]);

                // Agent balance after withdraw money (If admin approve request)
                $agentBalance = $senderDetail->balance_amount - $transaction->amount;

                if ($transaction->transaction_type == Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE')) {
                    $transactionMessageAlert = trans('apimessages.NOTIFICATION_MESSAGE_TO_AGENT_AFTER_WITHDARW_COMMISSION_REQUEST_APPROVED_BY_ADMIN');
                    // Insufficient commission wallet balance
                    $insufficientBalanceErrorMsg = strtr(trans('apimessages.INSUFFICIENT_COMMISSION_WALLET_BALANCE_MESSAGE_OF_AGENT'), [
                        '<Sender User>' => $senderUser->full_name,
                    ]);
                    // Agent balance after withdraw commission money (If admin approve request)
                    $agentBalance = $senderDetail->balance_amount;
                }
            }

            // Insufficient commission wallet balance of agent
            if ($transaction->transaction_type == Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE')) { // If withdraw from commission wallet request to admin
                if ($senderDetail->commission_wallet_balance < $transaction->amount) {
                    DB::rollback();
                    Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'), [
                        '<Mobile Number>' => $senderUser->mobile_number,
                    ]));
                    return response()->json([
                        'status' => 0,
                        'message' => $insufficientBalanceErrorMsg,
                    ], 200);
                }
            } else {
                if ($senderDetail->balance_amount < $transaction->amount) {
                    DB::rollback();
                    Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'), [
                        '<Mobile Number>' => $senderUser->mobile_number,
                    ]));
                    return response()->json([
                        'status' => 0,
                        'message' => $insufficientBalanceErrorMsg,
                    ], 200);
                }
            }

            // Add amount to receiver balance
            $receiverDetail->balance_amount += $transaction->amount;
            $receiverDetail->save();

            // Deduct from sender balance
            if ($transaction->transaction_type == Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE')) { // If withdraw from commission wallet request to admin
                $senderDetail->commission_wallet_balance -= $transaction->amount;
            } else {
                $senderDetail->balance_amount -= $transaction->amount;
            }

            $senderDetail->save();

            // Update transaction history
            $transaction->fill(array_filter([
                'transaction_status' => Config::get('constant.SUCCESS_TRANSACTION_STATUS'),
                'approved_by' => $request->user()->id,
                'approved_at' => Carbon::now(),
            ]));
            $transaction->save();
            Log::channel('transaction')->info(strtr(trans('log_messages.approveAddOrWithdrawRequest'), [
                '<User>' => $request->user()->mobile_number,
                '<Action>' => Helpers::getTransactionType($transaction->transaction_type),
            ]));

            /**
             * @added on 23rd July, 2018
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
                'action_detail' => $transaction,
            ];
            $auditTransaction->insertUpdate($auditTransactionRecord);
            /**
             * @added on 23rd July, 2018
             * @Code injection ended.
             */

            // Send message to agent after request approved by admin
            $message = strtr($transactionMessageAlert, [
                '<Value>' => number_format($transaction->amount, 2),
                '<Transaction ID>' => $transaction->transaction_id,
                '<Agent Current Balance>' => number_format($agentBalance, 2),
            ]);
            Helpers::sendMessage($transactionMessageToNumber, $message);
            // Save transaction message to notification table for agent
            Helpers::saveNotificationMessage($agentUser, $message);

            DB::commit();
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [],
            ], 200);
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
     * All transaction history
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function allTransactionHistory(Request $request)
    {
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
            $totalCount = UserTransaction::allTransactionCount($searchByUser, $searchByTransactionID, $searchByTransactionStatus, $searchByTransactionType, $searchByFromUserName, $searchByToUserName, $searchByTransactionCreatedAt);

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalCount);

            // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // All transaction history
            $requestData = UserTransaction::allTransactionHistory($request->limit, $getPaginationData['offset'], $sort, $order, $searchByUser, $searchByTransactionID, $searchByTransactionStatus, $searchByTransactionType, $searchByFromUserName, $searchByToUserName, $searchByTransactionCreatedAt);

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
     * Transfer Money from user to user
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateMobileNumber(Request $request)
    {
        try {
            $rule = [
                'from_mobile_number' => 'required',
                'to_mobile_number' => 'required|min:10|max:17|regex:/^\+?\d+$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // Check if same from and to number
            if ($request->from_mobile_number == $request->to_mobile_number) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.from_and_to_mobile_number_not_same'),
                ], 200);
            }

            // Validate mobile numbers
            $validateFromMobileNumber = Helpers::validateMobileNumber($request->from_mobile_number, $request->user()->mobile_number);
            $validateToMobileNumber = Helpers::validateMobileNumber($request->to_mobile_number, $request->user()->mobile_number);

            if (isset($validateFromMobileNumber['status']) && $validateFromMobileNumber['status'] == 0) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.from_mobile_number_error_msg'),
                ], 200);
            }

            $fromUserDetail = User::with('userDetail')->with('role')->where('mobile_number', $request->from_mobile_number)->first();
            if (isset($validateToMobileNumber['status']) && $validateToMobileNumber['status'] == 0) {
                // Transfer money to un-registered user
                if ($validateToMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) {
                    return response()->json([
                        'status' => 1,
                        'message' => trans('apimessages.transfer_money_to_unregistered_user_mobile'),
                        'action' => Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE'),
                        'data' => [
                            'from_user' => $fromUserDetail,
                            'to_user' => [
                                'mobile_number' => $request->to_mobile_number,
                            ],
                        ],
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 0,
                        'message' => $validateToMobileNumber['message'],
                    ], 200);
                }
            }

            $toUserDetail = User::with('userDetail')->with('role')->where('mobile_number', $request->to_mobile_number)->first();
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'action' => '',
                'data' => [
                    'from_user' => $fromUserDetail,
                    'to_user' => $toUserDetail,
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
     * Transfer money from user to user (one-to-one transaction)
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transferMoneyRequest(Request $request)
    {
        try {
            $rule = [
                'from_mobile_number' => 'required',
                'to_mobile_number' => 'required|min:10|max:17|regex:/^\+?\d+$/',
                'amount' => 'required|numeric|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            // Check if same from and to number
            if ($request->from_mobile_number == $request->to_mobile_number) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.from_and_to_mobile_number_not_same'),
                ], 200);
            }

            // Validate from user mobile number
            $validateFromMobileNumber = Helpers::validateMobileNumber($request->from_mobile_number, $request->user()->mobile_number);

            if (isset($validateFromMobileNumber['status']) && $validateFromMobileNumber['status'] == 0) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.from_mobile_number_error_msg'),
                ], 200);
            }

            // Validate to user mobile number
            $validateToMobileNumber = Helpers::validateMobileNumber($request->to_mobile_number, $request->user()->mobile_number);

            DB::beginTransaction();
            if (isset($validateToMobileNumber['status']) && $validateToMobileNumber['status'] == 0) {
                // Transfer money to un-registered user
                if ($validateToMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) {
                    // Create unregistered user
                    $guestUser = $this->objUser->where('mobile_number', $request->to_mobile_number)->first();
                    if ($guestUser === null) {
                        $guestUser = $this->objUser->insertUpdate([
                            'full_name' => Config::get('constant.GUEST_NAME'),
                            'mobile_number' => $request->to_mobile_number,
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
                        'message' => trans('apimessages.to_mobile_number_error_msg'),
                    ], 200);
                }
            }

            // Get helapay fee data
            if ($validateToMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) {
                $feeData = CommissionService::calculateFee($request->amount, Config::get('constant.SEND_E_VOUCHER_FEE'));
            } else {
                $feeData = CommissionService::calculateFee($request->amount, Config::get('constant.TRANSFER_MONEY_FEE'));
            }

            // Handle error
            if ($feeData['status'] == 0) {
                DB::rollback();
                return response()->json([
                    'status' => $feeData['status'],
                    'message' => $feeData['message'],
                ], $feeData['code']);
            }

            // Check sender balance
            $senderUser = User::where('mobile_number', $request->from_mobile_number)->first();
            $senderDetail = $senderUser->userDetail()->first();
            if ($senderDetail->balance_amount < ($request->amount + $feeData['data']['totalCommission'])) {
                DB::rollback();
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'), [
                    '<Mobile Number>' => $senderUser->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => $senderUser->full_name . trans('apimessages.insufficient_balance'),
                ], 200);
            }
            $receiverDetail = User::where('mobile_number', $request->to_mobile_number)->first();

            // Save transaction history with not verified status
            $otp = mt_rand(100000, 999999);
            $transferMoneyRequest = new TransferMoneyRequest([
                'otp_sent_to' => $request->user()->mobile_number,
                'to_user_id' => $receiverDetail->id,
                'amount' => $request->amount,
                'description' => (isset($request->description)) ? $request->description : null,
                'otp' => $otp,
                'otp_created_at' => Carbon::now(),
                'created_by' => $request->user()->id,
            ]);
            $senderUser->transferMoneyRequest()->save($transferMoneyRequest);

            // Send OTP message to sender
            $messageToSender = trans('apimessages.OTP_MESSAGE_TO_SENDER_TO_TRANSFER_MONEY');
            $message = strtr($messageToSender, [
                '<OTP>' => $otp,
                '<Value>' => $request->amount,
                '<Receiver Name>' => $receiverDetail->full_name,
                '<Receiver Mobile Number>' => Helpers::maskString($receiverDetail->mobile_number),
            ]);
            $sendOtp = Helpers::sendMessage($request->user()->mobile_number, $message); // $request->from_mobile_number

            if (!$sendOtp) {
                Log::channel('otp')->error(strtr(trans('log_messages.otp_error_action'), [
                    '<Mobile Number>' => $request->user()->mobile_number,
                    '<Action>' => 'Transfer',
                ]));
                DB::rollback();
                return response()->json([
                    'status' => '0',
                    'message' => trans('apimessages.SOMETHING_WENT_WRONG_WHILE_SENDING_OTP'),
                ], 200);
            }

            Log::channel('otp')->error(strtr(trans('log_messages.otp_success_action'), [
                '<Mobile Number>' => $request->user()->mobile_number,
                '<Action>' => 'Transfer',
            ]));

            $om = new OTPManagement();
            $om->otp = $otp;
            $om->otp_sent_to = $request->user()->mobile_number;
            $om->created_by = $request->user()->id;
            $om->operation = ($receiverDetail->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) ? Config::get('constant.OTP_O_E_VOUCHER_SENT_VERIFICATION') : Config::get('constant.OTP_O_TRANSFER_MONEY_VERIFICATION');
            $om->from_user = $senderUser->id;
            $om->to_user = $receiverDetail->id;
            $om->amount = $request->amount;
            $om->save();

            // Manage response message - Start
            if (Config::get('constant.DISPLAY_OTP') == 1) {
                $responseMessage = strtr(trans('apimessages.OTP_SENT_SUCCESS_MESSAGE_FOR_DEV'), [
                    '<Mobile Number>' => $request->user()->mobile_number,
                    '<OTP>' => $otp,
                ]);
            } else {
                $responseMessage = strtr(trans('apimessages.OTP_SENT_SUCCESS_MESSAGE_FOR_PROD'), [
                    '<Mobile Number>' => $request->user()->mobile_number,
                ]);
            }

            DB::commit();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => $responseMessage,
                'data' => [
                    'mobile_number' => $senderUser->mobile_number,
                    'request_id' => $transferMoneyRequest->id,
                ],
            ], 200);
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
     * Verify transfer request otp from admin
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyTransferMoneyOtp(Request $request)
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

            // To check that money request already proceed or invalid
            $senderUser = User::where('mobile_number', $request->mobile_number)->first();
            $transferMoneyRequest = $senderUser->transferMoneyRequest()->where('otp', $request->otp)->first();

            // If request with this otp not found
            if ($transferMoneyRequest === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.invalid_otp'),
                ], 200);
            }

            // Request already accepted
            if ($transferMoneyRequest !== null && $transferMoneyRequest->unregistered_number !== null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.transfer_request_already_proceed_or_not_possible'),
                ], 200);
            }

            if ($transferMoneyRequest->otp == null || $transferMoneyRequest->otp_created_at == null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.transfer_request_already_proceed_or_not_possible'),
                ], 200);
            }

            $secondsSinceTransferMoneyRequestOtpCreated = Carbon::now()->diffInSeconds($transferMoneyRequest->otp_created_date);

            if ($secondsSinceTransferMoneyRequestOtpCreated > $this->otpExpireSeconds) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.otp_expired'),
                ], 200);
            }

            DB::beginTransaction();

            // Add amount to receiver balance
            $receiverUser = User::find($transferMoneyRequest->to_user_id);

            // Get helapay fee data
            if ($receiverUser->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) {
                $feeData = CommissionService::calculateFee($transferMoneyRequest->amount, Config::get('constant.SEND_E_VOUCHER_FEE'));
            } else {
                $feeData = CommissionService::calculateFee($transferMoneyRequest->amount, Config::get('constant.TRANSFER_MONEY_FEE'));
            }

            // Handle error
            if ($feeData['status'] == 0) {
                DB::rollback();
                return response()->json([
                    'status' => $feeData['status'],
                    'message' => $feeData['message'],
                ], $feeData['code']);
            }

            // Check sender balance
            $senderDetail = $senderUser->userDetail()->lockForUpdate()->first();

            if ($senderDetail->balance_amount < ($transferMoneyRequest->amount + $feeData['data']['totalCommission'])) {
                DB::rollback();
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'), [
                    '<Mobile Number>' => $senderUser->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => $senderUser->full_name . trans('apimessages.insufficient_balance'),
                ], 200);
            }

            $otp = '';
            //$feeData = [];
            // If receiver is unregistered
            if ($receiverUser->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) {
                // Save into transfer money request and send OTP to unregistered user
                $otp = mt_rand(100000, 999999);
            } else {
                // Add amount to receiver balance
                $receiverDetail = $receiverUser->userDetail()->lockForUpdate()->first();
                $receiverDetail->balance_amount += $transferMoneyRequest->amount;
                $receiverDetail->save();
            }

            // Deduct from sender balance
            $senderDetail->balance_amount -= ($transferMoneyRequest->amount + $feeData['data']['totalCommission']);
            $senderDetail->save();

            // Add fee to admin user
            $adminData = User::with('userDetail')->where('role_id', Config::get('constant.SUPER_ADMIN_ROLE_ID'))->first();
            // Upgrade commission wallet balance
            $adminData->userDetail->commission_wallet_balance += $feeData['data']['helapayCommission'];
            $adminData->userDetail->save();

            // Save transaction history
            $transaction = new UserTransaction([
                'to_user_id' => $receiverUser->id,
                'amount' => ($transferMoneyRequest->amount + $feeData['data']['totalCommission']),
                'net_amount' => $transferMoneyRequest->amount,
                'total_commission_amount' => (!empty($feeData)) ? $feeData['data']['totalCommission'] : 0,
                'admin_commission_amount' => (!empty($feeData)) ? $feeData['data']['helapayCommission'] : 0,
                'description' => $transferMoneyRequest->description,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.SUCCESS_TRANSACTION_STATUS'),
                'transaction_type' => ($receiverUser->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) ? Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') : Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE'),
                'otp' => ($receiverUser->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) ? $otp : null,
                'created_by' => $request->user()->id,
            ]);
            $senderUser->senderTransaction()->save($transaction);

            Log::channel('transaction')->info(strtr(trans('log_messages.transfer_money'), [
                '<From User>' => $senderUser->mobile_number,
                '<To User>' => $receiverUser->mobile_user,
                '<Amount>' => $transferMoneyRequest->amount,
            ]));

            /**
             * @added on 23rd July, 2018
             * Following code is injected to create audit transaction for each user.
             *
             * @Code injection started.
             */
            $auditTransaction = new AuditTransaction();
            $auditTransactionRecord = [
                'transaction_type_id' => ($receiverUser->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) ? Config::get('constant.AUDIT_TRANSACTION_TYPE_E_VOUCHER') : Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE'),
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

            $transferMoneyRequest->delete(); // Delete transfer money request
            if ($receiverUser->verification_status != Config::get('constant.UNREGISTERED_USER_STATUS')) {

                // Transfer alert to receiver
                $receiverMsg = strtr(trans('apimessages.transfer_money_transaction_msg_to_receiver'), [
                    '<Value>' => $transferMoneyRequest->amount,
                    '<Sender Name>' => $senderUser->full_name,
                    '<Sender Mobile Number>' => Helpers::maskString($senderUser->mobile_number),
                    '<Transaction ID>' => $transaction->transaction_id,
                    '<Receiver Current Balance>' => number_format($receiverDetail->balance_amount, 2),
                ]);
                $transactionMsgToReceiver = Helpers::sendMessage($receiverUser->mobile_number, $receiverMsg);
                // Save transaction message to notification table for receiver
                Helpers::saveNotificationMessage($receiverUser, $receiverMsg);

                // Transfer alert to sender
                $senderMsg = strtr(trans('apimessages.transfer_money_transaction_msg_to_sender'), [
                    '<Sender Name>' => Config::get('constant.HELAPAY_ADMIN_NAME'),
                    '<Value>' => number_format($transferMoneyRequest->amount, 2),
                    '<Receiver Name>' => $receiverUser->full_name,
                    '<Receiver Mobile Number>' => Helpers::maskString($receiverUser->mobile_number),
                    '<Transaction ID>' => $transaction->transaction_id,
                    '<Fee>' => number_format($feeData['data']['totalCommission'],2),
                    '<Sender Current Balance>' => number_format($senderDetail->balance_amount, 2),
                ]);
                $transactionMsgTosender = Helpers::sendMessage($senderUser->mobile_number, $senderMsg);
                // Save transaction message to notification table for sender
                Helpers::saveNotificationMessage($senderUser, $senderMsg);
            } else if ($receiverUser->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) {
                // Transfer alert to unregistered user
                $receiverMsg = strtr(trans('apimessages.message_to_unregistered_user_for_transfer'), [
                    '<Value>' => number_format($transferMoneyRequest->amount, 2),
                    '<User Name>' => $senderUser->full_name,
                    '<User Mobile Number>' => Helpers::maskString($senderUser->mobile_number),
                    '<Authorization Code>' => implode('-', str_split($otp, 3)),
                    // '<Transaction ID>' => $transaction->transaction_id,
                ]);
                $transactionMsgToReceiver = Helpers::sendMessage($receiverUser->mobile_number, $receiverMsg);
                // Save transaction message to notification table for unregistered user
                Helpers::saveNotificationMessage($receiverUser, $receiverMsg);

                if (!$transactionMsgToReceiver) {
                    DB::rollback();
                    Log::error(strtr(trans('log_messages.message_error'), [
                        '<Mobile Number>' => $receiverUser->mobile_number,
                    ]));

                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.something_went_wrong'),
                    ], 200);
                }

                // Transfer alert to sender
                $senderMsg = strtr(trans('apimessages.TRANSACTION_MESSAGE_TO_SENDER_AFTER_SEND_MONEY_TO_UNREGISTERED_USER'), [
                    '<Sender Name>' => Config::get('constant.HELAPAY_ADMIN_NAME'),
                    '<Value>' => number_format($transferMoneyRequest->amount, 2),
                    '<Receiver Mobile Number>' => Helpers::maskString($receiverUser->mobile_number),
                    '<Transaction ID>' => $transaction->transaction_id,
                    '<Fee>' => number_format($feeData['data']['totalCommission'],2),
                    '<Sender Balance Amount>' => number_format($senderDetail->balance_amount, 2),
                ]);
                $transactionMsgTosender = Helpers::sendMessage($senderUser->mobile_number, $senderMsg);
                // Save transaction message to notification table for sender
                Helpers::saveNotificationMessage($senderUser, $senderMsg);
            }

            DB::commit();
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'authorizationCode' => (Config::get('constant.DISPLAY_OTP') == 1) ? $otp : '',
                'data' => [
                    'sender_name' => $senderUser->full_name,
                    'receiver_name' => $receiverUser->full_name,
                    'transaction_id' => $transaction->transaction_id,
                    'created_at' => date_format($transaction->created_at, 'Y-m-d H:i:s'),
                    'transaction_amount' => $transaction->amount,
                    'sender_country_code' => $senderDetail->country_code,
                ],
            ], 200);
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
     * Verify add or withdraw money request otp
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

            $user = $request->user();
            $userDetail = $request->user()->userDetail()->lockForUpdate()->first();
            // Check sender balance
            if ($request->action == Config::get('constant.ADD_MONEY_ACTION')) {
                // Add amount to admin balance
                $userDetail->balance_amount += $addOrWithdrawMoneyRequest->amount;
                $userDetail->save();
            } else {
                if ($userDetail->balance_amount < $addOrWithdrawMoneyRequest->amount) {
                    DB::rollback();
                    Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'), [
                        '<Mobile Number>' => $user->mobile_number,
                    ]));
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.insufficient_balance_msg'),
                    ], 200);
                }

                // Deduct from admin balance
                $userDetail->balance_amount -= $addOrWithdrawMoneyRequest->amount;
                $userDetail->save();
            }

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
                'approved_at' => Carbon::now(),
            ]);
            $request->user()->senderTransaction()->save($transaction);
            Log::channel('transaction')->info(strtr(trans('log_messages.add_or_withdraw'), [
                '<Action>' => $request->action,
                '<Amount>' => $addOrWithdrawMoneyRequest->amount,
                '<Transaction Id>' => $transaction->transaction_id,
                '<User>' => $request->user()->mobile_number,
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

            DB::commit();
            $transactionDetail = $transaction->with('senderuser')->with('senderuserdetail')->where('id', $transaction->id)->first();
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $transactionDetail,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addOrWithdrawMoneyForAgentByAdmin(Request $request)
    {
        try {
            $rule = [
                'agent_id' => 'required|regex:/^[1-9][0-9]{0,15}$/',
                'amount' => 'required|numeric|regex:/^\s*(?=.*[1-9])\d*(?:\.\d{1,2})?\s*$/',
                'transaction_type' => ['required', Rule::in([Config::get('constant.ADD_MONEY_TRANSACTION_TYPE'), Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 400);
            }

            DB::beginTransaction();

            if ($request->transaction_type == Config::get('constant.ADD_MONEY_TRANSACTION_TYPE')) {
                $receiverUser = User::find($request->agent_id);
                $receiverDetail = $receiverUser->userDetail()->lockForUpdate()->first();
                $insufficientBalanceErrorMsg = trans('apimessages.insufficient_balance_msg');
                $agentBalance = $receiverDetail->balance_amount;
            } else {
                $senderUser = User::find($request->agent_id);
                $senderDetail = $senderUser->userDetail()->lockForUpdate()->first();
                $insufficientBalanceErrorMsg = $senderUser->full_name . trans('apimessages.insufficient_balance');
                $agentBalance = $senderDetail->balance_amount;
            }
            if ($agentBalance < $request->amount) {
                DB::rollback();
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'), [
                    '<Mobile Number>' => $senderUser->mobile_number !== null ? $senderUser->mobile_number : $receiverUser->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => $insufficientBalanceErrorMsg,
                ], 200);
            }

            // Update transaction history
            $transaction = new UserTransaction([
                'amount' => $request->amount,
                'net_amount' => $request->amount,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.PENDING_TRANSACTION_STATUS'),
                'transaction_type' => $request->transaction_type,
                'description' => $request->description,
                'from_user_id' => $request->agent_id,
                'created_by' => $request->user()->id,
            ]);
            $transaction->save();
            Log::channel('transaction')->info(strtr(trans('log_messages.add_or_withdraw'), [
                '<Action>' => $request->action,
                '<Amount>' => $request->amount,
                '<Transaction Id>' => $transaction->transaction_id,
                '<User>' => $request->user()->mobile_number,
            ]));

            DB::commit();
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'transaction_id' => $transaction->id
                ],
            ], 200);

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
}
