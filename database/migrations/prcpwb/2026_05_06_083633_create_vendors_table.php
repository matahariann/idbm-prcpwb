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
    
    // sim_proc_poweb_vendor
    // Jenis Tabel: Master (MSH)
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE SEQUENCE IF NOT EXISTS "SQ_MSHVENDORS_IID" START 1 INCREMENT 1');
        DB::connection($this->connection)->statement('ALTER SEQUENCE "SQ_MSHVENDORS_IID" RESTART WITH 1');

        Schema::connection($this->connection)->create('PRCPWB_MSHVENDORS', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_MSHVENDORS_IID"\')'));
            $table->string('VVENDORNO', 20)->unique();
            $table->string('VVENDORNAME', 100);
            $table->string('VCONTACT', 100)->nullable();
            $table->string('VADDRESS', 2000)->nullable();
            $table->string('VIMPORT')->nullable();

            $table->string('VCREA', 100);
            $table->timestamp('DCREA');
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
        Schema::connection($this->connection)->dropIfExists('PRCPWB_MSHVENDORS');
        DB::connection($this->connection)->statement('DROP SEQUENCE IF EXISTS "SQ_MSHVENDORS_IID"');
    }
};
