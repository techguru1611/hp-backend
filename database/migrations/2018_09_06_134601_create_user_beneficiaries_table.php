<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserBeneficiariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_beneficiaries', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            
            /** Beneficiary personal information */
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            
            /** Beneficiary contact information */
            $table->string('email', 200);
            $table->string('country_code', 5);
            $table->string('phone', 13);

            /** These parameters will be used by Tranglo API */
            $table->integer('relationship')->unsigned();
            
            /** Beneficiary Bankin information */
            $table->tinyInteger('verification_status')->default(0)->comment('0 - Pending, 1 - Approved, 2 - Reject');

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
        Schema::dropIfExists('user_beneficiaries');
    }
}
