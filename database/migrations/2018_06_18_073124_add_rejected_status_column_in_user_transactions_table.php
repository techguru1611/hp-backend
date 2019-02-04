<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRejectedStatusColumnInUserTransactionsTable extends Migration
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
                $table->integer('rejected_by')->unsigned()->nullable()->after('approved_at');
                $table->dateTime('rejected_at')->nullable()->after('rejected_by');

                $table->foreign('rejected_by')->references('id')->on('users');
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
                $table->dropForeign('user_transactions_rejected_by_foreign');

                $table->dropColumn([
                    'rejected_by',
                    'rejected_at'
                ]);
            });
        }
    }
}
