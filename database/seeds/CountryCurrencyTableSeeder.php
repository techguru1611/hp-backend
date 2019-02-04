<?php

use Illuminate\Database\Seeder;
use App\CountryCurrency;
use Carbon\Carbon;

class CountryCurrencyTableSeeder extends Seeder
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

        $country = [
            [
                'id' => 1,
                'country_name' => 'Zambia',
                'country_code' => 'ZMW',
                'calling_code' => '+260',
                'unit' => 0.00,
                'sort_order' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 2,
                'country_name' => 'Democratic Republic of Congo',
                'country_code' => 'CDF',
                'calling_code' => '+243',
                'unit' => 0.00,
                'sort_order' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 3,
                'country_name' => 'Denmark',
                'country_code' => 'DKK',
                'calling_code' => '+45',
                'unit' => 0.00,
                'sort_order' => 3,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 4,
                'country_name' => 'India',
                'country_code' => 'INR',
                'calling_code' => '+91',
                'unit' => 0.00,
                'sort_order' => 4,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 5,
                'country_name' => 'Namibia',
                'country_code' => 'NAD',
                'calling_code' => '+264',
                'unit' => 0.00,
                'sort_order' => 5,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 6,
                'country_name' => 'South Africa',
                'country_code' => 'ZAR',
                'calling_code' => '+27',
                'unit' => 0.00,
                'sort_order' => 6,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 7,
                'country_name' => 'Zimbabwe',
                'country_code' => 'ZWD',
                'calling_code' => '+263',
                'unit' => 0.00,
                'sort_order' => 7,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 8,
                'country_name' => 'United Kingdom',
                'country_code' => 'GBP',
                'calling_code' => '+44',
                'unit' => 0.00,
                'sort_order' => 8,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 9,
                'country_name' => 'France',
                'country_code' => 'EUR',
                'calling_code' => '+33',
                'unit' => 0.00,
                'sort_order' => 9,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 10,
                'country_name' => 'Sweden',
                'country_code' => 'SEK',
                'calling_code' => '+46',
                'unit' => 0.00,
                'sort_order' => 10,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 11,
                'country_name' => 'Rwanda',
                'country_code' => 'RWF',
                'calling_code' => '+250',
                'unit' => 0.00,
                'sort_order' => 11,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 12,
                'country_name' => 'Uganda',
                'country_code' => 'UGX',
                'calling_code' => '+256',
                'unit' => 0.00,
                'sort_order' => 12,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 13,
                'country_name' => 'Nigeria',
                'country_code' => 'NGN',
                'calling_code' => '+234',
                'unit' => 0.00,
                'sort_order' => 13,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        foreach($country as $_country)
        {
            CountryCurrency::firstOrCreate([
                'id' => $_country['id'],
                'country_name' => $_country['country_name'],
                'country_code' => $_country['country_code'],
                'calling_code' => $_country['calling_code'],
                'unit' => $_country['unit'],
                'sort_order' => $_country['sort_order'],
                'created_at' => $_country['created_at'],
                'updated_at' => $_country['updated_at'],
            ]);
        }
        \DB::statement("SET FOREIGN_KEY_CHECKS=1");
    }
}
