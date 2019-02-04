<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtpManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('otp_management', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('from_user')->unsigned()->nullable();
            $table->integer('to_user')->unsigned()->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('message')->nullable();
            $table->string('otp_sent_to')->nullable();
            $table->string('otp', 10)->nullable();
            $table->tinyInteger('operation')->comment('1: login, 2: register, 3: forgot_password, 4: add_money_verification, 5: approve_add_money_verification, 6: withdraw_money_verification, 7: approve_withdraw_money_verification, 8: add_commission_to_wallet_verification, 9: approve_add_commission_to_wallet_verification, 10: withdraw_commission_from_wallet_verification, 11: approve_withdraw_commission_from_wallet_verification, 12: transfer_money_verification, 13: cash_in_verification, 14: cash_out_verification, 15: e-voucher_sent_verification, 16: e-voucher_authorization_code, 17: e-voucher_cash_out_verification, 18: e-voucher_add_to_wallet_verification, 19: e-voucher_add_to_wallet_verification');
            $table->integer('created_by')->unsigned()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('from_user')->references('id')->on('users');
            $table->foreign('to_user')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('otp_management', function (Blueprint $table) {
            $table->dropForeign('otp_management_from_user_foreign');
            $table->dropForeign('otp_management_to_user_foreign');
            $table->dropForeign('otp_management_created_by_foreign');
        });

        Schema::dropIfExists('otp_management');
    }
}
