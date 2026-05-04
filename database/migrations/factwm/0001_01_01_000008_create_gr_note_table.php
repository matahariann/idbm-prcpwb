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
        DB::statement('CREATE SEQUENCE IF NOT EXISTS "SQ_TRHGR_NOTES_IID" START 1 INCREMENT 1');

        Schema::create('FACTWM_TRHGR_NOTES', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRHGR_NOTES_IID"\')'));
            $table->string('VREF_TYPE')->nullable(); // REF TYPE
            $table->string('VRECEIPT_SEQUENCE')->nullable(); // RECEIPT SEQUENCE
            $table->string('IRECEIPT_NO')->nullable(); // RECEIPT NO

            $table->string('DDELIVERY_DATE')->nullable(); // DELIVERY DATE
            $table->string('DAPPROVAL_DATE')->nullable(); // APPROVAL DATE
            $table->string('VNOTEID')->nullable(); // NOTE ID

            $table->string('VGR_NUMBER')->unique(); // GRN NO
            $table->string('VDELIVERY_NUMBER')->nullable(); // DELLIVERY NUMBER
            $table->string('VPO_NUMBER')->nullable(); // ORDER NO
            // di table ini menyesuaikan tidak ada field currency
            $table->string('VVENDOR_CODE')->nullable(); // SUPPLIER CODE
            $table->string('VVENDOR_NAME')->nullable(); // SUPPLIER NAME
            $table->string('VSTATUS')->nullable();
            $table->string('VSTATUS_SUBMITTED')->default('PENDING')->comment('Status data yang sudah di verify po');
            $table->string('VDISPUTEFILE')->nullable();
            $table->string('VDISPUTEDESC')->nullable();
            $table->string('VDISPUTEREJECTDESC')->nullable();

            $table->timestamp('DGR')->nullable(); // GRN DATE
            $table->timestamp('DSYNC')->nullable();
            $table->timestamp('DAPPROVE')->nullable();
            $table->timestamp('DDISPUTE')->nullable();

            $table->string('VSOURCEREF4')->nullable();
            $table->string('VCONTRACTNO')->nullable();

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
        Schema::dropIfExists('FACTWM_TRHGR_NOTES');

        DB::statement('DROP SEQUENCE IF EXISTS "SQ_TRHGR_NOTES_IID"');
    }
};
