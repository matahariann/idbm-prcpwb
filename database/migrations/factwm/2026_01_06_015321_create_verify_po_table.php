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
        DB::statement('CREATE SEQUENCE IF NOT EXISTS "SQ_TRHVERIFY_PO_IID" START 1 INCREMENT 1');

        Schema::create('FACTWM_TRHVERIFY_PO', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRHVERIFY_PO_IID"\')'));
            $table->string('VBILLING_STATEMENT')->nullable();
            $table->string('VUNIQUE_CODE')->nullable();
            $table->string('VGRN_ID')->nullable();
            $table->string('VSUPPLIER_CODE');
            $table->string('VINVOICE_NUMBER');
            $table->date('DINVOICE_DATE')->nullable();
            $table->string('VTAX_INVOICE_NUMBER');
            $table->date('DTAX_INVOICE_DATE')->nullable();
            $table->bigInteger('ITOTAL');
            $table->bigInteger('IPPN');
            $table->bigInteger('IDPP')->nullable();
            $table->bigInteger('INET_AMOUNT');
            $table->string('VNPWP_SUPPLIER')->nullable();
            $table->string('VINVOICE_FILE');
            $table->string('VTAX_INVOICE_FILE')->nullable();
            $table->text('VNOTES')->nullable();
            $table->string('VREKAP_JASA_FILE')->nullable();
            $table->string('VPPH')->nullable();
            $table->string('VOBJECT')->nullable();
            $table->bigInteger('IDPP_PPH')->nullable();
            $table->float('FTARRIF')->nullable();
            $table->float('FVALUE')->nullable();
            $table->json('VGR_NUMBER_IID');
            $table->date('DSUBMITTED')->nullable();
            $table->string('VSTATUS')->default('draft');

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
        Schema::dropIfExists('FACTWM_TRHVERIFY_PO');
        DB::statement('DROP SEQUENCE IF EXISTS "SQ_TRHVERIFY_PO_IID"');
    }
};
