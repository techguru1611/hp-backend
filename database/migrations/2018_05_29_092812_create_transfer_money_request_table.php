<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransferMoneyRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfer_money_request', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('from_user_id')->unsigned()->nullable();
            $table->integer('to_user_id')->unsigned()->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('otp', 10)->nullable();
            $table->dateTime('otp_created_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('from_user_id')->references('id')->on('users');
            $table->foreign('to_user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transfer_money_request', function (Blueprint $table) {
            $table->dropForeign('transfer_money_request_from_user_id_foreign');
            $table->dropForeign('transfer_money_request_to_user_id_foreign');
        });
        Schema::dropIfExists('transfer_money_request');
    }
}
