<?php

namespace App;

use Config;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class UserTransaction extends Model
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['otp', 'voucher_redeemed_from', 'voucher_redeemed_at'];

    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'approved_at', 'rejected_at'];

    public function senderuser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function receiveruser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function receiveruserdetail()
    {
        return $this->belongsTo(UserDetail::class, 'to_user_id', 'user_id');
    }

    public function senderuserdetail()
    {
        return $this->belongsTo(UserDetail::class, 'from_user_id', 'user_id');
    }

    /**
     * To get user transaction
     * @param Integer $userId User Id
     * @return Transaction collection
     */
    public static function userTransactionCount($userId, $action, $filter)
    {
        $whereClause = self::handleTransactionAction($action);
        $whereTransactionTypeClause = self::handleTransactionFilter($filter, $userId);

        // Users transaction count
        $userTransactionCount = UserTransaction::whereRaw($whereClause)
            ->whereRaw($whereTransactionTypeClause)
            ->where(function($query) use ($userId) {
                $query->where('from_user_id', $userId)
                    ->orWhere('to_user_id', $userId)
                    ->orWhere('commission_agent_id', $userId);
            })
            ->count();
        return $userTransactionCount;
    }

    /**
     * To get user transaction
     * @param Integer $userId User Id
     * @return Transaction collection
     */
    public static function userTransaction($userId, $limit, $offset, $sort, $order, $action, $filter)
    {
        $whereClause = self::handleTransactionAction($action);
        $whereTransactionTypeClause = self::handleTransactionFilter($filter, $userId);

        return DB::select(DB::raw("
        SELECT
            user_transactions.id,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.ADDED_TO_WALLET') . "'
                WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.MONEY_WITHDRAW') . "'
                WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.ADDED_TO_WALLET') . "'
                WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.MONEY_WITHDRAW') . "'
                WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.CASE_IN') . "'
                WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_SENT_STATUS') . "'
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_CASHEDOUT_STATUS') . "'
                        WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_CASHEDOUT_STATUS') . "'
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_REDEEM') . "'
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_REDEEMED') . "'
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.MONEY_SENT') . "'
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.MONEY_RECEIVED') . "'
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_SENT') . "'
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_RECEIVED') . "'
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . "' THEN '" . Config::get('constant.BENEFICIARY_TRANSFER') . "'
                ELSE '" . Config::get('constant.CASE_OUT') . "'
            END
            AS transaction_type,
            user_transactions.transaction_id,
            user_transactions.created_at,
            user_transactions.description,
            user_transactions.description AS _description,
            user_transactions.amount,
            user_transactions.net_amount,
            user_transactions.total_commission_amount,
            user_transactions.admin_commission_amount,
            user_transactions.admin_commission_amount_from_receiver,
            user_transactions.agent_commission_amount,
            agent.full_name AS agent_full_name,
            agent.mobile_number AS agent_mobile_number,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN user_transactions.amount
                WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN '-'
                WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN user_transactions.amount
                WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN 
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN user_transactions.amount
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN user_transactions.amount
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN '-'
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN user_transactions.amount
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN user_transactions.amount
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN user_transactions.amount
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN 
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN user_transactions.amount
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . "' THEN 
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN  user_transactions.net_amount
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                    END    
            END
            AS withdrawal,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN user_transactions.amount
                WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN user_transactions.amount
                WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN '-'
                WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN 
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN  '-'
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN user_transactions.net_amount
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN  '-'
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN user_transactions.net_amount
                        WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN user_transactions.net_amount
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN user_transactions.net_amount
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN user_transactions.net_amount
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN user_transactions.net_amount
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN 
                    CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN  user_transactions.net_amount
                        WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                    END
                WHEN user_transactions.transaction_type = '" . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . "' THEN 
                    CASE WHEN user_transactions.to_user_id = '" . $userId . "' THEN  user_transactions.net_amount
                        WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                    END   
            END
            AS deposit,
            CASE WHEN user_transactions.transaction_status = '" . Config::get('constant.PENDING_TRANSACTION_STATUS') . "' THEN 'Pending'
                WHEN user_transactions.transaction_status = '" . Config::get('constant.SUCCESS_TRANSACTION_STATUS') . "' THEN 'Success'
                WHEN user_transactions.transaction_status = '" . Config::get('constant.REJECTED_TRANSACTION_STATUS') . "' THEN 'Rejected'
                ELSE 'Failed'
            END
            AS transaction_status,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.TRANSACTION_ADDED_TO_WALLET_STATUS') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.TRANSACTION_WITHDRAW_FROM_WALLET_STATUS') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.TRANSACTION_ADDED_COMMISSION_TO_WALLET_STATUS') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.TRANSACTION_WITHDRAW_COMMISSION_FROM_WALLET_STATUS') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.TRANSACTION_CASH_IN_STATUS') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.TRANSACTION_E_VOUCHER_CASHED_OUT_STATUS') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.TRANSACTION_E_VOUCHER_CASHED_OUT_STATUS') . "'
                            WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN '" . Config::get('constant.TRANSACTION_E_VOUCHER_CASHED_OUT_STATUS') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.TRANSACTION_E_VOUCHER_ADDED_TO_WALLET_STATUS') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.TRANSACTION_E_VOUCHER_ADDED_TO_WALLET_STATUS') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.TRANSACTION_MONEY_SENT_STATUS') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.TRANSACTION_MONEY_RECEIVED_STATUS') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.TRANSACTION_E_VOUCHER_SENT_STATUS') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.TRANSACTION_E_VOUCHER_RECEIVED_STATUS') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . "' THEN '" . Config::get('constant.BENEFICIARY_TRANSFER') . "'
                    ELSE '" . Config::get('constant.TRANSACTION_CASH_OUT_STATUS') . "'
                END
            AS status,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.HELAPAY_ADMIN_NAME') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.HELAPAY_ADMIN_NAME') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN CONCAT(from_user.full_name, ' ', '(Agent)')
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN CONCAT(to_user.full_name, ' ', '(Sender)')
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN CONCAT(from_user.full_name, ' ', '(Sender)')
                            WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN
                                CASE WHEN user_transactions.to_user_id IS NULL THEN CONCAT(from_user.full_name, ' ', '(Sender)')
                                    WHEN to_user.verification_status='" . Config::get('constant.UNREGISTERED_USER_STATUS') . "' THEN '" . Config::get('constant.GUEST_NAME') . "'
                                    ELSE CONCAT(to_user.full_name, ' ', '(Sender)')
                                END
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN CONCAT(from_user.full_name, ' ', '(Sender)')
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN CONCAT(from_user.full_name, ' ', '(Sender)')
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN CONCAT(from_user.full_name, ' ', '(Sender)')
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . "' THEN   
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN from_user.full_name
                        END    
                END
            AS from_val,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN from_user.mobile_number
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN to_user.mobile_number
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN from_user.mobile_number
                            WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN
                                CASE WHEN user_transactions.to_user_id IS NULL THEN from_user.mobile_number
                                    ELSE to_user.mobile_number
                                END
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN from_user.mobile_number
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN from_user.mobile_number
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN from_user.mobile_number
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN from_user.mobile_number
                        END
                END
            AS from_number_val,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN 
                        CASE WHEN user_transactions.external_user_id is not null THEN external_users.name
                            ELSE '" . Config::get('constant.HELAPAY_ADMIN_NAME') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.HELAPAY_ADMIN_NAME') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN CONCAT(to_user.full_name, ' ', '(Receiver)')
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN CONCAT(from_user.full_name, ' ', '(Agent)')
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN
                                CASE WHEN user_transactions.to_user_id IS NULL THEN '" . Config::get('constant.SELF_USER') . "'
                                    WHEN to_user.verification_status='" . Config::get('constant.UNREGISTERED_USER_STATUS') . "' THEN '" . Config::get('constant.GUEST_NAME') . "'
                                    ELSE CONCAT(to_user.full_name, ' ', '(Receiver)')
                                END
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN
                                CASE WHEN user_transactions.to_user_id IS NULL THEN '" . Config::get('constant.SELF_USER') . "'
                                    WHEN to_user.verification_status='" . Config::get('constant.UNREGISTERED_USER_STATUS') . "' THEN '" . Config::get('constant.GUEST_NAME') . "'
                                    ELSE CONCAT(to_user.full_name, ' ', '(Receiver)')
                                END
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN CONCAT(to_user.full_name, ' ', '(Receiver)')
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN
                                CASE WHEN user_transactions.to_user_id IS NULL THEN '" . Config::get('constant.SELF_USER') . "'
                                    WHEN to_user.verification_status='" . Config::get('constant.UNREGISTERED_USER_STATUS') . "' THEN '" . Config::get('constant.GUEST_NAME') . "'
                                    ELSE  CONCAT(to_user.full_name, ' ', '(Receiver)')
                                END
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . "' THEN  
                        CASE WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.from_user_id = '" . $userId . "' THEN beneficiaries.name
                        END
                END
            AS to_val,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN to_user.mobile_number
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN from_user.mobile_number
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN
                                CASE WHEN user_transactions.to_user_id IS NULL THEN from_user.mobile_number
                                    ELSE to_user.mobile_number
                                END
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN 
                                CASE WHEN user_transactions.to_user_id IS NULL THEN from_user.mobile_number
                                    ELSE to_user.mobile_number
                                END
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN to_user.mobile_number
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN
                                CASE WHEN user_transactions.to_user_id IS NULL THEN from_user.mobile_number
                                    ELSE to_user.mobile_number
                                END
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . "' THEN  
                        CASE WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.from_user_id = '" . $userId . "' THEN beneficiaries.mobile_number
                        END
                END
            AS to_number_val,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN CONCAT(agent.full_name, ' ', '(Agent)')
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN CONCAT(agent.full_name, ' ', '(Agent)')
                            WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN CONCAT(agent.full_name, ' ', '(Agent)')
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                END
            AS agent_val,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN agent.mobile_number
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN agent.mobile_number
                            WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN agent.mobile_number
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                END
            AS agent_number_val,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN '" . url('/images/plus.png') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN '" . url('/images/minus.png') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN '" . url('/images/plus.png') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN '" . url('/images/minus.png') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . url('/images/minus.png') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . url('/images/plus.png') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . url('/images/plus.png') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . url('/images/minus.png') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . url('/images/minus.png') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . url('/images/plus.png') . "'
                            WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN '" . url('/images/plus.png') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . url('/images/minus.png') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . url('/images/plus.png') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . url('/images/minus.png') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . url('/images/plus.png') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . url('/images/minus.png') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . url('/images/plus.png') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . url('/images/minus.png') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . url('/images/plus.png') . "'
                        END      
                END
            AS image,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN '+'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN '+'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN '-'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '+'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '+'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '-'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '+'
                            WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN '+'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '+'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '+'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '+'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '-'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '+'
                        END    
                END
            AS sign,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN ''
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN ''
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN ''
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN ''
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN ''
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN ''
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN ''
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN ''
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_CASHED_OUT_BUTTON_TEXT') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_CASHED_OUT_BUTTON_TEXT') . "'
                            WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_CASHED_OUT_BUTTON_TEXT') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_ADDED_TO_WALLET_BUTTON_TEXT') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_ADDED_TO_WALLET_BUTTON_TEXT') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN ''
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN ''
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_RESEND_BUTTON_TEXT') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.E_VOUCHER_ADD_TO_WALLET_BUTTON_TEXT') . "'
                        END
                     WHEN user_transactions.transaction_type = '" . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN ''
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN ''
                        END    
                END
            AS button_text,
            CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                            WHEN user_transactions.commission_agent_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_ENABLE_STATUS') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_ENABLE_STATUS') . "'
                        END
                    WHEN user_transactions.transaction_type = '" . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . "' THEN
                        CASE WHEN user_transactions.from_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                            WHEN user_transactions.to_user_id = '" . $userId . "' THEN '" . Config::get('constant.BUTTON_DISABLE_STATUS') . "'
                        END    
                END
            AS button_status
        from user_transactions
            LEFT JOIN users AS from_user ON from_user.id = user_transactions.from_user_id
            LEFT JOIN users AS to_user ON to_user.id = user_transactions.to_user_id
            LEFT JOIN users AS agent ON agent.id = user_transactions.commission_agent_id
            LEFT JOIN user_beneficiaries AS beneficiaries ON beneficiaries.id = user_transactions.beneficiary_id
            LEFT JOIN external_users ON external_users.id = user_transactions.external_user_id
            WHERE $whereClause
            AND $whereTransactionTypeClause
            AND (user_transactions.from_user_id = '" . $userId . "' OR user_transactions.to_user_id = '" . $userId . "' OR user_transactions.commission_agent_id = '" . $userId . "')
            AND user_transactions.deleted_at IS NULL
            ORDER BY $sort $order, user_transactions.id DESC
            LIMIT " . $limit . " OFFSET " . $offset . "
            "));
    }

    /**
     * To get all transaction count
     * @param Integer $limit no of records per page
     * @param Integer $offset records start from
     * @param String $sort sort parameter
     * @param String $order ASC / DESC
     * @param Integer $searchByUser User id from which you want to search for
     * @param String $searchByTransactionID Search parameter
     * @param String $searchByTransactionStatus Search parameter
     * @param String $searchByTransactionType Search parameter
     * @param String $searchByToUserName Search parameter
     * @param String $searchByTransactionCreatedAt Search parameter
     * @param Integer $adminId To get perticular admin history (Made by this Id)
     * @return Transaction collection
     */
    public static function allTransactionCount($searchByUser, $searchByTransactionID, $searchByTransactionStatus, $searchByTransactionType, $searchByFromUserName, $searchByToUserName, $searchByTransactionCreatedAt, $adminId = null)
    {
        // Total Count
        $totalCount = UserTransaction::join('users AS from_user', 'from_user.id', '=', 'user_transactions.from_user_id')
            ->leftJoin('users AS to_user', 'to_user.id', '=', 'user_transactions.to_user_id')
            ->leftJoin('user_beneficiaries','user_beneficiaries.id','=','user_transactions.beneficiary_id');

        // Get history of given admin id
        if ($adminId != null) {
            $totalCount = $totalCount->where('user_transactions.created_by', $adminId);
        }

        // Search by user
        if ($searchByUser !== null) {
            $totalCount = $totalCount->where(function ($query) use ($searchByUser) {
                $query->where('user_transactions.to_user_id', $searchByUser)
                    ->orWhere('user_transactions.from_user_id', $searchByUser)
                    ->orWhere('user_transactions.beneficiary_id',$searchByUser);

            });
        }
        // Search by transaction ID
        if ($searchByTransactionID !== null) {
            $totalCount = $totalCount->where('user_transactions.transaction_id', 'LIKE', "%$searchByTransactionID%");
        }

        // Search by transaction status
        if ($searchByTransactionStatus !== null) {
            $totalCount = $totalCount->where('user_transactions.transaction_status', $searchByTransactionStatus);
        }

        // Search by transaction created date
        if ($searchByTransactionCreatedAt !== null) {
            $totalCount = $totalCount->where(DB::raw('DATE_FORMAT(user_transactions.created_at, "%Y-%m-%d, %h:%i:%s %p")'), 'LIKE', "%$searchByTransactionCreatedAt%");
        }

        // Search by transaction type
        if ($searchByTransactionType !== null) {
            $totalCount = $totalCount->where('user_transactions.transaction_type', $searchByTransactionType);
        }

        // Search by From user name
        if ($searchByFromUserName !== null) {
            $totalCount = $totalCount->where(DB::raw('CASE WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" ELSE CASE WHEN from_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN from_user.mobile_number ELSE from_user.full_name END END'), 'LIKE', "%$searchByFromUserName%");
        }

        // Search by To user name
        if ($searchByToUserName !== null) {
            $totalCount = $totalCount->where(DB::raw('CASE 
                WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN 
                    CASE WHEN from_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN from_user.mobile_number 
                        ELSE from_user.full_name END WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" 
                WHEN user_transactions.transaction_type = ' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.SELF_USER') . '" 
                WHEN user_transactions.transaction_type =' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" 
                WHEN user_transactions.transaction_type =' . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . ' THEN user_beneficiaries.name
                WHEN user_transactions.transaction_type = ' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN 
                    CASE WHEN user_transactions.to_user_id IS NULL THEN "' . Config::get('constant.SELF_USER') . '" 
                        ELSE CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN to_user.mobile_number 
                            ELSE to_user.full_name END 
                    END 
                WHEN user_transactions.transaction_type = ' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN 
                    CASE WHEN user_transactions.to_user_id IS NULL THEN "' . Config::get('constant.SELF_USER') . '" 
                        ELSE CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN to_user.mobile_number 
                            ELSE to_user.full_name END 
                    END 
                        ELSE CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN to_user.mobile_number 
                ELSE to_user.full_name END 
                END'), 'LIKE', "%$searchByToUserName%");
        }

        $totalCount = $totalCount->count();
        return $totalCount;
    }

    /**
     * To get all transaction data
     * @param Integer $limit no of records per page
     * @param Integer $offset records start from
     * @param String $sort sort parameter
     * @param String $order ASC / DESC
     * @param Integer $searchByUser User id from which you want to search for
     * @param String $searchByTransactionID Search parameter
     * @param String $searchByTransactionStatus Search parameter
     * @param String $searchByTransactionType Search parameter
     * @param String $searchByToUserName Search parameter
     * @param String $searchByTransactionCreatedAt Search parameter
     * @param Integer $adminId To get perticular admin history (Made by this Id)
     * @return Transaction collection
     */
    public static function allTransactionHistory($limit, $offset, $sort, $order, $searchByUser, $searchByTransactionID, $searchByTransactionStatus, $searchByTransactionType, $searchByFromUserName, $searchByToUserName, $searchByTransactionCreatedAt, $adminId = null)
    {
        $allTransactions = UserTransaction::join('users AS from_user', 'from_user.id', '=', 'user_transactions.from_user_id')
            ->leftJoin('users AS to_user', 'to_user.id', '=', 'user_transactions.to_user_id')
            ->leftJoin('users AS agent_user', 'agent_user.id', '=', 'user_transactions.commission_agent_id')
            ->leftJoin('roles AS from_user_role', 'from_user_role.id', '=', 'from_user.role_id')
            ->leftJoin('roles AS to_user_role', 'to_user_role.id', '=', 'to_user.role_id')
            ->leftJoin('user_beneficiaries','user_beneficiaries.id','=','user_transactions.beneficiary_id')
            ->leftJoin('external_users','external_users.id','=','user_transactions.external_user_id');

        // Get history of given admin id
        if ($adminId != null) {
            $allTransactions = $allTransactions->where('user_transactions.created_by', $adminId);
        }

        // Search by user
        if ($searchByUser !== null) {
            $allTransactions = $allTransactions->where(function ($query) use ($searchByUser) {
                $query->where('user_transactions.to_user_id', '=', $searchByUser)
                    ->orWhere('user_transactions.from_user_id', '=', $searchByUser);
            });
        }

        // Search by transaction ID
        if ($searchByTransactionID !== null) {
            $allTransactions = $allTransactions->where('user_transactions.transaction_id', 'LIKE', "%$searchByTransactionID%");
        }

        // Search by transaction status
        if ($searchByTransactionStatus !== null) {
            $allTransactions = $allTransactions->where('user_transactions.transaction_status', $searchByTransactionStatus);
        }

        // Search by transaction created date
        if ($searchByTransactionCreatedAt !== null) {
            $allTransactions = $allTransactions->where(DB::raw('DATE_FORMAT(user_transactions.created_at, "%Y-%m-%d, %h:%i:%s %p")'), 'LIKE', "%$searchByTransactionCreatedAt%");
        }

        // Search by transaction type
        if ($searchByTransactionType !== null) {
            $allTransactions = $allTransactions->where('user_transactions.transaction_type', $searchByTransactionType);
        }

        // Search by From user name
        if ($searchByFromUserName !== null) {
            $allTransactions = $allTransactions->where(DB::raw('CASE WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" ELSE CASE WHEN from_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN from_user.mobile_number ELSE from_user.full_name END END'), 'LIKE', "%$searchByFromUserName%");
        }

        // Search by To user name
        if ($searchByToUserName !== null) {
            $allTransactions = $allTransactions->where(DB::raw('CASE WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN CASE WHEN from_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN from_user.mobile_number ELSE from_user.full_name END WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" WHEN user_transactions.transaction_type =' . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . ' THEN user_beneficiaries.name WHEN user_transactions.transaction_type = ' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.SELF_USER') . '" WHEN user_transactions.transaction_type =' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" WHEN user_transactions.transaction_type = ' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN CASE WHEN user_transactions.to_user_id IS NULL THEN "' . Config::get('constant.SELF_USER') . '" ELSE CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN to_user.mobile_number ELSE to_user.full_name END END WHEN user_transactions.transaction_type = ' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN CASE WHEN user_transactions.to_user_id IS NULL THEN "' . Config::get('constant.SELF_USER') . '" ELSE CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN to_user.mobile_number ELSE to_user.full_name END END ELSE CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN to_user.mobile_number ELSE to_user.full_name END END'), 'LIKE', "%$searchByToUserName%");
        }

        $allTransactions = $allTransactions->orderBy($sort, $order)
            ->orderBy('id', 'DESC')
            ->take($limit)
            ->offset($offset)
            ->get([
                'user_transactions.id',
                'user_transactions.transaction_id',
                'user_transactions.amount',
                'user_transactions.net_amount',
                'user_transactions.total_commission_amount',
                'user_transactions.admin_commission_amount',
                'user_transactions.admin_commission_amount_from_receiver',
                'user_transactions.agent_commission_amount',
                'user_transactions.created_at',
                'user_transactions.description',
                'agent_user.full_name AS agent_full_name',
                'agent_user.mobile_number AS agent_mobile_number',
                DB::raw('CASE WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" ELSE CASE WHEN from_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN from_user.mobile_number ELSE from_user.full_name END END AS from_user_name'),
                DB::raw('CASE 
                    WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN 
                        CASE WHEN from_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN from_user.mobile_number 
                            ELSE from_user.full_name END 
                    WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN 
                        CASE WHEN user_transactions.external_user_id is not null THEN external_users.name
                            ELSE "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" 
                        END     
                    WHEN user_transactions.transaction_type = ' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.SELF_USER') . '" 
                    WHEN user_transactions.transaction_type =' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" 
                    WHEN user_transactions.transaction_type = ' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN 
                        CASE WHEN user_transactions.to_user_id IS NULL THEN "' . Config::get('constant.SELF_USER') . '" 
                            ELSE CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN to_user.mobile_number 
                                ELSE to_user.full_name END 
                        END 
                    WHEN user_transactions.transaction_type = ' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN 
                        CASE WHEN user_transactions.to_user_id IS NULL THEN "' . Config::get('constant.SELF_USER') . '" 
                            ELSE CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN to_user.mobile_number 
                                ELSE to_user.full_name END 
                        END 
                    WHEN user_transactions.transaction_type=' . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . ' THEN user_beneficiaries.name      
                    ELSE CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN to_user.mobile_number ELSE to_user.full_name END 
                    END AS to_user_name'),
                DB::raw('CASE 
                    WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.SUPER_ADMIN_ROLE_NAME') . '" 
                    ELSE from_user_role.name END AS from_user_role'),
                DB::raw('CASE 
                    WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN from_user_role.name 
                    WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN 
                        CASE WHEN user_transactions.external_user_id is not null THEN "' . Config::get('constant.EXTERNAL_USER') . '"
                             ELSE "' . Config::get('constant.SUPER_ADMIN_ROLE_NAME') . '"
                        END     
                    WHEN user_transactions.transaction_type = ' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN 
                        CASE WHEN user_transactions.to_user_id IS NULL THEN from_user_role.name 
                            ELSE to_user_role.name 
                        END 
                    WHEN user_transactions.transaction_type = ' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN 
                        CASE WHEN user_transactions.to_user_id IS NULL THEN from_user_role.name 
                            ELSE to_user_role.name 
                        END 
                    ELSE to_user_role.name 
                END AS to_user_role'),

                DB::raw('CASE
                    WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.ADDED_TO_WALLET') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.MONEY_WITHDRAW') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.ADDED_COMMISSION_TO_WALLET') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.WITHDRAW_COMMISSION_FROM_WALLET') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSFER_MONEY') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.E_VOUCHER_SENT_STATUS') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.E_VOUCHER_REDEEM_STATUS') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.E_VOUCHER_CASHOUT_STATUS') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.CASE_IN') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.BENEFICIARY_TRANSFER_TYPE') . ' THEN "' . Config::get('constant.BENEFICIARY_TRANSFER') . '"
                    ELSE "' . Config::get('constant.CASE_OUT') . '"
                END
                AS transaction_type'),
                DB::raw('CASE
                    WHEN user_transactions.transaction_status=' . Config::get('constant.PENDING_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.PENDING_TRANSACTION') . '"
                    WHEN user_transactions.transaction_status=' . Config::get('constant.SUCCESS_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.SUCCESS_TRANSACTION') . '"
                    WHEN user_transactions.transaction_status=' . Config::get('constant.REJECTED_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.REJECTED_TRANSACTION') . '"
                    ELSE "' . Config::get('constant.FAILED_TRANSACTION') . '"
                END
                AS transaction_status'),
            ]);
        return $allTransactions;
    }

    /**
     * To get commission transaction data count
     * @param Integer $userId User Id
     * @param String $filter filter key
     * @return Transaction collection
     */
    public static function agentCommissionTransactionCount($userId, $filter)
    {
        $whereTransactionTypeClause = self::handleTransactionFilter($filter, $userId);
        return $totalCount = UserTransaction::where('commission_agent_id', $userId)->whereRaw($whereTransactionTypeClause)->count();
    }

    /**
     * To get commission transaction data
     * @param Integer $userId User Id
     * @param String $filter filter key
     * @return Transaction collection
     */
    public static function agentCommissionTransaction($userId, $limit, $offset, $sort, $order, $filter)
    {
        $whereTransactionTypeClause = self::handleTransactionFilter($filter, $userId);
        return UserTransaction::join('users AS from_user', 'from_user.id', '=', 'user_transactions.from_user_id')
            ->leftJoin('users AS to_user', 'to_user.id', '=', 'user_transactions.to_user_id')
            ->where('commission_agent_id', $userId)
            ->whereRaw($whereTransactionTypeClause)
            ->orderBy($sort, $order)
            ->orderBy('id', 'DESC')
            ->take($limit)
            ->offset($offset)
            ->get([
                'user_transactions.id',
                'user_transactions.transaction_id',
                'user_transactions.created_at',
                'user_transactions.description',
                'user_transactions.description AS _description',
                'user_transactions.amount',
                'user_transactions.net_amount',
                'user_transactions.total_commission_amount',
                'user_transactions.admin_commission_amount',
                'user_transactions.admin_commission_amount_from_receiver',
                'user_transactions.agent_commission_amount',
                DB::raw('CASE WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "-" ELSE from_user.mobile_number END AS from_user_mobile_number'),
                DB::raw('CASE WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN from_user.mobile_number WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN "-" WHEN user_transactions.transaction_type = ' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN CASE WHEN user_transactions.to_user_id IS NULL THEN from_user.mobile_number ELSE to_user.mobile_number END WHEN user_transactions.transaction_type = ' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN CASE WHEN user_transactions.to_user_id IS NULL THEN from_user.mobile_number ELSE to_user.mobile_number END ELSE to_user.mobile_number END AS to_user_mobile_number'),
                DB::raw('CASE WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" ELSE CASE WHEN from_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN "' . Config::get('constant.GUEST_NAME') . '" ELSE from_user.full_name END END AS from_user_name'),
                DB::raw('CASE WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN CASE WHEN from_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN "' . Config::get('constant.GUEST_NAME') . '" ELSE from_user.full_name END WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" WHEN user_transactions.transaction_type = ' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.SELF_USER') . '" WHEN user_transactions.transaction_type =' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.HELAPAY_ADMIN_NAME') . '" WHEN user_transactions.transaction_type = ' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN CASE WHEN user_transactions.to_user_id IS NULL THEN "' . Config::get('constant.SELF_USER') . '" ELSE CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN "' . Config::get('constant.GUEST_NAME') . '" ELSE to_user.full_name END END WHEN user_transactions.transaction_type = ' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN CASE WHEN user_transactions.to_user_id IS NULL THEN "' . Config::get('constant.SELF_USER') . '" ELSE CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN "' . Config::get('constant.GUEST_NAME') . '" ELSE to_user.full_name END END ELSE CASE WHEN to_user.verification_status=' . Config::get('constant.UNREGISTERED_USER_STATUS') . ' THEN "' . Config::get('constant.GUEST_NAME') . '" ELSE to_user.full_name END END AS to_user_name'),
                DB::raw('CASE
                    WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.ADDED_TO_WALLET') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.MONEY_WITHDRAW') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.ADDED_TO_WALLET') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.MONEY_WITHDRAW') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSFER_MONEY') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.E_VOUCHER_SENT_STATUS') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.E_VOUCHER_REDEEM_STATUS') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.E_VOUCHER_CASHOUT_STATUS') . '"
                    WHEN user_transactions.transaction_type=' . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.CASE_IN') . '"
                    ELSE "' . Config::get('constant.CASE_OUT') . '"
                END
                AS transaction_type'),
                DB::raw('CASE
                    WHEN user_transactions.transaction_status=' . Config::get('constant.PENDING_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.PENDING_TRANSACTION') . '"
                    WHEN user_transactions.transaction_status=' . Config::get('constant.SUCCESS_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.SUCCESS_TRANSACTION') . '"
                    WHEN user_transactions.transaction_status=' . Config::get('constant.REJECTED_TRANSACTION_STATUS') . ' THEN "' . Config::get('constant.REJECTED_TRANSACTION') . '"
                    ELSE "' . Config::get('constant.FAILED_TRANSACTION') . '"
                END
                AS transaction_status'),
                DB::raw('CASE WHEN user_transactions.transaction_type= ' . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_ADDED_TO_WALLET_STATUS') . '"
                    WHEN user_transactions.transaction_type= ' . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_WITHDRAW_FROM_WALLET_STATUS') . '"
                    WHEN user_transactions.transaction_type= ' . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_ADDED_COMMISSION_TO_WALLET_STATUS') . '"
                    WHEN user_transactions.transaction_type= ' . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_WITHDRAW_COMMISSION_FROM_WALLET_STATUS') . '"
                    WHEN user_transactions.transaction_type= ' . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_CASH_IN_STATUS') . '"
                    WHEN user_transactions.transaction_type= ' . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_E_VOUCHER_CASHED_OUT_STATUS') . '"
                    WHEN user_transactions.transaction_type= ' . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_E_VOUCHER_ADDED_TO_WALLET_STATUS') . '"
                    WHEN user_transactions.transaction_type= ' . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSFER_MONEY') . '"
                    WHEN user_transactions.transaction_type= ' . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . ' THEN "' . Config::get('constant.TRANSACTION_E_VOUCHER_SENT_STATUS') . '"
                    ELSE "' . Config::get('constant.TRANSACTION_CASH_OUT_STATUS') . '"
                END AS status'),
                DB::raw("CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN CONCAT(from_user.full_name, ' ', '(Sender)')
                        WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN CONCAT(from_user.full_name, ' ', '(Sender)')
                        WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN CONCAT(from_user.full_name, ' ', '(Sender)')
                    END
                AS from_val"),
                DB::raw("CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN CONCAT(to_user.full_name, ' ', '(Receiver)')
                        WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN CONCAT(to_user.full_name, ' ', '(Receiver)')
                        WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                            CASE WHEN user_transactions.to_user_id IS NULL THEN CONCAT(from_user.full_name, ' ', '(Receiver)')
                                WHEN to_user.verification_status = '" . Config::get('constant.UNREGISTERED_USER_STATUS') . "' THEN '" . Config::get('constant.GUEST_NAME') . "'
                                ELSE CONCAT(to_user.full_name, ' ', '(Receiver)')
                            END
                    END
                AS to_val"),
                DB::raw("CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN from_user.mobile_number
                        WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN from_user.mobile_number
                        WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN from_user.mobile_number
                    END
                AS from_number_val"),
                DB::raw("CASE WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "' THEN to_user.mobile_number
                        WHEN user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "' THEN to_user.mobile_number
                        WHEN user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "' THEN
                            CASE WHEN user_transactions.to_user_id IS NULL THEN from_user.mobile_number
                                ELSE to_user.mobile_number
                            END
                    END
                AS to_number_val")
            ]);

    }

    public static function handleTransactionAction ($action) {
        // Switch case to handle $action
        switch ($action)
        {
            case Config::get('constant.ADD_WITHDRAW_DATA'): // code to be executed if $action = ;
                $whereClause = "transaction_type IN (" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "," . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . ")";
                break;
            case Config::get('constant.E-VOUCHER_ACTION'): // code to be executed if $action = ;
                $whereClause = "transaction_type IN (" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "," . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "," . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . ")";
                break;
            default: // code to be executed if action doesn't match any cases
                $whereClause = "1=1";
        }    
        return $whereClause;
    }

    public static function handleTransactionFilter ($filter, $userId) {
        // Switch case to handle $filter
        switch ($filter)
        {
            case Config::get('constant.E_VOUCHER_SENT_FILTER'): // code to be executed if $filter = ;
                $whereTransactionTypeClause = "user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' AND user_transactions.from_user_id = '" . $userId . "'";
                break;
            case Config::get('constant.E_VOUCHER_RECEIVED_FILTER'): // code to be executed if $filter = ;
                $whereTransactionTypeClause = "user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_TRANSACTION_TYPE') . "' AND user_transactions.to_user_id = '" . $userId . "'";
                break;
            case Config::get('constant.MONEY_SENT_FILTER'): // code to be executed if $filter = ;
                $whereTransactionTypeClause = "user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' AND user_transactions.from_user_id = '" . $userId . "'";
                break;
            case Config::get('constant.MONEY_RECEIVED_FILTER'): // code to be executed if $filter = ;
                $whereTransactionTypeClause = "user_transactions.transaction_type = '" . Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE') . "' AND user_transactions.to_user_id = '" . $userId . "'";
                break;
            case Config::get('constant.ADDED_TO_WALLET_FILTER'): // code to be executed if $filter = ;
                $whereTransactionTypeClause = "user_transactions.transaction_type = '" . Config::get('constant.ADD_MONEY_TRANSACTION_TYPE') . "'";
                break;
            case Config::get('constant.WITHDRAW_MONEY_FILTER'): // code to be executed if $filter = ;
                $whereTransactionTypeClause = "user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE') . "'";
                break;
            case Config::get('constant.CASHIN_FILTER'): // code to be executed if $filter = ;
                $whereTransactionTypeClause = "user_transactions.transaction_type = '" . Config::get('constant.CASH_IN_TRANSACTION_TYPE') . "'";
                break;
            case Config::get('constant.CASHOUT_FILTER'): // code to be executed if $filter = ;
                $whereTransactionTypeClause = "user_transactions.transaction_type = '" . Config::get('constant.CASH_OUT_TRANSACTION_TYPE') . "'";
                break;
            case Config::get('constant.ADDED_COMMISSION_TO_WALLET_FILTER'): // code to be executed if $filter = ;
                $whereTransactionTypeClause = "user_transactions.transaction_type = '" . Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE') . "'";
                break;
            case Config::get('constant.WITHDRAWAL_OF_COMMISSION_FROM_WALLET_FILTER'): // code to be executed if $filter = ;
                $whereTransactionTypeClause = "user_transactions.transaction_type = '" . Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE') . "'";
                break;
            case Config::get('constant.E_VOUCHER_ADDED_TO_WALLET_FILTER'): // code to be executed if $filter = ;
                $whereTransactionTypeClause = "user_transactions.transaction_type = '" . Config::get('constant.REDEEMED_TRANSACTION_TYPE') . "' AND user_transactions.to_user_id = '" . $userId . "'";
                break;
            case Config::get('constant.E_VOUCHER_CASHED_OUT_FILTER'): // code to be executed if $filter = ;
                $whereTransactionTypeClause = "user_transactions.transaction_type = '" . Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE') . "'";
                break;
            default: // code to be executed if filter doesn't match any cases
                $whereTransactionTypeClause = "1=1";
        }    
        return $whereTransactionTypeClause;
    }

}
