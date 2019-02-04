<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVoucherRelatedColumnInUserTransactionsTable extends Migration
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
                $table->integer('voucher_sent_by')->unsigned()->nullable()->after('voucher_redeemed_at')->comment('Used to identify sender while unregistered user redeem voucher from agent');
                $table->string('voucher_transaction_id')->nullable()->after('voucher_sent_by')->comment('Used to identify evoucher transaction id of sender while unregistered user redeem voucher from agent');

                $table->foreign('voucher_sent_by')->references('id')->on('users');
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
                $table->dropForeign('user_transactions_voucher_sent_by_foreign');

                $table->dropColumn([
                    'voucher_sent_by',
                    'voucher_transaction_id',
                ]);
            });
        }
    }
}
