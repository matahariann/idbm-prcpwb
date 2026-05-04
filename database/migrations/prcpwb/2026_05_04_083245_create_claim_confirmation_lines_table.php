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

    // sim_proc_claim_confirm_det
    // Jensi Tabel: Transaksi (TRD)
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE SEQUENCE "SQ_TRDCLAIMCONFIRMATIONLINES_IID" START 1 INCREMENT 1');

        Schema::connection($this->connection)->create('PRCPWB_TRDCLAIMCONFIRMATIONLINES', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRDCLAIMCONFIRMATIONLINES_IID"\')'));
            $table->integer('ICONFIRMATIONNO');
            $table->integer('ICONFIRMATIONREVNO');
            $table->string('VPARTNO', 25);
            $table->string('VDESCRIPTION', 35);
            $table->string('VTRNO', 20);
            $table->string('VREJECTREASONCODE', 8);
            $table->string('VREJECTREASONDESCRIPTION', 35);
            $table->float('EQTY');
            $table->float('EAMOUNT');
            $table->binary('VORDERNO');

            $table->string('VCREA', 100);
            $table->timestamp('DCREA');
            $table->string('VMODI', 100)->nullable();
            $table->timestamp('DMODI')->nullable();
            $table->string('VDELETE', 100)->nullable();
            $table->timestamp('DDELETE')->nullable();

            $table->unique(['ICONFIRMATIONNO', 'ICONFIRMATIONREVNO', 'VPARTNO']);
            $table->index(['ICONFIRMATIONREVNO']);

            $table->foreign(
                ['ICONFIRMATIONNO', 'ICONFIRMATIONREVNO'],
                'fk_trd_claimconf_to_trh'
            )
            ->references(['ICONFIRMATIONNO', 'ICONFIRMATIONREVNO'])
            ->on('PRCPWB_TRHCLAIMCONFIRMATIONS')
            ->onUpdate('cascade')
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('PRCPWB_TRDCLAIMCONFIRMATIONLINES');
        DB::connection($this->connection)->statement('DROP SEQUENCE IF EXISTS "SQ_TRDCLAIMCONFIRMATIONLINES_IID"');
    }
};
