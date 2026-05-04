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

    // sim_proc_poweb_podetail
    // Jensi Tabel: Transkasi (TRD)
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE SEQUENCE "SQ_TRDPOLINES_IID" START 1 INCREMENT 1');

        Schema::connection($this->connection)->create('PRCPWB_TRDPOLINES', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRDPOLINES_IID"\')'));
            $table->string('VPONO', 12);
            $table->integer('IREVISIONNO')->nullable();
            $table->string('VLINENO', 4);
            $table->string('VRELEASENO', 4);
            $table->string('VPARTNO', 25)->nullable();
            $table->string('VDESCRIPTION', 2000);
            $table->double('EBUYQTYDUE');
            $table->string('VBUYUNITMEAS', 10);
            $table->double('EBUYUNITPRICE');
            $table->double('EAMOUNT');
            $table->string('VREQUISITIONNO', 12);         

            $table->string('VCREA', 100);
            $table->timestamp('DCREA');
            $table->string('VMODI', 100)->nullable();
            $table->timestamp('DMODI')->nullable();
            $table->string('VDELETE', 100)->nullable();
            $table->timestamp('DDELETE')->nullable();

            $table->unique(['VPONO', 'VLINENO', 'VRELEASENO']);
            $table->index(['VPONO']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('PRCPWB_TRDPOLINES');
        DB::connection($this->connection)->statement('DROP SEQUENCE IF EXISTS "SQ_TRDPOLINES_IID"');
    }
};
