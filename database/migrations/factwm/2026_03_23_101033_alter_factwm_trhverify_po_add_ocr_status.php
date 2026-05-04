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
        Schema::table('FACTWM_TRHVERIFY_PO', function (Blueprint $table) {
            $table->string('VREQUIRE_MATERAI_OCR')->nullable();
            $table->string('VOCR_MATERAI_STATUS')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('FACTWM_TRHVERIFY_PO', function (Blueprint $table) {
            $table->dropColumn(['VREQUIRE_MATERAI_OCR', 'VOCR_MATERAI_STATUS']);
        });
    }
};
