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
            $table->string('VTAX_INVOICE_NUMBER')->nullable()->change();
            $table->date('DTAX_INVOICE_DATE')->nullable()->change();
            $table->string('VINVOICE_FILE')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('FACTWM_TRHVERIFY_PO', function (Blueprint $table) {
            $table->string('VTAX_INVOICE_NUMBER')->nullable(false)->change();
            $table->date('DTAX_INVOICE_DATE')->nullable(false)->change();
            $table->string('VINVOICE_FILE')->nullable(false)->change();
        });
    }
};
