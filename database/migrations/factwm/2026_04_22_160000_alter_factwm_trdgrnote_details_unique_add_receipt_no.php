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
            $table->dropUnique('uk_gr_material');
            $table->unique(['VGR_NUMBER', 'VMATERIAL_CODE', 'IRECEIPT_NO'], 'uk_gr_material_receipt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('FACTWM_TRDGR_NOTE_DETAILS', function (Blueprint $table) {
            $table->dropUnique('uk_gr_material_receipt');
            $table->unique(['VGR_NUMBER', 'VMATERIAL_CODE'], 'uk_gr_material');
        });
    }
};
