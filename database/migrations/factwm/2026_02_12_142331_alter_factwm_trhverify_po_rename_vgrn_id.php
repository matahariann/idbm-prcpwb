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
            $table->renameColumn('VGRN_ID', 'VGRN_NUMBER');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('FACTWM_TRHVERIFY_PO', function (Blueprint $table) {
            $table->renameColumn('VGRN_NUMBER', 'VGRN_ID');
        });
    }
};
