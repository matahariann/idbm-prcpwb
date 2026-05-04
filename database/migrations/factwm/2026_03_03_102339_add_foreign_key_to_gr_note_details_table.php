<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('FACTWM_TRDGR_NOTE_DETAILS', function (Blueprint $table) {
            $table->unsignedBigInteger('IID_GR_NOTE')->nullable()->after('IID');
            $table->foreign('IID_GR_NOTE')->references('IID')->on('FACTWM_TRHGR_NOTES');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('FACTWM_TRDGR_NOTE_DETAILS', function (Blueprint $table) {
            $table->dropForeign(['IID_GR_NOTE']);
            $table->dropColumn('IID_GR_NOTE');
        });
    }
};
