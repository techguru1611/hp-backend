<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserBeneficiariesTableNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('user_beneficiaries');
        Schema::dropIfExists('user_beneficiary_bank_details');
        Schema::dropIfExists('tranglo_common_codes');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        Schema::create('user_beneficiaries', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name');
            $table->string('phone_number',20);
            $table->string('account_number',30)->unique();
            $table->string('ifsc_code',50)->nullable();
            $table->string('swift_code',50)->nullable();
            $table->string('branch_code',50)->nullable();
            $table->string('bank_name',100)->nullable();
            $table->string('branch_name',100)->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('otp',20)->nullable();
            $table->timestamp('otp_date')->nullable();
            $table->timestamp('otp_created_date')->nullable();
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
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('user_beneficiaries');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        Schema::create('user_beneficiaries', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();

            /** Beneficiary personal information */
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();

            /** Beneficiary contact information */
            $table->string('email', 200)->nullable();
            $table->string('country_code', 5)->nullable();
            $table->string('phone', 13);

            /** These parameters will be used by Tranglo API */
            $table->integer('relationship')->unsigned()->nullable();

            /** Beneficiary Bankin information */
            $table->tinyInteger('verification_status')->default(0)->comment('0 - Pending, 1 - Approved, 2 - Reject');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('user_beneficiary_bank_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('beneficiary_id')->unsigned();

            /** These parameters will be used by Tranglo API */
            $table->integer('account_type')->unsigned()->default(1)->comment('1 - Banks | 2 - E-Wallet Operators | 3 - Mobile Operators (Remittance) | 4 - Prepaid card | 5 - Cash Pickup | 6 - Bill Payment | 7 - Banks-SKN (Non Real-Time)(Indonesia ONLY) | 9 - Home delivery (Only available for Vietnam)');
            $table->integer('identification_type')->unsigned()->comment('1 - Work Permit | 2 - International Passport | 3 - Identification ID | 4 - Social Security | 5 - Residence Permit')->nullable();

            /** Issuer code can be obtain from Tranglo APIs */
            $table->string('issuer_code', 50)->nullable();

            /** Bank information */
            $table->string('bank_name', 100)->nullable();
            $table->string('branch_name', 100)->nullable();

            /** This is only going to use for India, China, Bangladesh  */
            $table->string('swift_code', 50)->nullable();
            $table->string('ifsc_code', 50)->nullable();
            $table->string('branch_code', 50)->nullable();

            /** Bank contact & location information */
            $table->string('address', 255)->nullable();
            $table->integer('country')->unsigned()->nullable();
            $table->integer('state')->unsigned()->nullable();
            $table->string('city', 50)->nullable();


            $table->string('account_no', 30);

            $table->string('otp',20)->nullable();
            $table->timestamp('otp_date')->nullable();
            $table->timestamp('otp_created_date')->nullable();

            /** It's related to OTP verification. */
            $table->tinyInteger('verification_status')->default(0)->comment('0 - Pending, 1 - Approved, 2 - Reject');

            /** Only one account can be primary from multiple account */
            $table->tinyInteger('is_primary')->default(0)->comment('0 - No, 1 - Yes');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('beneficiary_id')->references('id')->on('user_beneficiaries');
        });

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
}
