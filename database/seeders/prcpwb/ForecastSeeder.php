<?php

namespace Database\Seeders\prcpwb;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ForecastSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::connection('prcpwb')->statement('TRUNCATE TABLE "PRCPWB_TRHFORECASTS" RESTART IDENTITY CASCADE');
        $oldDatas = DB::connection('mysql_legacy')->table('sim_proc_poweb_forecast')->get();

        foreach ($oldDatas as $old) {
            DB::connection('prcpwb')->table('PRCPWB_TRHFORECASTS')->insert([
                'VPERIOD'       => $old->Period,
                'IREVNO'        => $old->Rev_No,
                'VVENDORNO'     => $old->Vendor_No,
                'VDESTINATIONID'=> $old->Destination_Id,
                'VSTATUS'       => $old->Status,
                'VNOTES'        => $old->Notes,
                'DRELEASEDATE'  => $old->Released_Date,
                'VCONFIRMNOTES' => $old->Confirm_Notes,
                'DCONFIRMDATE'  => $old->Confirm_Date,
                'VCREA'         => $old->Created_By,
                'DCREA'         => $old->Created_Date,
                'VMODI'         => $old->Modified_By,
                'DMODI'         => $old->Modified_Date,
            ]);
        }

        $maxId = DB::connection('prcpwb')->table('PRCPWB_TRHFORECASTS')->max('IID');
        if ($maxId) {
            DB::connection('prcpwb')->statement("SELECT setval('\"SQ_TRHFORECASTS_IID\"', $maxId)");
        }
    }
}
