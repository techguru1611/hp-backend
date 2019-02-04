<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUserBeneficiariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_beneficiaries')) {
            Schema::table('user_beneficiaries', function (Blueprint $table) {
                $table->string('last_name', 100)->nullable()->change();
                /** Beneficiary contact information */
                $table->string('email', 200)->nullable()->change();
                $table->string('country_code', 5)->nullable()->change();

                /** These parameters will be used by Tranglo API */
                $table->integer('relationship')->unsigned()->nullable()->change();
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
        if (Schema::hasTable('user_beneficiaries')) {
            Schema::table('user_beneficiaries', function (Blueprint $table) {
                $table->string('last_name', 100)->change();
                /** Beneficiary contact information */
                $table->string('email', 200)->change();
                $table->string('country_code', 5)->change();

                /** These parameters will be used by Tranglo API */
                $table->integer('relationship')->unsigned()->change();
            });
        }
    }
}
