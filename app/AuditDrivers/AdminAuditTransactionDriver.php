<?php

/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2018
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace App\AuditDrivers;

use OwenIt\Auditing\Contracts\Audit as AuditModel;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\AuditDriver;
use Carbon\Carbon;
use JWTAuth;
use Config;
use App\AuditTransaction;
use App\Audit;

/**
 * Class will manage the transacation detail which is done by the Admin user.
 * It will handles following type of transaction
 * 1 - Add Money, 
 * 2 - Withdraw Money, 
 * 3 - One to one Transaction, 
 * 4 - Cash In, 
 * 5 - e-voucher, 
 * 6 - Redeem, 
 * 7 - e-voucher cash out, 
 * 8 - Cash Out, 
 * 9 - Add commission to wallet, 
 * 10 - Withdraw commission
 */
class AdminAuditTransactionDriver implements AuditDriver
{
    /**
     * Perform an audit.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return \OwenIt\Auditing\Contracts\Audit
     */
    public function audit(Auditable $model) : AuditModel
    {
        /**
         * @todo: @auditTransactionTypes variable needs to move to language or constant folder.
         */
        $auditTransactionTypes = [
            1 => 'Add Money',
            2 => 'Withdraw Money',
            3 => 'One to one Transaction',
            4 => 'Cash In',
            5 => 'e-voucher',
            6 => 'Redeem',
            7 => 'e-voucher cash out',
            8 => 'Cash Out',
            9 => 'Add commission to wallet',
            10 => 'Withdraw commission'
        ];

        /**
         * We are only targeting Admin user rest of the user will be by pass Audit Transaction.
         */
        if (JWTAuth::user() && JWTAuth::user()->hasRole(Config::get('constant.SUPER_ADMIN_SLUG'))) {
            $columnArray = $model->toAudit();
            // dd($columnArray);

            $auditTransaction = new AuditTransaction();
            $auditTransaction->transaction_date = Carbon::now();
            $auditTransaction->transaction_id = $columnArray["new_values"]['transaction_id'];
            $auditTransaction->action_id = $columnArray["new_values"]['transaction_type'];
            $auditTransaction->action_type = $auditTransactionTypes[$columnArray["new_values"]['transaction_type']];

            /**
             * Action detail needs to up setup properly.
             * For time being its set as transaction type.
             */
            $auditTransaction->action_detail = $auditTransactionTypes[$columnArray["new_values"]['transaction_type']];
            $auditTransaction->modified_by = JWTAuth::user()->id;

            $auditTransaction->event = $columnArray["event"];
            $auditTransaction->url = isset($columnArray["url"]) ? $columnArray["url"] : "";
            $auditTransaction->ip_address = isset($columnArray["ip_address"]) ? $columnArray["ip_address"] : "";
            $auditTransaction->user_agent = isset($columnArray["user_agent"]) ? $columnArray["user_agent"] : "";

            $auditTransaction->save();
        }

        return Audit::create($model->toAudit());
    }

    /**
     * Remove older audits that go over the threshold.
     *
     * @param \OwenIt\Auditing\Contracts\Auditable $model
     *
     * @return bool
     */
    public function prune(Auditable $model) : bool
    {
        // TODO: Implement the pruning logic
        return false;
    }
}