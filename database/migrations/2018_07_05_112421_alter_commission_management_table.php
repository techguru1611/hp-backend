<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCommissionManagementTable extends Migration
{
    public function __construct()
    {
        \DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('commission_management')) {
            Schema::table('commission_management', function (Blueprint $table) {
                $table->float('agent_commission', 5, 2)->nullable()->default(null)->comment('It is calculated from admin commision / In percentage')->change();
                $table->float('government_share', 5, 2)->nullable()->default(null)->change();
                $table->smallInteger('transaction_type')->nullable()->default(null)->comment('1 - Add Money, 2 - Withdraw Money, 3 - One to one Transaction, 4 - Cash In, 5 - e-voucher, 6 - Redeem, 7 - e-voucher cash out, 8 - Cash Out')->change();
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
        if (Schema::hasTable('commission_management')) {
            Schema::table('commission_management', function (Blueprint $table) {
                $table->float('agent_commission', 5, 2)->nullable(false)->comment('It is calculated from admin commision / In percentage')->change();
                $table->float('government_share', 5, 2)->nullable(false)->change();
                $table->smallInteger('transaction_type')->nullable(false)->comment('1 - Add Money, 2 - Withdraw Money, 3 - One to one Transaction, 4 - Cash In, 5 - e-voucher, 6 - Redeem, 7 - e-voucher cash out, 8 - Cash Out');
            });
        }
    }
}
