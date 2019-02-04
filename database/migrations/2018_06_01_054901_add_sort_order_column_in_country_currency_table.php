<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortOrderColumnInCountryCurrencyTable extends Migration
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
                $table->integer('sort_order')->nullable()->after('amount');
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
                $table->dropColumn(['sort_order']);
            });
        }
    }
}
