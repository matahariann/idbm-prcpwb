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

    // sim_proc_poweb_poheader
    // Jensi Tabel: Transaksi (TRH)
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement('CREATE SEQUENCE IF NOT EXISTS "SQ_TRHPO_IID" START 1 INCREMENT 1');
        DB::connection($this->connection)->statement('ALTER SEQUENCE "SQ_TRHPO_IID" RESTART WITH 1');

        Schema::connection($this->connection)->create('PRCPWB_TRHPO', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_TRHPO_IID"\')'));
            $table->string('VORDERNO', 12)->unique();
            $table->integer('IREVISIONNO')->nullable();
            $table->string('VVENDORNO', 20)->nullable();
            $table->string('VSTATUS', 10);
            $table->timestamp('DRELEASEDATE');
            $table->timestamp('DGETDATE')->nullable();     
            $table->string('VCONFIRMTEXT', 255)->nullable();
            $table->timestamp('DCONFIRMDATE')->nullable();
            $table->timestamp('DDATEENTERED')->nullable();
            $table->string('VDELIVERYBY', 50)->nullable();
            $table->timestamp('DWANTEDDELIVERYDATE')->nullable();
            $table->timestamp('DWANTEDRECEIPTDATE')->nullable();
            $table->double('EVAT')->nullable();
            $table->string('VREMARK', 2000)->nullable();
            $table->string('VCURRENCYCODE', 3)->nullable();
            $table->string('VDELTERMS', 10)->nullable();
            $table->string('VDESTINATION', 50)->nullable();

            $table->string('VCREA', 100);
            $table->timestamp('DCREA');
            $table->string('VMODI', 100)->nullable();
            $table->timestamp('DMODI')->nullable();
            $table->string('VDELETE', 100)->nullable();
            $table->timestamp('DDELETE')->nullable();

            $table->index('VVENDORNO');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('PRCPWB_TRHPO');
        DB::connection($this->connection)->statement('DROP SEQUENCE IF EXISTS "SQ_TRHPO_IID"');
    }
};
