<?php

use Illuminate\Database\Seeder;
use App\Commission;
use Carbon\Carbon;

class CommissionManagementTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Set foreign key constraint to zero
        \DB::statement("SET FOREIGN_KEY_CHECKS=0");

        \DB::statement("TRUNCATE TABLE commission_management");

        $commission = [
            [
                'start_range' => 0.01,
                'end_range' => 50.00,
                'amount_range' => '0.01-50.00',
                'admin_commission' => 1.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'start_range' => 50.01,
                'end_range' => 150.00,
                'amount_range' => '50.01-150.00',
                'admin_commission' => 1.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'start_range' => 150.01,
                'end_range' => 250.00,
                'amount_range' => '150.01-250.00',
                'admin_commission' => 2.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'start_range' => 250.01,
                'end_range' => 350.00,
                'amount_range' => '250.01-350.00',
                'admin_commission' => 4.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'start_range' => 350.01,
                'end_range' => 500.00,
                'amount_range' => '350.01-500.00',
                'admin_commission' => 5.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'start_range' => 500.01,
                'end_range' => 1000.00,
                'amount_range' => '500.01-1000.00',
                'admin_commission' => 10.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'start_range' => 1000.01,
                'end_range' => 2000.00,
                'amount_range' => '1000.01-2000.00',
                'admin_commission' => 20.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'start_range' => 2000.01,
                'end_range' => 3000.00,
                'amount_range' => '2000.01-3000.00',
                'admin_commission' => 30.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'start_range' => 3000.01,
                'end_range' => 4000.00,
                'amount_range' => '3000.01-4000.00',
                'admin_commission' => 40.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'start_range' => 4000.01,
                'end_range' => 5000.00,
                'amount_range' => '4000.01-5000.00',
                'admin_commission' => 50.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

        ];

        foreach($commission as $_commission)
        {
            Commission::firstOrCreate([
                'start_range' => $_commission['start_range'],
                'end_range' => $_commission['end_range'],
                'amount_range' => $_commission['amount_range'],
                'admin_commission' => $_commission['admin_commission'],
                'created_at' => $_commission['created_at'],
                'updated_at' => $_commission['updated_at'],
            ]);
        }
        \DB::statement("SET FOREIGN_KEY_CHECKS=1");
    }
}
