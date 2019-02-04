<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOtpSentToColumnInCashInOutRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('cash_in_out_request')) {
            Schema::table('cash_in_out_request', function (Blueprint $table) {
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
        if (Schema::hasTable('cash_in_out_request')) {
            Schema::table('cash_in_out_request', function (Blueprint $table) {
                $table->dropColumn([
                    'otp_sent_to'
                ]);
            });
        }
    }
}
