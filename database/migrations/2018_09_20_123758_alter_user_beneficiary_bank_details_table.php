<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUserBeneficiaryBankDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_beneficiary_bank_details')) {
            Schema::table('user_beneficiary_bank_details', function (Blueprint $table) {
                $table->integer('identification_type')->unsigned()->nullable()->change();
                $table->string('bank_name', 100)->nullable()->change();
                $table->string('address', 255)->nullable()->change();
                $table->integer('country')->unsigned()->nullable()->change();
                $table->integer('state')->unsigned()->nullable()->change();
                $table->string('city', 50)->nullable()->change();
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
        if (Schema::hasTable('user_beneficiary_bank_details')) {
            Schema::table('user_beneficiary_bank_details', function (Blueprint $table) {
                $table->integer('identification_type')->change();
                $table->string('bank_name', 100)->change();
                $table->string('address', 255)->change();
                $table->integer('country')->unsigned()->change();
                $table->integer('state')->unsigned()->change();
                $table->string('city', 50)->change();
            });
        }
    }
}
