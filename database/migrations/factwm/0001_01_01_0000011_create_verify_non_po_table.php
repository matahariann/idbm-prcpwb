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
        DB::statement('CREATE SEQUENCE IF NOT EXISTS "SQ_TRHVERIFY_NON_PO_IID" START 1 INCREMENT 1');

        Schema::create('FACTWM_TRHVERIFY_NON_PO', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRHVERIFY_NON_PO_IID"\')'));
            $table->string('VBILLING_STATEMENT')->nullable();
            $table->string('VUNIQUE_CODE')->nullable();
            $table->string('VSUPPLIER_CODE');
            $table->string('VINV_NO_SUPPLIER')->nullable();
            $table->timestamp('DINV_DATE')->nullable();
            $table->bigInteger('IDPP_PPH')->nullable();
            $table->bigInteger('INET_AMOUNT')->nullable();
            $table->string('VOBJECT')->nullable();
            $table->string('FTARRIF')->nullable();
            $table->string('FVALUE')->nullable();
            $table->string('VTAX_CODE')->nullable();
            $table->string('VPPH')->nullable();
            $table->string('VDPP')->nullable();
            $table->string('VPPN')->nullable();
            $table->string('VTAX_NUMBER')->nullable();
            $table->timestamp('DTAX_DATE')->nullable();
            $table->bigInteger('ITOTAL')->nullable();
            $table->string('VSTATUS')->nullable();
            $table->string('VPDF_TAX')->nullable();
            $table->string('VPDF_INVOICE')->nullable();
            $table->string('VPDF_REKAP_JASA')->nullable();
            $table->string('VQRCODE')->nullable();
            $table->timestamp('DSUBMITTED')->nullable();
            $table->text('VNOTES')->nullable();
            // $table->timestamp('DAPPROVED')->nullable();
            $table->timestamp('DPLAN_PAY_DATE')->nullable();

            $table->string('VCREA', 100)->nullable();
            $table->timestamp('DCREA')->nullable();
            $table->string('VMODI', 100)->nullable();
            $table->timestamp('DMODI')->nullable();
            $table->string('VDELETE', 100)->nullable();
            $table->timestamp('DDELETE')->nullable();

            $table->timestamp('DAPPROVED')->nullable()->comment('tanggal yang diambil ketika idbm hit api di report invoice');
            $table->string('VSTATUS_INVOICE')->default('WAITING')->comment('status invoice');
            $table->string('VPYHSICAL_DOC_STATUS')->nullable()->comment('isinya kosong atau submitted di report invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('FACTWM_TRHVERIFY_NON_PO');

        DB::statement('DROP SEQUENCE IF EXISTS "SQ_TRHVERIFY_NON_PO_IID"');
    }
};
