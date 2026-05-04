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
        DB::statement('CREATE SEQUENCE IF NOT EXISTS "SQ_TRDGR_NOTE_DETAILS_IID" START 1 INCREMENT 1');

        Schema::create('FACTWM_TRDGR_NOTE_DETAILS', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRDGR_NOTE_DETAILS_IID"\')'));
            $table->string('VGR_NUMBER'); // GRN NO
            $table->string('VMATERIAL_CODE')->nullable(); // PART NO
            $table->string('VDESCRIPTION')->nullable(); // DESCRIPTION
            $table->integer('IQTY')->nullable(); // QTY
            $table->string('UOM')->nullable(); // UOM
            $table->string('VPRICE')->nullable(); // PRICE
            $table->string('VAMOUNT')->nullable();

            $table->string('VOBJ_STATE')->nullable(); // OBJ STATE
            $table->string('VCURRENCY')->nullable(); // move from parent table

            $table->timestamp('DGR')->nullable();
            $table->timestamp('DSYNC')->nullable();
            $table->timestamp('DAPPROVE')->nullable();
            $table->timestamp('DDISPUTE')->nullable();

            $table->string('VCREA', 100)->nullable();
            $table->timestamp('DCREA')->nullable();
            $table->string('VMODI', 100)->nullable();
            $table->timestamp('DMODI')->nullable();
            $table->string('VDELETE', 100)->nullable();
            $table->timestamp('DDELETE')->nullable();

            $table->unique(['VGR_NUMBER', 'VMATERIAL_CODE'], 'uk_gr_material');

            $table->index('VGR_NUMBER');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('FACTWM_TRDGR_NOTE_DETAILS');

        DB::statement('DROP SEQUENCE IF EXISTS "SQ_TRDGR_NOTE_DETAILS_IID"');
    }
};
