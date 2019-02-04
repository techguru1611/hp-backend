<?php

namespace App\Http\Controllers;

use App\AuditTransaction;
use App\CountryCurrency;
use App\Helpers\CommissionService;
use App\Helpers\Helpers;
use App\OTPManagement;
use App\User;
use App\UserBeneficiary;
use App\UserDetail;
use App\UserTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BeneficiaryTransactionController extends Controller
{

    public function __construct()
    {
        $this->objUser = new User();
    }

    /**
     * convert amount according to currency
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function convertAmount(Request $request){
        try{
            $rule = [
                'amount' => 'required',
                'sender_currency' => 'required',
                'receiver_currency' => 'required',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $amount = $request->amount;
            $receiver_currency = $request->receiver_currency;
            $sender_currency = $request->sender_currency;
            // convert amount
            $data = CommissionService::convertAmount($amount, $sender_currency, $receiver_currency);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $data,
            ], 200);
        } catch (\Exception $e){
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage()
            ]));

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    public function transferFee(Request $request){
        try {
            // Rule Validation Start
            $rule = [
                'amount' => 'required',
                'beneficiary_id' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $beneficiary = UserBeneficiary::find($request->beneficiary_id);


            if (!$beneficiary){
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.beneficiary_not_found'),
                ], 200);
            }

            // Validate receiver mobile number
            $validateMobileNumber = Helpers::validateMobileNumber($beneficiary->mobile_number, $request->user()->mobile_number);
            $userType = Config::get('constant.ACTIVE_FLAG');;
            // If user is not verified
            if (isset($validateMobileNumber['status']) && $validateMobileNumber['status'] == 0) {
                // Transfer money to un-registered user
                if ($validateMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) {
                    // Create unregistered user
                    $guestUser = $this->objUser->where('mobile_number', $beneficiary->mobile_number)->first();
                    if ($guestUser === null) {
                        $guestUser = $this->objUser->insertUpdate([
                            'full_name' => $beneficiary->name,
                            'mobile_number' => $beneficiary->mobile_number,
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
                $userType = Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE');
            }

            // Get helapay fee data
            if ($validateMobileNumber['code'] == Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE')) {
                $feeData = CommissionService::calculateFee($request->amount, Config::get('constant.SEND_E_VOUCHER_FEE'));
            } else {
                $feeData = CommissionService::calculateFee($request->amount, Config::get('constant.BENEFICIARY_TRANSFER_FEE'));
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
            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'action' => $userType,
                'data' => [
                    'receiver_full_name' => $beneficiary->name,
                    'receiver_nick_name' => $beneficiary->nick_name,
                    'receiver_mobile_number' => $beneficiary->mobile_number,
                    'total_amount' => number_format($request->amount + $feeData['data']['totalCommission'], 2, '.', ''),
                    'amount' => number_format($request->amount + $feeData['data']['totalCommission'], 2, '.', ''),
                    'netAmount' => number_format($request->amount, 2, '.', ''),
                    'totalCommission' => number_format($feeData['data']['totalCommission'], 2, '.', ''),
                    'agentCommission' => number_format($feeData['data']['agentCommission'], 2, '.', ''),
                    'helapayCommission' => number_format($feeData['data']['helapayCommission'], 2, '.', ''),
                ],
            ], 200);
        } catch (\Exception $e){
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage()
            ]));

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    public function sendMoney(Request $request)
    {
        try{
            $rule = [
                'amount' => 'required',
                'beneficiary_id' => 'required|integer|min:1',
                'description' => 'nullable'
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $beneficiary = UserBeneficiary::find($request->beneficiary_id);

            if (!$beneficiary){
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.beneficiary_not_found'),
                ], 200);
            }
            // Check sender balance
            $senderDetail = $request->user()->userDetail()->lockForUpdate()->first();

            // Validate receiver mobile number
            $validateMobileNumber = Helpers::validateMobileNumber($beneficiary->mobile_number, $request->user()->mobile_number);
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
            $feeData = CommissionService::calculateFee($request->amount, Config::get('constant.BENEFICIARY_TRANSFER_FEE'));

            // Handle error
            if ($feeData['status'] == 0) {
                return response()->json([
                    'status' => $feeData['status'],
                    'message' => $feeData['message'],
                ], $feeData['code']);
            }

            // If sender doesn't have sufficient balance
            if ($senderDetail->balance_amount < $request->amount) {
                Log::channel('transaction')->error(strtr(trans('log_messages.insufficient_balance'), [
                    '<Mobile Number>' => $request->user()->mobile_number,
                ]));
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.insufficient_balance_msg'),
                ], 200);
            }

            DB::beginTransaction();

            $receiverUser = $this->objUser->where('mobile_number', $beneficiary->mobile_number)->first();
            $receiverDetail = $receiverUser->userDetail()->lockForUpdate()->first();
            $receiverDetail->balance_amount += $request->amount;
            $receiverDetail->save();

            // Deduct from sender balance
            $senderDetail->balance_amount -= ($request->amount + $feeData['data']['totalCommission']);
            $senderDetail->save();

            // Add commission to admin user
            $adminData = User::with('userDetail')->where('role_id', Config::get('constant.SUPER_ADMIN_ROLE_ID'))->first();
            // Upgrade commission wallet balance
            $adminData->userDetail->commission_wallet_balance += $feeData['data']['helapayCommission'];;
            $adminData->userDetail->save();

            // Save transaction history
            $transaction = new UserTransaction([
                'to_user_id' => $receiverUser->id,
                'beneficiary_id' => $request->beneficiary_id,
                'amount' => ($request->amount + $feeData['data']['totalCommission']),
                'net_amount' => $request->amount,
                'total_commission_amount' => $feeData['data']['totalCommission'],
                'admin_commission_amount' => $feeData['data']['helapayCommission'],
                'description' => (isset($request->description)) ? $request->description : null,
                'transaction_id' => 'tr_' . str_random(12),
                'transaction_status' => Config::get('constant.SUCCESS_TRANSACTION_STATUS'),
                'transaction_type' => Config::get('constant.BENEFICIARY_TRANSFER_TYPE'),
                'created_by' => $request->user()->id,
            ]);

            $request->user()->senderTransaction()->save($transaction);
            Log::channel('transaction')->info(strtr(trans('log_messages.beneficiary_transfer'),[
                '<To User>' => $beneficiary->mobile_number,
                '<By User>' => $request->user()->mobile_number,
                '<Amount>' => $request->amount,
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

            $transactionMsgToReceiver = Helpers::sendMessage($beneficiary->mobile_number, $receiverMsg);

            // Save transaction message to notification table for receiver user
            Helpers::saveNotificationMessage($receiverUser, $receiverMsg);

            // Transfer alert to sender
            $senderMsg = strtr(trans('apimessages.MESSAGE_TO_SENDER_AFTER_SENT_MONEY_TO_BENEFICIARY'), [
                '<Value>' => number_format($request->amount, 2),
                '<Receiver Name>' => $beneficiary->name,
                '<Receiver Account Number>' => Helpers::maskString($beneficiary->mobile_number),
                '<Fee>' => number_format($feeData['data']['totalCommission'],2),
                '<Transaction ID>' => $transaction->transaction_id,
                '<Sender Balance Amount>' => number_format($senderDetail->balance_amount, 2),
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
        } catch (\Exception $e){
            DB::rollBack();
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage()
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

            $beneficiary = UserBeneficiary::find($request->beneficiary_id);
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
            $guestUser = $this->objUser->where('mobile_number', $beneficiary->mobile_number)->first();
            if ($guestUser === null) {
                $guestUser = $this->objUser->insertUpdate([
                    'full_name' => $beneficiary->name,
                    'mobile_number' => $beneficiary->mobile_number,
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
                'beneficiary_id' => $request->beneficiary_id,
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
            $transactionMsgToReceiver = Helpers::sendMessage($beneficiary->mobile_number, $receiverMsg);

            $om = new OTPManagement();
            $om->otp = $otp;
            $om->from_user = $request->user()->id;
            $om->to_user = $guestUser->id;
            $om->otp_sent_to = $beneficiary->mobile_number;
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
            $senderMsg = strtr(trans('apimessages.MESSAGE_TO_SENDER_AFTER_SENT_MONEY_TO_BENEFICIARY'), [
                '<Value>' => number_format($request->amount, 2),
                '<Receiver Name>' => $beneficiary->name,
                '<Receiver Account Number>' => Helpers::maskString($beneficiary->mobile_number),
                '<Fee>' => number_format($feeData['data']['totalCommission'],2),
                '<Transaction ID>' => $transaction->transaction_id,
                '<Sender Balance Amount>' => number_format($senderDetail->balance_amount, 2),
            ]);
            $transactionMsgTosender = Helpers::sendMessage($request->user()->mobile_number, $senderMsg);

            // Success message for transfer to unregistered user
            $successMsg = strtr(trans('apimessages.success_message_to_unregistered_user_for_transfer'), [
                '<Mobile Number>' => $beneficiary->mobile_number,
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
