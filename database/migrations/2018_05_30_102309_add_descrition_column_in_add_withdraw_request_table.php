<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescritionColumnInAddWithdrawRequestTable extends Migration
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
                $table->text('description')->nullable()->after('amount');
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
                $table->dropColumn(['description']);
            });
        }
    }
}
