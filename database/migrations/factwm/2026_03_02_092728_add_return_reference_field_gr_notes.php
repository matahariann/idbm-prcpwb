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
        Schema::table('FACTWM_TRHGR_NOTES', function (Blueprint $table) {
            $table->dropUnique(['VGR_NUMBER']);
            $table->string('VRETURN_REF')->nullable()->after('VCONTRACTNO');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('FACTWM_TRHGR_NOTES', function (Blueprint $table) {
            $table->unique('VGR_NUMBER');
            $table->dropColumn('VRETURN_REF');
        });
    }
};
