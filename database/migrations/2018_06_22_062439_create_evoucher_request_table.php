<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEvoucherRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evoucher_request', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('from_user_id')->unsigned()->nullable();
            $table->integer('to_user_id')->unsigned()->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('otp_sent_to')->nullable();
            $table->string('otp', 10)->nullable();
            $table->dateTime('otp_created_at')->nullable();
            $table->string('unregistered_number')->nullable();
            $table->integer('created_by')->unsigned()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('from_user_id')->references('id')->on('users');
            $table->foreign('to_user_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('evoucher_request', function (Blueprint $table) {
            $table->dropForeign('evoucher_request_from_user_id_foreign');
            $table->dropForeign('evoucher_request_to_user_id_foreign');
            $table->dropForeign('evoucher_request_created_by_foreign');
        });
        Schema::dropIfExists('evoucher_request');
    }
}
