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

    // CTM_MONITOR_DIES_TAB
    // Jensi Tabel: Master (MSH)
    
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE SEQUENCE "SQ_MSHDIESS_IID" START 1 INCREMENT 1');

        Schema::connection($this->connection)->create('PRCPWB_MSHDIES', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_MSHDIESS_IID"\')'));
            $table->integer('IDIESNO')->nullable();
            $table->string('VPARTNO', 200)->nullable();
            $table->string('VPARTDESCRIPTION', 200)->nullable();
            $table->string('VSUPPLIERNO', 200)->nullable();
            $table->string('VSUPPLIERNAME', 200)->nullable();
            $table->integer('IPERCENTAGE')->nullable(); 
            $table->string('VREMARK', 200)->nullable();
            $table->integer('IQTYDEPRECIATION')->nullable();
            $table->date('DVALIDFROM');
            $table->date('DVALIDTO')->nullable();
            $table->decimal('ETOTALRECEIVEDQTY', 65, 4)->nullable();
            $table->decimal('EQTYREMAINED', 65, 4)->nullable();
            $table->integer('IRECEIVEDONIFS7')->nullable();

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
        Schema::connection($this->connection)->dropIfExists('PRCPWB_MSHDIES');
        DB::connection($this->connection)->statement('DROP SEQUENCE IF EXISTS "SQ_MSHDIESS_IID"');
    }
};
