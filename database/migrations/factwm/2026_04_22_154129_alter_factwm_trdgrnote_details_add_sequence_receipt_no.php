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
            $table->string('IRECEIPT_NO')->nullable();
            $table->string('VRECEIPT_SEQUENCE')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('FACTWM_TRDGR_NOTE_DETAILS', function (Blueprint $table) {
            $table->dropColumn([
                'IRECEIPT_NO',
                'VRECEIPT_SEQUENCE',
            ]);
        });
    }
};
