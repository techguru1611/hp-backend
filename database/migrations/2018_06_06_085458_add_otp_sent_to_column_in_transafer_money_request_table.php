<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOtpSentToColumnInTransaferMoneyRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('transfer_money_request')) {
            Schema::table('transfer_money_request', function (Blueprint $table) {
                $table->string('otp_sent_to', 20)->nullable()->after('description');
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
        if (Schema::hasTable('transfer_money_request')) {
            Schema::table('transfer_money_request', function (Blueprint $table) {
                $table->dropColumn([
                    'otp_sent_to'
                ]);
            });
        }
    }
}
