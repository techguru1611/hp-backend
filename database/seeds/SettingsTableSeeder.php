<?php

use Illuminate\Database\Seeder;
use App\Settings;
use Carbon\Carbon;
use Config;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Settings Table Seeder
        \DB::statement("SET FOREIGN_KEY_CHECKS=0");

        $settings = [
            [
                'name' => 'Company Logo',
                'slug' => 'logo',
                'value' => '',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Company Name',
                'slug' => 'company_name',
                'value' => Config::get('constant.COMPANY_NAME'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'E-Voucher Validity Duration',
                'slug' => 'e-voucher_validity',
                'value' => '90', // Days
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Copy Right Text',
                'slug' => 'copy_right_string',
                'value' => '',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Default Transfer Fee',
                'slug' => 'default_transfer_fee',
                'value' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Default Add To Wallet Fee',
                'slug' => 'default_add_to_wallet_fee',
                'value' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Default Send E-Voucher fee',
                'slug' => 'default_send_evoucher_fee',
                'value' => '1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Company Email',
                'slug' => 'company_email',
                'value' => Config::get('constant.COMPANY_EMAIL'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Company Phone Number',
                'slug' => 'company_phone_number',
                'value' => Config::get('constant.COMPANY_PHONE_NO'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Company Address',
                'slug' => 'company_address',
                'value' => Config::get('constant.COMPANY_ADDRESS'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Company Facebook',
                'slug' => 'company_facebook_url',
                'value' => Config::get('constant.COMPANY_FACEBOOK_URL'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Company Twitter',
                'slug' => 'company_twitter_url',
                'value' => Config::get('constant.COMPANY_TWITTER_URL'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Company Url',
                'slug' => 'company_url',
                'value' => Config::get('constant.COMPANY_URL'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Default Latitude',
                'slug' => 'default_latitude',
                'value' => Config::get('constant.DEFAULT_AGENT_LATITUDE'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Default Longitude',
                'slug' => 'default_longitude',
                'value' => Config::get('constant.DEFAULT_AGENT_LONGITUDE'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ];

        foreach($settings as $_setting)
        {
            Settings::firstOrCreate([
                'name' => $_setting['name'],
                'slug' => $_setting['slug'],
                'value' => $_setting['value'],
                'created_at' => $_setting['created_at'],
                'updated_at' => $_setting['updated_at'],
            ]);
        }
        \DB::statement("SET FOREIGN_KEY_CHECKS=1");
    }
}
