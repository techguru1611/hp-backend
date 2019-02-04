<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Config;

class OTPManagement extends Model
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'otp_management';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['from_user_id', 'to_user_id', 'amount', 'net_amount', 'otp_sent_from', 'otp_sent_to', 'otp', 'operation', 'created_by'];

    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public static function getOTPListCount($searchByOTP, $searchBySentTO, $searchByTransaction, $searchByCreatedAt){
        $OTPListCount = OTPManagement::leftJoin('users', 'users.id', '=', 'otp_management.created_by');
        // Search by OTP
        if ($searchByOTP !== null) {
            $OTPListCount = $OTPListCount->where('otp_management.otp', 'LIKE', "%$searchByOTP%");
        }

        if ($searchBySentTO !== null) {
            $OTPListCount = $OTPListCount->where('otp_management.otp_sent_to', 'LIKE', "%$searchBySentTO%");
        }

        if ($searchByTransaction !== null) {
            $OTPListCount = $OTPListCount->where('otp_management.operation', "$searchByTransaction");
        }

        // Search by login created date
        if ($searchByCreatedAt !== null) {
            $OTPListCount = $OTPListCount->where(DB::raw('DATE_FORMAT(otp_management.created_at, "%Y-%m-%d, %h:%i:%s %p")'), 'LIKE', "%$searchByCreatedAt%");
        }

        $OTPListCount = $OTPListCount->count();
        return $OTPListCount;
    }


    public static function getOTPList($limit, $offset, $sort, $order, $searchByOTP, $searchBySentTO, $searchByTransaction, $searchByCreatedAt){
        $OTPList = OTPManagement::leftJoin('users', 'users.id', '=', 'otp_management.created_by');
        // Search by OTP
        if ($searchByOTP !== null) {
            $OTPList = $OTPList->where('otp_management.otp', 'LIKE', "%$searchByOTP%");
        }

        if ($searchBySentTO !== null) {
            $OTPList = $OTPList->where('otp_management.otp_sent_to', 'LIKE', "%$searchBySentTO%");
        }

        if ($searchByTransaction !== null) {
            $OTPList = $OTPList->where('otp_management.operation', "$searchByTransaction");
        }

        // Search by login created date
        if ($searchByCreatedAt !== null) {
            $OTPList = $OTPList->where(DB::raw('DATE_FORMAT(otp_management.created_at, "%Y-%m-%d, %h:%i:%s %p")'), 'LIKE', "%$searchByCreatedAt%");
        }

        $OTPList = $OTPList->orderBy($sort, $order)
            ->orderBy('otp_management.id', 'DESC')
            ->take($limit)
            ->offset($offset)
            ->get([
                'otp_management.otp',
                'otp_management.created_at',
                'otp_management.otp_sent_to',
                'otp_management.message',
                DB::raw('CASE 
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_LOGIN'). ' THEN "'. Config::get('constant.OTP_N_LOGIN').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_REGISTER'). ' THEN "'. Config::get('constant.OTP_N_REGISTER').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_FORGOT_PASSWORD'). ' THEN "'. Config::get('constant.OTP_N_FORGOT_PASSWORD').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_ADD_MONEY_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_ADD_MONEY_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_APPROVE_ADD_MONEY_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_APPROVE_ADD_MONEY_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_WITHDRAW_MONEY_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_WITHDRAW_MONEY_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_APPROVE_WITHDRAW_MONEY_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_APPROVE_WITHDRAW_MONEY_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_ADD_COMMISSION_TO_WALLET_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_ADD_COMMISSION_TO_WALLET_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_APPROVE_ADD_COMMISSION_TO_WALLET_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_APPROVE_ADD_COMMISSION_TO_WALLET_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_WITHDRAW_COMMISSION_FROM_WALLET_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_WITHDRAW_COMMISSION_FROM_WALLET_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_APPROVE_WITHDRAW_COMMISSION_FROM_WALLET_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_APPROVE_WITHDRAW_COMMISSION_FROM_WALLET_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_TRANSFER_MONEY_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_TRANSFER_MONEY_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_CASH_IN_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_CASH_IN_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_CASH_OUT_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_CASH_OUT_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_E_VOUCHER_SENT_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_E_VOUCHER_SENT_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_E_VOUCHER_AUTHORIZATION_CODE'). ' THEN "'. Config::get('constant.OTP_N_E_VOUCHER_AUTHORIZATION_CODE').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_E_VOUCHER_CASH_OUT_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_E_VOUCHER_CASH_OUT_VERIFICATION').'"
                    WHEN otp_management.operation=' .Config::get('constant.OTP_O_E_VOUCHER_ADD_TO_WALLET_VERIFICATION'). ' THEN "'. Config::get('constant.OTP_N_E_VOUCHER_ADD_TO_WALLET_VERIFICATION').'"
                    ELSE "-"
                    END 
                    AS transaction_type'),
            ]);

        return $OTPList;
    }

}
