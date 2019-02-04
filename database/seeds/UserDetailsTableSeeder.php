<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class UserDetailsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::statement("SET FOREIGN_KEY_CHECKS=0");

        // \DB::table('user_details')->truncate();

        \DB::table('user_details')->insert(array(
            0 =>
            array(
                'id' => 1,
                'user_id' => 1,
                'balance_amount' => 1000000,
                'country_code' => 'ZK',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => NULL,
            ),
        ));

        \DB::statement("SET FOREIGN_KEY_CHECKS=1");
    }
}
