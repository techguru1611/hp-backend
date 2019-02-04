<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UserTransaction;
use Config;
use Carbon\Carbon;
use DB;
use App\Settings;

class ExpireEvoucher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'helapay:expireEvoucher';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire e-voucher sent before e-voucher expiry days'; // Admin can update e-voucher expiry days 

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $evoucherExpiryDaysSetting = Settings::where('slug', Config::get('constant.E_VOUCHER_VALIDITY_SETTING_SLUG'))->first();

        $expiryDaysLimit = ($evoucherExpiryDaysSetting != null ? ($evoucherExpiryDaysSetting->value > 0 ? intval($evoucherExpiryDaysSetting->value) : Config::get('constant.DEFAULT_EVOUCHER_EXPIRE_DAYS')) : Config::get('constant.DEFAULT_EVOUCHER_EXPIRE_DAYS'));
        UserTransaction::where('transaction_type', Config::get('constant.E_VOUCHER_TRANSACTION_TYPE'))
            ->whereDate('created_at', '<=', Carbon::now()->subDays($expiryDaysLimit)->format('Y-m-d'))
            ->chunk(100, function ($transactions) {
                DB::transaction(function () use (&$transactions) {
                    foreach($transactions as $transaction) {
                        $transaction->transaction_status = Config::get('constant.EXPIRED_TRANSACTION_STATUS');
                        $transaction->save();

                        // Cash back to sender user
                        $transaction->senderuserdetail->balance_amount += $transaction->amount;
                        $transaction->senderuserdetail->save();
                    }
                });
            });
    }
}
