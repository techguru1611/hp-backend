<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveKycIdColumnKycDocumentCommentsTable extends Migration
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
                $table->dropForeign('kyc_document_comments_kyc_id_foreign');
                $table->dropColumn([
                    'kyc_id'
                ]);
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
    }
}
