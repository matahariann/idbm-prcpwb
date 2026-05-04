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
        DB::statement('CREATE SEQUENCE IF NOT EXISTS "SQ_TRDVERIFY_NON_PO_DETAILS_IID" START 1 INCREMENT 1');

        Schema::create('FACTWM_TRDVERIFY_NON_PO_DETAILS', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRDVERIFY_NON_PO_DETAILS_IID"\')'));
            $table->text('VDESCRIPTION')->nullable();
            $table->integer('IQTY')->nullable();
            $table->string('VUOM')->nullable();
            $table->string('IPRICE')->nullable();
            $table->string('IDPP_NILAI_LAIN')->nullable();
            $table->string('IPPN')->nullable();
            $table->string('ITOTAL')->nullable();

            $table->foreignId('TRHVERIFY_NON_PO_IID')
                ->constrained('FACTWM_TRHVERIFY_NON_PO', 'IID')
                ->onDelete('cascade');

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
        Schema::dropIfExists('FACTWM_TRDVERIFY_NON_PO_DETAILS');

        DB::statement('DROP SEQUENCE IF EXISTS "SQ_TRDVERIFY_NON_PO_DETAILS_IID"');
    }
};
