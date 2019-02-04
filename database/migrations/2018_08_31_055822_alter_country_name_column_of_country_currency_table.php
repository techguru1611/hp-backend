<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCountryNameColumnOfCountryCurrencyTable extends Migration
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
                $table->string('country_name', 50)->change();
                $table->string('country_code', 3)->change();
                $table->string('calling_code', 4)->change();
                $table->unsignedDecimal('amount', 15, 2)->change();
                $table->integer('sort_order')->unsigned()->change();
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
                $table->string('country_name', 255)->change();
                $table->string('country_code', 10)->change();
                $table->string('calling_code', 10)->change();
                $table->decimal('amount', 15, 5)->unsigned(false)->change();
                $table->integer('sort_order')->change();
            });
        }
    }
}
