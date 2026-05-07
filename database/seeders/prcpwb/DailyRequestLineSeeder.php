<?php

namespace Database\Seeders\prcpwb;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DailyRequestLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::connection('prcpwb')->statement('TRUNCATE TABLE "PRCPWB_TRHDAILYREQUESTLINES" RESTART IDENTITY CASCADE');
        $oldDatas = DB::connection('mysql_legacy')->table('sim_proc_poweb_dailyrequestno')->get();

        foreach ($oldDatas as $old) {
            DB::connection('prcpwb')->table('PRCPWB_TRHDAILYREQUESTLINES')->insert([
                'VVENDORNO'         => $old->vendor_no,
                'DWANTEDRECEIPTDATE'=> $old->wanted_receipt_date,
                'VPONO'             => $old->po_no,
                'VTIME'             => $old->time,
                'VDELIVERYNOTENO'   => $old->delivery_note_no,
                'VCREA'             => "Seeder",
                'DCREA'             => now(),
            ]);
        }

        $maxId = DB::connection('prcpwb')->table('PRCPWB_TRHDAILYREQUESTLINES')->max('IID');
        if ($maxId) {
            DB::connection('prcpwb')->statement("SELECT setval('\"SQ_TRHDAILYREQUESTLINES_IID\"', $maxId)");
        }
    }
}
