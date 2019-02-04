<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNickNameInUserBeneficiaryTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_beneficiaries', function (Blueprint $table) {
            $table->string('nick_name')->after('name')->nullable();
            $table->string('account_number',30)->nullable()->change();
            $table->renameColumn('phone_number','mobile_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_beneficiaries', function (Blueprint $table) {
            $table->dropColumn(['nick_name']);
            $table->renameColumn('mobile_number','phone_number');
        });
    }
}
