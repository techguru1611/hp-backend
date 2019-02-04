<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentAddMoneyRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_add_money_request', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->comment('agent_id');
            $table->decimal('amount', 15, 2);
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
        Schema::table('agent_add_money_request', function (Blueprint $table) {
            $table->dropForeign('agent_add_money_request_user_id_foreign');
        });

        Schema::dropIfExists('agent_add_money_request');
    }
}
