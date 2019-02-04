<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddKycColumnsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->tinyInteger('kyc_status')->default(0)->comment('0 - Pending | 1 = Approved | 2 = Rejected | 3 = Correction')->after('verification_status_updater_role');
                $table->integer('kyc_approved_by')->unsigned()->nullable()->after('kyc_status');
                $table->timestamp('kyc_approved_at')->nullable()->after('kyc_approved_by');

                $table->foreign('kyc_approved_by')->references('id')->on('users');
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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('users_kyc_approved_by_foreign');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'kyc_status',
                    'kyc_approved_by',
                    'kyc_approved_at'
                ]);
            });
        }
    }
}
