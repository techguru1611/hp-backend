<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnsIntoUserTransactionsTable extends Migration
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
                $table->integer('approved_by')->unsigned()->nullable()->after('transaction_by');
                $table->dateTime('approved_at')->nullable()->after('approved_by');
                $table->integer('created_by')->unsigned()->nullable()->after('approved_at');

                $table->foreign('approved_by')->references('id')->on('users');
                $table->foreign('created_by')->references('id')->on('users');
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
                $table->dropForeign('user_transactions_approved_by_foreign');
                $table->dropForeign('user_transactions_created_by_foreign');

                $table->dropColumn([
                    'approved_by',
                    'approved_at',
                    'created_by'
                ]);
            });
        }
    }
}
