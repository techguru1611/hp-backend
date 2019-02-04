<?php

use Illuminate\Database\Seeder;
use Config;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        \DB::statement("SET FOREIGN_KEY_CHECKS=0");

        // \DB::table('users')->truncate();

        \DB::table('users')->insert(array(
            0 =>
            array(
                'id' => 1,
                'role_id' => 1,
                'full_name' => Config::get('constant.HELAPAY_ADMIN_NAME'),
                'mobile_number' => Config::get('constant.ADMIN_MOBILE_NO'),
                'email' => Config::get('constant.ADMIN_EMAIL_NO'),
                'password' => bcrypt('12345678'),
                'otp' => NULL,
                'otp_date' => NULL,
                'otp_created_date' => NULL,
                'verification_status' => 1,
                'created_at' => '2018-04-01 17:04:00',
                'updated_at' => '2018-04-01 17:04:00',
                'deleted_at' => NULL,
            ),
        ));

        \DB::statement("SET FOREIGN_KEY_CHECKS=1");
    }
}
