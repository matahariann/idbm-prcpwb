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
        DB::statement('CREATE SEQUENCE IF NOT EXISTS "SQ_FILES_IID" START 1 INCREMENT 1');

        Schema::create('FACTWM_FILES', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_FILES_IID"\')'));
            // $table->string('VFILEABLE_TYPE', 100)->nullable();
            // $table->integer('VFILEABLE_ID')->nullable();
            $table->string('VNAME', 100)->nullable();
            $table->integer('ISIZE')->nullable();
            $table->string('VEXTENSION')->nullable();
            $table->integer('IFOLDER_ID')->nullable();
            $table->string('VCREA', 100)->nullable();
            $table->timestamp('DCREA')->nullable();
            $table->integer('IUSER_ID')->nullable();
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
        Schema::dropIfExists('FACTWM_FILES');

        DB::statement('DROP SEQUENCE IF EXISTS "SQ_FILES_IID"');
    }
};
