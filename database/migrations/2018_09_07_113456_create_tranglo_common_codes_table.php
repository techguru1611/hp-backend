<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrangloCommonCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tranglo_common_codes', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('code_type')->comment('1 - Purpose | 2 - Source of fund | 3 - Sender and Beneficiary Relationship | 4 - Account Type | 5 - Sender Identification Type | 6 - Beneficiary Identification Type');
            $table->string('code', 50);
            $table->string('code_description', 255);

            $table->timestamps();
            $table->softDeletes();
        });

        /** Adding FK for user beneficiaries. */
        if (Schema::hasTable('user_beneficiaries')) {
            Schema::table('user_beneficiaries', function (Blueprint $table) {
                $table->foreign('relationship')->references('id')->on('tranglo_common_codes');
                
            });
        }
        
        if (Schema::hasTable('user_beneficiary_bank_details')) {
            Schema::table('user_beneficiary_bank_details', function (Blueprint $table) {
                $table->foreign('account_type')->references('id')->on('tranglo_common_codes');
                $table->foreign('identification_type')->references('id')->on('tranglo_common_codes');
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
        Schema::dropIfExists('tranglo_common_codes');
    }
}