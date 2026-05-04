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

    // sim_proc_poweb_part_stock_vendor
    // Jensi Tabel: Master (MSH)
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE SEQUENCE "SQ_MSHSTOCKVENDORS_IID" START 1 INCREMENT 1');
     
        Schema::connection($this->connection)->create('PRCPWB_MSHSTOCKVENDORS', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_MSHSTOCKVENDORS_IID"\')'));
            $table->string('VVENDORNO', 20);
            $table->string('VPARTNO', 60)->default('');
            $table->double('EQTYONHAND')->nullable();
            $table->timestamp('DUPLOADDATE')->nullable();
            $table->string('VREMARK', 300)->nullable();

            $table->string('VCREA', 100);
            $table->timestamp('DCREA');
            $table->string('VMODI', 100)->nullable();
            $table->timestamp('DMODI')->nullable();
            $table->string('VDELETE', 100)->nullable();
            $table->timestamp('DDELETE')->nullable();

            $table->unique(['VVENDORNO', 'VPARTNO']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('PRCPWB_MSHSTOCKVENDORS');
        DB::connection($this->connection)->statement('DROP SEQUENCE IF EXISTS "SQ_MSHSTOCKVENDORS_IID"');
    }
};
