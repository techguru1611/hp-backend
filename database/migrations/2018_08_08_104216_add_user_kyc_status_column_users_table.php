<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserKycStatusColumnUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                /**
                 * user_kyc_status - 0 : Pending to upload | 1 : Document Submitted | 2 : Completed or Approved.
                 */
                $table->integer('user_kyc_status')->default(0)->after('kyc_approved_at')->comment('0 : Pending to upload | 1 : Document Submitted | 2 : Completed or Approved. Holds status about user KYC document is uploaded or not.');
                $table->timestamp('kyc_uploaded_at')->nullable()->after('user_kyc_status')->comment('Holds date time when user upload the documents');
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
        //
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'user_kyc_status',
                    'kyc_uploaded_at',
                ]);
            });
        }
    }
}
