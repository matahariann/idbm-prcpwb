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

    // sim_proc_poweb_deleted_dr
    // Jensi Tabel: Transaksi (TRH)
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE SEQUENCE "SQ_TRHDELETEDDAILYREQUESTS_IID" START 1 INCREMENT 1');

        Schema::connection($this->connection)->create('PRCPWB_TRHDELETEDDAILYREQUESTS', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRHDELETEDDAILYREQUESTS_IID"\')'));
            $table->string('VVENDORNO', 200);
            $table->string('VPARTNO', 200);
            $table->string('VPARTDESCRIPTION', 200)->nullable();
            $table->date('DWANTEDRECEIPTDATE');
            $table->date('DPROPOSEDWANTEDRECEIPTDATE');
            $table->string('VTIME', 200);
            $table->integer('IQUANTITY')->nullable();
            $table->integer('IQUANTITYCONFIRMATION')->nullable();
            $table->integer('IQUANTITYACTUAL')->nullable();
            $table->string('VSTATUS', 200)->nullable();
            $table->string('VDELIVERYNOTENO', 200)->nullable();
            $table->string('VPONO', 200)->nullable();
            $table->string('VDAILYREQNO', 200)->nullable();
            $table->string('VPRODUCTFAMILY', 200);
            $table->integer('IREVNO');
            $table->string('VFORECAST', 200);
            $table->integer('IMSPERIOD');
            $table->integer('IMSYEAR');
            $table->string('VUNITMEAS', 200)->nullable();
            $table->string('VDEDICATEDLOCATION', 200)->nullable();
            $table->string('VPROCCONTACT', 200)->nullable();

            $table->string('VCREA', 100);
            $table->timestamp('DCREA');
            $table->string('VMODI', 200)->nullable();
            $table->timestamp('DMODI')->nullable();
            $table->string('VDELETE', 200)->nullable();
            $table->timestamp('DDELETE')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('PRCPWB_TRHDELETEDDAILYREQUESTS');
        DB::connection($this->connection)->statement('DROP SEQUENCE IF EXISTS "SQ_TRHDELETEDDAILYREQUESTS_IID"');
    }
};
