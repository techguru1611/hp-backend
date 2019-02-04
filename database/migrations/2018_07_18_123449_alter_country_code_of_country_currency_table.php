<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCountryCodeOfCountryCurrencyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('country_currency')) {
            Schema::table('country_currency', function (Blueprint $table) {
                $table->string('country_code', 10)->nullable()->default(null)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('country_currency')) {
            Schema::table('country_currency', function (Blueprint $table) {
                $table->string('country_code', 10)->nullable(false)->change();
            });
        }
    }
}
