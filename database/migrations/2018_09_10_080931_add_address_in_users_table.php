<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAddressInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('latitude',10,8)->after('language')->nullable();
            $table->decimal('longitude',11,8)->after('latitude')->nullable();
            $table->string('address')->nullable()->after('longitude');
            $table->string('street_address')->nullable()->after('address');
            $table->string('locality')->nullable()->after('street_address');
            $table->string('country')->nullable()->after('locality');
            $table->string('state')->nullable()->after('country');
            $table->string('city')->nullable()->after('state');
            $table->string('zip_code',8)->nullable()->after('city');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'latitude', 'longitude', 'address', 'street_address', 'locality', 'country', 'state', 'city', 'zip_code'
            ]);
        });
    }
}
