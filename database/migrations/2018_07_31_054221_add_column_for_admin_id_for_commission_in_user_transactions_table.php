<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnForAdminIdForCommissionInUserTransactionsTable extends Migration
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
                $table->integer('commission_admin_id')->unsigned()->nullable()->after('agent_commission_in_percentage')->comment('User id with admin role in which commission amount added');

                $table->foreign('commission_admin_id')->references('id')->on('users');
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
                $table->dropForeign('user_transactions_commission_admin_id_foreign');

                $table->dropColumn([
                    'commission_admin_id',
                ]);
            });
        }
    }
}
