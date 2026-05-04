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

    // CTM_MONITOR_DIES_LINE_TAB
    // Jensi Tabel: Master (MSD)
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE SEQUENCE "SQ_MSDDIESLINES_IID" START 1 INCREMENT 1');

        Schema::connection($this->connection)->create('PRCPWB_MSDDIESLINES', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_MSDDIESLINES_IID"\')'));
            $table->integer('ISEQNO')->nullable();
            $table->integer('IDIESNO')->nullable();
            $table->string('VPARTNO', 200)->nullable();
            $table->string('VPARTDESCRIPTION', 200)->nullable();
            $table->string('VSUPPLIERNO', 200)->nullable();
            $table->string('VSUPPLIERNAME', 200)->nullable();
            $table->string('VPONO', 200)->nullable();
            $table->integer('ILINENO')->nullable();
            $table->integer('IRELEASENO')->nullable();
            $table->integer('IRECEIPTNO')->nullable();
            $table->decimal('ERECEIVEDQTY', 65, 4)->nullable();
            $table->string('VDELIVERYNOTENO', 200)->nullable();
            $table->string('VRECEIPTREFERENCE', 200)->nullable();
            $table->timestamp('DACTUALDELIVERYDATE');

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
        Schema::connection($this->connection)->dropIfExists('PRCPWB_MSDDIESLINES');
        DB::connection($this->connection)->statement('DROP SEQUENCE IF EXISTS "SQ_MSDDIESLINES_IID"');
    }
};
