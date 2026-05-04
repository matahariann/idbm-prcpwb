<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE SEQUENCE "SQ_LOGVERIFY_NON_PO_OTHER_FILES_IID" START 1 INCREMENT 1');

        Schema::create('FACTWM_LOGVERIFY_NON_PO_OTHER_FILES', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_LOGVERIFY_NON_PO_OTHER_FILES_IID"\')'));
            $table->integer('TRHVERIFY_NON_PO_IID')->nullable();
            $table->string('VNAME')->nullable();
            $table->string('VPATH')->nullable();
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
        Schema::dropIfExists('FACTWM_LOGVERIFY_NON_PO_OTHER_FILES');
    }
};
