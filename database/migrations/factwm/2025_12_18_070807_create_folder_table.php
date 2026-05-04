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
        DB::statement('CREATE SEQUENCE IF NOT EXISTS "SQ_FOLDERS_IID" START 1 INCREMENT 1');

        Schema::create('FACTWM_FOLDERS', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_FOLDERS_IID"\')'));
            $table->string('VNAME', 100)->nullable();
            $table->string('VSUPPLIER_CODE', 100)->nullable();
            $table->string('VSUPPLIER_NAME', 100)->nullable();
            $table->integer('IPARENT_ID')->nullable();
            $table->integer('ISIZE')->nullable();
            $table->integer('ITOTAL_FILES')->nullable();
            $table->string('VFOLDER_TYPE', 100)->nullable();
            $table->string('VCREA', 100)->nullable();
            $table->integer('IUSER_ID')->nullable();
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
        Schema::dropIfExists('FACTWM_FOLDERS');

        DB::statement('DROP SEQUENCE IF EXISTS "SQ_FOLDERS_IID"');
    }
};
