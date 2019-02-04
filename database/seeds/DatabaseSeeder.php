<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RolesTableSeeder::class,
            UsersTableSeeder::class,
            UserDetailsTableSeeder::class,
            CountryCurrencyTableSeeder::class,
            CommissionManagementTableSeeder::class,
            SettingsTableSeeder::class,
            PermissoinsTableSeeder::class,
        ]);
    }
}
