<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\OTPManagement;
use App\User;
use App\UserBeneficiary;
use App\UserDetail;
use App\UserTransaction;
use CommissionService;
use Config;
use DB;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\Voice\Number;
use Validator;

class SendMoneyController extends Controller
{

    public function __construct()
    {
        $this->objUser = new User();
        $this->otpExpireSeconds = Config::get('constant.OTP_EXPIRE_SECONDS');
        $this->resendOtpExpireSeconds = Config::get('constant.RESEND_OTP_EXPIRE_SECONDS');
    }

    /**
     * while transfer money first check if this is valid mobile.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkMobileExist(Request $request)
    {
        try {
            $rule = [
                'mobile_number' => 'required|min:10|max:17|regex:/^\+?\d+$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $validateMobileNumber = Helpers::validateMobileNumber($request->mobile_number, (isset($request->type) && $request->type == Config::get('constant.E-VOUCHER_ACTION')) ? null : $request->user()->mobile_number);

            Log::info(strtr(trans('log_messages.check_mobile_exists'), [
                '<Mobile Number>' => $request->mobile_number,
            ]));

            if (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0) {
                // Transfer money to un-registered user
                if ($validateMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) {
                    return response()->json([
                        'status' => 1,
                        'message' => (isset($request->action) && $request->action == Config::get('constant.E-VOUCHER_ACTION')) ? trans('apimessages.e-voucher_to_unregistered_user_mobile') : trans('apimessages.transfer_money_to_unregistered_user_mobile'),
                        'action' => Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE'),
                        'data' => [
                            'full_name' => Config::get('constant.GUEST_NAME'),
                            'mobile_number' => $request->mobile_number,
                        ],
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 0,
                        'message' => $validateMobileNumber['message'],
                    ], 200);
                }
            }
            $userDetail = $this->objUser->where('mobile_number', $request->mobile_number)->first();
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'action' => '',
                'data' => [
                    'full_name' => $userDetail->full_name,
                    'mobile_number' => $userDetail->mobile_number,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage(),
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.error_validating_user_mobile'),
            ], 500);
        }
    }

    /**
     * Calculate helapay fee while transfer money to another user
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transferFee(Request $request)
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

            // Validate receiver mobile number
            $validateMobileNumber = Helpers::validateMobileNumber($request->mobile_number, $request->user()->mobile_number);

            // If user is not verified
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

            // Get helapay fee data
            if ($validateMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) {
                $feeData = CommissionService::calculateFee($request->amount, Config::get('constant.SEND_E_VOUCHER_FEE'));
            } else {
                $feeData = CommissionService::calculateFee($request->amount, Config::get('constant.TRANSFER_MONEY_FEE'));
            }

            // Handle error
            if ($feeData['status'] == 0) {
                return response()->json([
                    'status' => $feeData['status'],
                    'message' => $feeData['message'],
                ], $feeData['code']);
            }

            $senderDetail = $request->user()->userDetail()->lockForUpdate()->first();

            // If sender doesn't have sufficient balance
            if ($senderDetail->balance_amount < ($request->amount + $feeData['data']['totalCommission'])) {
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'), [
                    '<Mobile Number>' => $request->user()->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.insufficient_balance_msg'),
                ], 200);
            }

            // Receiver user data
            $receiverUser = $this->objUser->where('mobile_number', $request->mobile_number)->first();

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

    /**
     * Send money to verified user by mobile number (one-to-one transaction)
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMoney(Request $request)
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

            DB::beginTransaction();
            // Check sender balance
            $senderDetail = $request->user()->userDetail()->lockForUpdate()->first();
            $otp = '';
            // Validate receiver mobile number
            $validateMobileNumber = Helpers::validateMobileNumber($request->mobile_number, $request->user()->mobile_number);
            if (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0) {
                // Transfer money to un-registered user as an e-voucher
                if ($validateMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) {

                    $transferMoneyToUnregisteredUser = $this->transferMoneyToUnregisteredUser($senderDetail, $request);

                    // If any error occured while transfering money to unregistered user
                    if ($transferMoneyToUnregisteredUser['status'] == 0) {
                        DB::rollback();
                        return response()->json([
                            'status' => $transferMoneyToUnregisteredUser['status'],
                            'message' => $transferMoneyToUnregisteredUser['message'],
                        ], $transferMoneyToUnregisteredUser['code']);
                    }

                    DB::commit();
                    return response()->json([
                        'status' => $transferMoneyToUnregisteredUser['status'],
                        'message' => $transferMoneyToUnregisteredUser['message'],
                        'authorizationCode' => ($transferMoneyToUnregisteredUser['authorizationCode']) ? $transferMoneyToUnregisteredUser['authorizationCode'] : '',
                        'action' => ($transferMoneyToUnregisteredUser['action']) ? $transferMoneyToUnregisteredUser['action'] : '',
                        'data' => ($transferMoneyToUnregisteredUser['data']) ? $transferMoneyToUnregisteredUser['data'] : [],
                    ], $transferMoneyToUnregisteredUser['code']);
                } else {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => $validateMobileNumber['message'],
                    ], 200);
                }
            }

            // Get commission data
            $feeData = CommissionService::calculateFee($request->amount, Config::get('constant.TRANSFER_MONEY_FEE'));

            // Handle error
            if ($feeData['status'] == 0) {
                DB::rollback();
                return response()->json([
                    'status' => $feeData['status'],
                    'message' => $feeData['message'],
                ], $feeData['code']);
            }

            if ($senderDetail->balance_amount < ($request->amount + $feeData['data']['totalCommission'])) {
                DB::rollback();
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'), [
                    '<Mobile Number>' => $request->user()->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.insufficient_balance_msg'),
                ], 200);
            }

            // Add amount to receiver balance
            $receiverUser = $this->objUser->where('mobile_number', $request->mobile_number)->first();
            $receiverDetail = $receiverUser->userDetail()->lockForUpdate()->first();
            $receiverDetail->balance_amount += $request->amount;
            $receiverDetail->save();

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
                'to_user_id' => $receiverUser->id,
                'amount' => ($request->amount + $feeData['data']['totalCommission']),
                'net_amount' => $request->amount,
                'total_commission_amount' => $feeData['data']['totalCommission'],
                'admin_commission_amount' => $feeData['data']['helapayCommission'],
                'description' => (isset($request->description)) ? $request->description : null,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.SUCCESS_TRANSACTION_STATUS'),
                'transaction_type' => Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE'),
                'created_by' => $request->user()->id,
            ]);

            $request->user()->senderTransaction()->save($transaction);
            Log::channel('transaction')->info(strtr(trans('log_messages.sendMoney'), [
                '<Amount>' => $request->amount,
                '<TO User>' => $receiverUser->mobile_number,
                '<From User>' => $request->user()->mobile_number,
            ]));

            $transactionDetail = $transaction->with('receiveruser')->with('receiveruserdetail')->where('id', $transaction->id)->first();

            // Transfer alert to receiver
            $receiverMsg = strtr(trans('apimessages.transfer_money_transaction_msg_to_receiver'), [
                '<Value>' => number_format($transaction->net_amount,2),
                '<Sender Name>' => $request->user()->full_name,
                '<Sender Mobile Number>' => Helpers::maskString($request->user()->mobile_number),
                '<Transaction ID>' => $transaction->transaction_id,
                '<Receiver Current Balance>' => number_format($receiverDetail->balance_amount, 2),
            ]);

            $transactionMsgToReceiver = Helpers::sendMessage($request->mobile_number, $receiverMsg);

            // Save transaction message to notification table for receiver user
            Helpers::saveNotificationMessage($receiverUser, $receiverMsg);

            // Transfer alert to sender
            $senderMsg = strtr(trans('apimessages.transfer_money_transaction_msg_to_sender'), [
                '<Sender Name>' => 'You',
                '<Value>' => number_format($request->amount, 2),
                '<Receiver Name>' => $receiverUser->full_name,
                '<Receiver Mobile Number>' => Helpers::maskString($receiverUser->mobile_number),
                '<Transaction ID>' => $transaction->transaction_id,
                '<Fee>' => number_format($feeData['data']['totalCommission'],2),
                '<Sender Current Balance>' => number_format($senderDetail->balance_amount, 2),
            ]);
            $transactionMsgTosender = Helpers::sendMessage($request->user()->mobile_number, $senderMsg);

            // Save transaction message to notification table for sender user
            Helpers::saveNotificationMessage($request->user(), $senderMsg);

            DB::commit();

            // Added extra parameter to fulfill iOS requirement
            $transactionDetail->_description = $transactionDetail->description;
            $isBeneficiary = UserBeneficiary::where('mobile_number',$request->mobile_number)->count();
            $isBeneficiary = (isset($isBeneficiary) && $isBeneficiary > 0) ? 1 : 0;
            $transactionDetail->isBeneficiry = $isBeneficiary;

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'action' => '',
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
            ], 500);
        }
    }

    /**
     * Transaction history of user
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactionHistory(Request $request)
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
            $userId = $request->user()->id;

            // To get history of specific transaction (e.g: add_withdraw, transfer_money, e-voucher etc.)
            $action = (isset($request->action) && !empty($request->action)) ? $request->action : '';

            // To filter by transaction type
            $filter = (isset($request->filter) && !empty($request->filter)) ? $request->filter : '';

            // Total Count
            $totalCount = UserTransaction::userTransactionCount($userId, $action, $filter);
            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, Config::get('constant.TRANSACTION_HISTORY_PER_PAGE_LIMIT'), $totalCount);

            // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'user_transactions.id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // Transaction History
            $transactionHistory = UserTransaction::userTransaction($userId, Config::get('constant.TRANSACTION_HISTORY_PER_PAGE_LIMIT'), $getPaginationData['offset'], $sort, $order, $action, $filter);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => $totalCount,
                'noOfPages' => $getPaginationData['noOfPages'],
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'filter' => (isset($request->action) && $request->action == Config::get('constant.E-VOUCHER_ACTION')) ? Config::get('constant.E_VOUCHER_FILTER') : Config::get('constant.TRANSACTION_FILTER'),
                'data' => $transactionHistory,
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
     * To get recent transaction which is sent by of user
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentTransaction(Request $request)
    {
        try {
            // Recent Transaction (Transfer money by user)
            $recentTransaction = $request->user()->senderTransaction()->whereHas('receiveruser', function ($query) {
                $query->where('verification_status', '<>', Config::get('constant.UNREGISTERED_USER_STATUS'));
            })->with('receiveruser')
                ->with('receiveruserdetail');
            // Search by transaction type
            if (isset($request->type) && $request->type == Config::get('constant.E-VOUCHER_ACTION')) {
                $recentTransaction = $recentTransaction->whereIn('transaction_type', [Config::get('constant.E_VOUCHER_TRANSACTION_TYPE'), Config::get('constant.REDEEMED_TRANSACTION_TYPE'), Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE')]);
            }
            $recentTransaction = $recentTransaction->whereNotNull('to_user_id')
                ->take(Config::get('constant.RECENT_TRANSACTION_LIMIT'))
                ->offset(0)
                ->orderBy('id', 'DESC')
                ->get()
                ->unique('to_user_id');

            if (count($recentTransaction) == 0) {
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.default_success_msg'),
                    'data' => null,
                ], 200);
            }

            $recentTransaction = array_values($recentTransaction->toArray());

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $recentTransaction,
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

    public function transferMoneyToUnregisteredUser($senderDetail, $request)
    {
        try {

            // Get helapay fee
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
                return [
                    'status' => 0,
                    'message' => trans('apimessages.insufficient_balance_msg'),
                    'code' => 200,
                ];
            }

            // Deduct from sender balance
            $senderDetail->balance_amount -= ($request->amount + $feeData['data']['totalCommission']);
            $senderDetail->save();

            // Add fee to admin user
            $adminData = User::with('userDetail')->where('role_id', Config::get('constant.SUPER_ADMIN_ROLE_ID'))->first();
            // Upgrade commission wallet balance
            $adminData->userDetail->commission_wallet_balance += $feeData['data']['helapayCommission'];
            $adminData->userDetail->save();


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

            // Save into transfer money request and send OTP to unregistered user
            $otp = mt_rand(100000, 999999);

            // Save transaction history
            $transaction = new UserTransaction([
                'to_user_id' => $guestUser->id,
                'amount' => ($request->amount + $feeData['data']['totalCommission']),
                'net_amount' => $request->amount,
                'total_commission_amount' => $feeData['data']['totalCommission'],
                'admin_commission_amount' => $feeData['data']['helapayCommission'],
                'description' => (isset($request->description)) ? $request->description : null,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.SUCCESS_TRANSACTION_STATUS'),
                'transaction_type' => Config::get('constant.E_VOUCHER_TRANSACTION_TYPE'),
                'otp' => $otp,
                'created_by' => $request->user()->id,
            ]);

            $request->user()->senderTransaction()->save($transaction);

            // Transfer alert to unregistered user
            $receiverMsg = strtr(trans('apimessages.message_to_unregistered_user_for_transfer'), [
                '<Value>' => number_format($request->amount, 2),
                '<User Name>' => $request->user()->full_name,
                '<User Mobile Number>' => Helpers::maskString($request->user()->mobile_number),
                '<Authorization Code>' => implode('-', str_split($otp, 3)),
                // '<Transaction ID>' => $transaction->transaction_id,
            ]);
            $transactionMsgToReceiver = Helpers::sendMessage($request->mobile_number, $receiverMsg);

            $om = new OTPManagement();
            $om->otp = $otp;
            $om->from_user = $request->user()->id;
            $om->to_user = $guestUser->id;
            $om->otp_sent_to = $request->mobile_number;
            $om->amount = $request->amount;
            $om->operation = Config::get('constant.OTP_O_E_VOUCHER_SENT_VERIFICATION');
            $om->created_by = $request->user()->id;
            $om->message = $receiverMsg;
            $om->save();

            // Save transaction message to notification table for receiver user
            Helpers::saveNotificationMessage($guestUser, $receiverMsg);

            if (!$transactionMsgToReceiver) {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.something_went_wrong'),
                    'code' => 200,
                ];
            }

            // Transfer alert to sender
            $senderMsg = strtr(trans('apimessages.TRANSACTION_MESSAGE_TO_SENDER_AFTER_SEND_MONEY_TO_UNREGISTERED_USER'), [
                '<Sender Name>' => 'You',
                '<Value>' => number_format($request->amount, 2),
                '<Receiver Mobile Number>' => Helpers::maskString($request->mobile_number),
                '<Transaction ID>' => $transaction->transaction_id,
                '<Fee>' => number_format($feeData['data']['totalCommission'],2),
                '<Sender Balance Amount>' => number_format($senderDetail->balance_amount, 2),
            ]);
            $transactionMsgTosender = Helpers::sendMessage($request->user()->mobile_number, $senderMsg);

            // Success message for transfer to unregistered user
            $successMsg = strtr(trans('apimessages.success_message_to_unregistered_user_for_transfer'), [
                '<Mobile Number>' => $request->mobile_number,
                '<Value>' => $request->amount,
            ]);

            // Save transaction message to notification table for sender user
            Helpers::saveNotificationMessage($request->user(), $senderMsg);

            $transactionDetail = $transaction->with('receiveruser')->with('receiveruserdetail')->where('id', $transaction->id)->first();

            // All good so return the response
            return [
                'status' => 1,
                'message' => $successMsg,
                'code' => 200,
                'authorizationCode' => (Config::get('constant.DISPLAY_OTP') == 1) ? $otp : '',
                'action' => Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE'),
                'data' => $transactionDetail,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'code' => 500,
            ];
        }
    }
}
