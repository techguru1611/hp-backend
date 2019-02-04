<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOtpSentToColumnInAgentAddWithdrawMoneyRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('agent_add_withdraw_money_request')) {
            Schema::table('agent_add_withdraw_money_request', function (Blueprint $table) {
                $table->string('otp_sent_to', 20)->nullable()->after('user_id');
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
        if (Schema::hasTable('agent_add_withdraw_money_request')) {
            Schema::table('agent_add_withdraw_money_request', function (Blueprint $table) {
                $table->dropColumn([
                    'otp_sent_to'
                ]);
            });
        }
    }
}
