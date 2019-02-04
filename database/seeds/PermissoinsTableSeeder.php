<?php

use Illuminate\Database\Seeder;
use App\Permission;

class PermissoinsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $permissions = [
            'Transfer Money' => 'transfer_money',
            'Withdraw Money' => 'withdraw_money',
            'Cash In' => 'cash_in',
            'Cash Out' => 'cash_out',
            'Add Money' => 'add_money',
            'E-Voucher Redeem (Add To Wallet)' => 'e_voucher_redeem_add_to_wallet',
            'E-Voucher Send' => 'e_voucher_send'
        ];

        foreach($permissions as $permission => $slug) {
            $isThere = Permission::where('slug', '=', $slug)->first();

            if(!$isThere) {
                Permission::firstOrCreate([
                    'slug' => $slug, 
                    'name' => $permission,
                    'created_by' => 1
                ]);
            }
        }
    }
}
