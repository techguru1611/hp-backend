<?php

use Illuminate\Database\Seeder;
use App\Helpers\Helpers;
use App\TrangloCommonCode;
use Carbon\Carbon;

class TrangloCommonCodeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        TrangloCommonCode::truncate();

        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
        /** 
         *  1 - Purpose 
         *  2 - Source of fund 
         *  3 - Sender and Beneficiary Relationship 
         *  4 - Account Type 
         *  5 - Sender Identification Type 
         *  6 - Beneficiary Identification Type 
         **/
        /** Code Type 1 - Purpose Code */
        $csvPath = database_path() . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR . 'csv' . DIRECTORY_SEPARATOR . 'Purpose-Code-List.txt';
        $items = Helpers::convertCSVToArray($csvPath);
        
        foreach ($items as $key => $item) {
            $row = TrangloCommonCode::create([
                'code_type' => 1,
                'code' => $item['code'],
                'code_description' => $item['description'],
                'created_at' => Carbon::now()
            ]);
        }

        /** Code Type 2 - Source of fund */
        $csvPath = database_path() . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR . 'csv' . DIRECTORY_SEPARATOR . 'Source-of-fund-list.txt';
        $items = Helpers::convertCSVToArray($csvPath);

        foreach ($items as $key => $item) {
            $row = TrangloCommonCode::create([
                'code_type' => 2,
                'code' => $item['code'],
                'code_description' => $item['description'],
                'created_at' => Carbon::now()
            ]);
        }

        /** Code Type 3 - Sender & Beneficiary Relationship */
        $csvPath = database_path() . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR . 'csv' . DIRECTORY_SEPARATOR . 'Sender-and-Beneficiary-Relationship.txt';
        $items = Helpers::convertCSVToArray($csvPath);

        foreach ($items as $key => $item) {
            $row = TrangloCommonCode::create([
                'code_type' => 3,
                'code' => $item['code'],
                'code_description' => $item['description'],
                'created_at' => Carbon::now()
            ]);
        }

        /** Code Type 4 - Account Type */
        $csvPath = database_path() . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR . 'csv' . DIRECTORY_SEPARATOR . 'Sender-and-Beneficiary-Account-Type.txt';
        $items = Helpers::convertCSVToArray($csvPath);

        foreach ($items as $key => $item) {
            $row = TrangloCommonCode::create([
                'code_type' => 4,
                'code' => $item['code'],
                'code_description' => $item['description'],
                'created_at' => Carbon::now()
            ]);
        }

        /** Code Type 5 - Sender Identification Type */
        $csvPath = database_path() . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR . 'csv' . DIRECTORY_SEPARATOR . 'Sender-Identification-Type.txt';
        $items = Helpers::convertCSVToArray($csvPath);

        foreach ($items as $key => $item) {
            $row = TrangloCommonCode::create([
                'code_type' => 5,
                'code' => $item['code'],
                'code_description' => $item['description'],
                'created_at' => Carbon::now()
            ]);
        }

        /** Code Type 6 - Beneficiary Identification Type */
        $csvPath = database_path() . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR . 'csv' . DIRECTORY_SEPARATOR . 'Beneficiary-Identification-Type.txt';
        $items = Helpers::convertCSVToArray($csvPath);

        foreach ($items as $key => $item) {
            $row = TrangloCommonCode::create([
                'code_type' => 6,
                'code' => $item['code'],
                'code_description' => $item['description'],
                'created_at' => Carbon::now()
            ]);
        }
    }
}
