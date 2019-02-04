<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserIdColumnKycDocumentCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        if (Schema::hasTable('kyc_document_comments')) {
            Schema::table('kyc_document_comments', function (Blueprint $table) {
                $table->integer('user_id')->unsigned()->nullable()->after('notes_by');
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
        if (Schema::hasTable('kyc_document_comments')) {
            Schema::table('kyc_document_comments', function (Blueprint $table) {
                $table->dropForeign('kyc_document_comments_user_id_foreign');
                $table->dropColumn([
                    'user_id'
                ]);
            });
        }
    }
}
