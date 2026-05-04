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

    // sim_proc_poweb_forecastdetail
    // Jensi Tabel: Transaksi (TRD)
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE SEQUENCE "SQ_TRDFORECASTLINES_IID" START 1 INCREMENT 1');

        Schema::connection($this->connection)->create('PRCPWB_TRDFORECASTLINES', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRDFORECASTLINES_IID"\')'));
            $table->string('VPERIOD', 10);
            $table->integer('IREVNO')->default(0);
            $table->string('VVENDORNO', 20);
            $table->string('VPARTNO', 25);
            $table->timestamp('DRECEIPTDATE');
            $table->string('VDESCRIPTION', 200);
            $table->float('EDUEQTY');
            $table->string('VUNITMEAS', 10);
            $table->string('VDIMQUALITY', 25)->nullable();
            $table->float('EQTYONHAND')->nullable();
            $table->float('EQTYTOORDERMAKER')->nullable();
            $table->timestamp('DETATOORDERMAKER')->nullable();
            $table->string('VDESTINATIONID', 10)->default('');

            $table->string('VCREA', 100);
            $table->timestamp('DCREA');
            $table->string('VMODI', 100)->nullable();
            $table->timestamp('DMODI')->nullable();
            $table->string('VDELETE', 100)->nullable();
            $table->timestamp('DDELETE')->nullable();

            $table->unique(['VPERIOD', 'IREVNO', 'VVENDORNO', 'VPARTNO', 'DRECEIPTDATE', 'VDESTINATIONID']);
            $table->index(['VPERIOD', 'IREVNO', 'VVENDORNO', 'VDESTINATIONID']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('PRCPWB_TRDFORECASTLINES');
        DB::connection($this->connection)->statement('DROP SEQUENCE IF EXISTS "SQ_TRDFORECASTLINES_IID"');
    }
};
