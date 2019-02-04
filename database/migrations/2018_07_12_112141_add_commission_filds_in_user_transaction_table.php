<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommissionFildsInUserTransactionTable extends Migration
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
                $table->decimal('net_amount', 15, 2)->after('amount');
                $table->decimal('admin_commission_amount', 15, 2)->after('net_amount');
                $table->decimal('admin_commission_in_percentage', 5, 2)->after('admin_commission_amount');
                $table->decimal('agent_commission_amount', 15, 2)->after('admin_commission_in_percentage');
                $table->decimal('agent_commission_in_percentage', 5, 2)->after('agent_commission_amount');
                $table->integer('commission_agent_id')->unsigned()->nullable()->after('agent_commission_in_percentage');

                $table->foreign('commission_agent_id')->references('id')->on('users');
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
                $table->dropForeign('user_transactions_commission_agent_id_foreign');

                $table->dropColumn([
                    'net_amount',
                    'admin_commission_amount',
                    'admin_commission_in_percentage',
                    'agent_commission_amount',
                    'agent_commission_in_percentage',
                    'commission_agent_id',
                ]);
            });
        }
    }
}
