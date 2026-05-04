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

    // sim_proc_claim_confirmation
    // Jensi Tabel: Transaksi (TRH)
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE SEQUENCE "SQ_TRHCLAIMCONFIRMATIONS_IID" START 1 INCREMENT 1');

        Schema::connection($this->connection)->create('PRCPWB_TRHCLAIMCONFIRMATIONS', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRHCLAIMCONFIRMATIONS_IID"\')'));
            $table->integer('ICONFIRMATIONNO');
            $table->integer('ICONFIRMATIONREVNO');
            $table->integer('ICLAIMNO');
            $table->integer('IREVISIONNO');
            $table->string('VVENDORNO', 20);
            $table->date('DCONFIRMATIONDATE');
            $table->string('VSTATUS', 15);
            $table->string('VCONFIRMATIONTEXT', 1000);
            $table->timestamp('DRELEASEDATE')->nullable();
            $table->timestamp('DVENDORCONFIRMATIONDATE')->nullable();
            $table->integer('IRETURNREWORK');
            $table->string('VATTENTION', 35);

            $table->string('VCREA', 100);
            $table->timestamp('DCREA');
            $table->string('VMODI', 100)->nullable();
            $table->timestamp('DMODI')->nullable();
            $table->string('VDELETE', 100)->nullable();
            $table->timestamp('DDELETE')->nullable();

            $table->unique(['ICONFIRMATIONNO', 'ICONFIRMATIONREVNO']);
            $table->index(['ICONFIRMATIONREVNO']);
            $table->index(['VVENDORNO']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('PRCPWB_TRHCLAIMCONFIRMATIONS');
        DB::connection($this->connection)->statement('DROP SEQUENCE IF EXISTS "SQ_TRHCLAIMCONFIRMATIONS_IID"');
    }
};
