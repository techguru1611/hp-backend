<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashInOutRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_in_out_request', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->comment('agent_id');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->enum('action', ['cashin', 'cashout'])->default('cashin');
            $table->string('otp', 10)->nullable();
            $table->dateTime('otp_created_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash_in_out_request', function (Blueprint $table) {
            $table->dropForeign('cash_in_out_request_user_id_foreign');
        });
        Schema::dropIfExists('cash_in_out_request');
    }
}
