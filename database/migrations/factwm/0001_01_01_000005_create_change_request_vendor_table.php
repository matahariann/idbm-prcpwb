<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE SEQUENCE IF NOT EXISTS "SQ_MSHCHANGE_REQUEST_IID" START 1 INCREMENT 1');

        Schema::create('FACTWM_MSHCHANGE_REQUEST_VENDOR', function (Blueprint $table) {
            $table->bigInteger('IID')->primary()->default(DB::raw('nextval(\'"SQ_MSHCHANGE_REQUEST_IID"\')'));
            $table->bigInteger('ICOMM_ID')->nullable();
            $table->string('VSUPPLIER_CODE', 100);
            $table->string('VSUPPLIER_NAME', 100);
            $table->string('VUSERNAME', 100)->nullable();
            $table->string('VNAME', 100)->nullable();
            $table->string('VMETHOD_ID', 100)->nullable();
            $table->string('VDESCRIPTION', 100)->nullable();
            $table->string('VADDRESS_ID', 100)->nullable();
            $table->string('VPARTY_TYPE_DB_VAL', 100)->nullable();
            $table->string('BMETHOD_DEFAULT', 100)->default(false);
            $table->string('VVALUE', 100)->nullable();
            $table->string('VSTATUS', 50);
            $table->string('VTYPE', 50);
            $table->boolean('BDOWNLOAD')->default(false);
            $table->timestamp('DDOWNLOAD')->nullable();

            $table->bigInteger('IUSER_ID')->nullable();
            $table->foreignId('ISUPPLIER_ID')
                ->constrained('FACTWM_MSHSUPPLIERS', 'IID')
                ->onDelete('cascade');

            $table->string('VCREA', 100)->nullable();
            $table->timestamp('DCREA')->nullable();
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
        Schema::dropIfExists('FACTWM_MSHCHANGE_REQUEST_VENDOR');

        DB::statement('DROP SEQUENCE IF EXISTS "SQ_MSHCHANGE_REQUEST_IID"');
    }
};
