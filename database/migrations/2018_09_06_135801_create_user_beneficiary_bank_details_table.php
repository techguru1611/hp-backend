<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserBeneficiaryBankDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_beneficiary_bank_details', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('beneficiary_id')->unsigned();
            
            /** These parameters will be used by Tranglo API */
            $table->integer('account_type')->unsigned()->default(1)->comment('1 - Banks | 2 - E-Wallet Operators | 3 - Mobile Operators (Remittance) | 4 - Prepaid card | 5 - Cash Pickup | 6 - Bill Payment | 7 - Banks-SKN (Non Real-Time)(Indonesia ONLY) | 9 - Home delivery (Only available for Vietnam)');
            $table->integer('identification_type')->unsigned()->comment('1 - Work Permit | 2 - International Passport | 3 - Identification ID | 4 - Social Security | 5 - Residence Permit');

            /** Issuer code can be obtain from Tranglo APIs */
            $table->string('issuer_code', 50)->nullable();
                        
            /** Bank information */
            $table->string('bank_name', 100);
            $table->string('branch_name', 100)->nullable();
            
            /** This is only going to use for India, China, Bangladesh  */
            $table->string('swift_code', 50)->nullable();
            $table->string('ifsc_code', 50)->nullable();
            $table->string('branch_code', 50)->nullable();
            
            /** Bank contact & location information */
            $table->string('address', 255);
            $table->integer('country')->unsigned();
            $table->integer('state')->unsigned();
            $table->string('city', 50);
            

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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_beneficiary_bank_details');
    }
}
