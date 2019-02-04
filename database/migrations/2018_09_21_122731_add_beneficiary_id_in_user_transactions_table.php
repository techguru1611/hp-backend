<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBeneficiaryIdInUserTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->integer('beneficiary_id')->unsigned()->nullable()->after('to_user_id');

            $table->foreign('beneficiary_id')->references('id')->on('user_beneficiaries');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->dropForeign('user_transactions_beneficiary_id_foreign');
            $table->dropColumn(['beneficiary_id']);
        });
    }
}
