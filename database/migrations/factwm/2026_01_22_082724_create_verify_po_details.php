<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE SEQUENCE IF NOT EXISTS "SQ_TRDVERIFY_PO_DETAILS_IID" START 1 INCREMENT 1');

        Schema::create('FACTWM_TRDVERIFY_PO_DETAILS', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRDVERIFY_PO_DETAILS_IID"\')'));
            $table->integer('TRHVERIFY_PO_IID')->nullable();
            $table->integer('TRDGR_NOTE_DETAILS_IID')->nullable();
            $table->timestamp('FACTWM_TRHGR_NOTES_DGR', 100)->nullable();
            $table->string('VCREA', 100)->nullable();
            $table->timestamp('DCREA')->nullable();
            $table->string('VMODI', 100)->nullable();
            $table->timestamp('DMODI')->nullable();
            $table->string('VDELETE', 100)->nullable();
            $table->timestamp('DDELETE')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('FACTWM_TRDVERIFY_PO_DETAILS');
        DB::statement('DROP SEQUENCE IF EXISTS "SQ_TRDVERIFY_PO_DETAILS_IID"');
    }
};
