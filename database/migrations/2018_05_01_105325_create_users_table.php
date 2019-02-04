<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('role_id')->unsigned();
            $table->string('full_name',100);
            $table->string('mobile_number',20)->unique()->nullable();
            $table->string('email',150)->unique()->nullable();
            $table->string('password');
            $table->string('otp',20)->nullable();
            $table->timestamp('otp_date')->nullable();
            $table->timestamp('otp_created_date')->nullable();
            $table->tinyInteger('verification_status')->default(0)->comment('0 - Status, 1 - Approved, 2 - Reject');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('role_id')->references('id')->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('users');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
