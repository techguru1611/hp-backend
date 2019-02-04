<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewCommissionRelatedColumnIntoTransactionTable extends Migration
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
                $table->decimal('admin_commission_amount_from_receiver', 15, 2)->unsigned()->after('admin_commission_amount');
                $table->integer('receiver_commission_admin_id')->unsigned()->nullable()->after('commission_admin_id');

                $table->dropColumn([
                    'admin_commission_in_percentage',
                ]);

                $table->foreign('receiver_commission_admin_id')->references('id')->on('users');
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
                $table->dropForeign('user_transactions_receiver_commission_admin_id_foreign');
                $table->dropColumn([
                    'admin_commission_amount_from_receiver',
                    'receiver_commission_admin_id'
                ]);

                $table->decimal('admin_commission_in_percentage', 5, 2)->unsigned()->after('admin_commission_amount');
            });
        }
    }
}
