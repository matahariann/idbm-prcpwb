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

    // sim_proc_poweb_forecast
    // Jensi Tabel: Transaksi (TRH)
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE SEQUENCE "SQ_TRHFORECASTS_IID" START 1 INCREMENT 1');

        Schema::connection($this->connection)->create('PRCPWB_TRHFORECASTS', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRHFORECASTS_IID"\')'));
            $table->string('VPERIOD', 10);
            $table->integer('IREVNO')->default(0);
            $table->string('VVENDORNO', 20);
            $table->string('VDESTINATIONID', 30);
            $table->string('VSTATUS', 20);
            $table->string('VNOTES', 2000)->nullable();
            $table->timestamp('DRELEASEDATE')->nullable();
            $table->string('VCONFIRMNOTES', 2000)->nullable();
            $table->timestamp('DCONFIRMDATE')->nullable();

            $table->string('VCREA', 100);
            $table->timestamp('DCREA');
            $table->string('VMODI', 100)->nullable();
            $table->timestamp('DMODI')->nullable();
            $table->string('VDELETE', 100)->nullable();
            $table->timestamp('DDELETE')->nullable();

            $table->unique(['VPERIOD', 'IREVNO', 'VVENDORNO', 'VDESTINATIONID']);
            $table->index(['VVENDORNO', 'VDESTINATIONID']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('PRCPWB_TRHFORECASTS');
        DB::connection($this->connection)->statement('DROP SEQUENCE IF EXISTS "SQ_TRHFORECASTS_IID"');
    }
};
