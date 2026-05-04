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
        DB::statement('CREATE SEQUENCE IF NOT EXISTS "SQ_MSHINFORMATION_IID" START 1 INCREMENT 1');

        Schema::create('FACTWM_MSHINFORMATION', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_MSHINFORMATION_IID"\')'));
            $table->text('VNOTES')->nullable();
            $table->timestamp('DFROM');
            $table->timestamp('DTO');
            $table->string('VUSER_TYPE', 100);
            $table->string('VCATEGORY')->nullable();
            $table->string('VFILE_INFORMATION')->nullable();
            $table->string('VUPDLOAD_DATA_VENDOR')->nullable();
            $table->string('VUPDLOAD_FOTO_ASSET')->nullable();
            $table->integer('ITOTALVIEW')->nullable();
            $table->string('VVIEWERS')->nullable();
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
        Schema::dropIfExists('FACTWM_MSHINFORMATION');

        DB::statement('DROP SEQUENCE IF EXISTS "SQ_MSHINFORMATION_IID"');
    }
};
