<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommissionManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commission_management', function (Blueprint $table) {
            $table->increments('id');
            $table->double('start_range', 15, 2)->nullable();
            $table->double('end_range', 15, 2)->nullable();
            $table->string('amount_range')->nullable();
            $table->float('admin_commission', 5, 2);
            $table->float('agent_commission', 5, 2)->comment('It is calculated from admin commision / In percentage');
            $table->float('government_share', 5, 2);
            $table->smallInteger('transaction_type')->comment('1 - Add Money, 2 - Withdraw Money, 3 - One to one Transaction, 4 - Cash In, 5 - e-voucher, 6 - Redeem, 7 - e-voucher cash out, 8 - Cash Out');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('agent_id')->unsigned()->nullable();
            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('agent_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('commission_management', function (Blueprint $table) {
            $table->dropForeign('commission_management_agent_id_foreign');
            $table->dropForeign('commission_management_created_by_foreign');
            $table->dropForeign('commission_management_updated_by_foreign');
        });

        Schema::dropIfExists('commission_management');
    }
}
