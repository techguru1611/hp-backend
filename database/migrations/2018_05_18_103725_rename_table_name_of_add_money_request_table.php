<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameTableNameOfAddMoneyRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('agent_add_money_request', 'agent_add_withdraw_money_request');
        Schema::table('agent_add_withdraw_money_request', function (Blueprint $table) {
            $table->enum('action', ['add', 'withdraw'])->default('add')->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('agent_add_withdraw_money_request', function (Blueprint $table) {
            $table->dropColumn('action');
        });
        Schema::rename('agent_add_withdraw_money_request', 'agent_add_money_request');
    }
}
