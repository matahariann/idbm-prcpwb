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

    // sim_proc_poweb_dailyrequestno
    // Jensi Tabel: Transaksi (TRD)
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE SEQUENCE "SQ_TRHDAILYREQUESTLINES_IID" START 1 INCREMENT 1');

        Schema::connection($this->connection)->create('PRCPWB_TRHDAILYREQUESTLINES', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRHDAILYREQUESTLINES_IID"\')'));
            $table->string('VVENDORNO', 200)->nullable();
            $table->date('DWANTEDRECEIPTDATE')->nullable();
            $table->string('VPONO', 200)->nullable();
            $table->string('VTIME', 200)->nullable();
            $table->string('VDELIVERYNOTENO', 200)->nullable();

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
        Schema::connection($this->connection)->dropIfExists('PRCPWB_TRHDAILYREQUESTLINES');
        DB::connection($this->connection)->statement('DROP SEQUENCE IF EXISTS "SQ_TRHDAILYREQUESTLINES_IID"');
    }
};
