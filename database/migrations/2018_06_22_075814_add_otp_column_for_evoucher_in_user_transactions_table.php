<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOtpColumnForEvoucherInUserTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_transactions')) {
            Schema::table('user_transactions', function (Blueprint $table) {
                
                $table->string('otp', 10)->nullable()->after('rejected_at');
                $table->integer('voucher_redeemed_from')->unsigned()->nullable()->after('otp');
                $table->dateTime('voucher_redeemed_at')->nullable()->after('voucher_redeemed_from');

                $table->foreign('voucher_redeemed_from')->references('id')->on('users');
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
        if (Schema::hasTable('user_transactions')) {
            Schema::table('user_transactions', function (Blueprint $table) {
                $table->dropForeign('user_transactions_voucher_redeemed_from_foreign');
                $table->dropColumn([
                    'otp',
                    'voucher_redeemed_from',
                    'voucher_redeemed_at',
                ]);
            });
        }
    }
}
