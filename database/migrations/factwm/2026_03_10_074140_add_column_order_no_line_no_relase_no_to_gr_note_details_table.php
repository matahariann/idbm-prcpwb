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
            $table->string('VORDER_NO')->nullable()->after('IID_GR_NOTE');
            $table->string('VLINE_NO')->nullable()->after('VORDER_NO');
            $table->string('VRELEASE_NO')->nullable()->after('VLINE_NO');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('FACTWM_TRDGR_NOTE_DETAILS', function (Blueprint $table) {
            //
        });
    }
};
