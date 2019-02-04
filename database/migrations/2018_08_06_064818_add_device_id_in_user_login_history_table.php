<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeviceIdInUserLoginHistoryTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_login_history')) {
            Schema::table('user_login_history', function (Blueprint $table) {
                $table->string('device_id', 255)->nullable()->after('ip_address');
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
        if (Schema::hasTable('user_login_history')) {
            Schema::table('user_login_history', function (Blueprint $table) {
                $table->dropColumn('device_id');
            });
        }
    }
}
