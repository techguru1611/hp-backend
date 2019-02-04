<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnForUnregistredNumberInTransferMoneyRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('transfer_money_request')) {
            Schema::table('transfer_money_request', function (Blueprint $table) {
                $table->string('unregistered_number')->nullable()->after('otp_created_at');
                $table->integer('created_by')->nullable()->after('unregistered_number');
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
        if (Schema::hasTable('transfer_money_request')) {
            Schema::table('transfer_money_request', function (Blueprint $table) {
                $table->dropColumn([
                    'unregistered_number',
                    'created_by'
                ]);
            });
        }
    }
}
