<?php

namespace Database\Seeders\prcpwb;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ForecastLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::connection('prcpwb')->statement('TRUNCATE TABLE "PRCPWB_TRDFORECASTLINES" RESTART IDENTITY CASCADE');
        $oldDatas = DB::connection('mysql_legacy')->table('sim_proc_poweb_forecastdetail')->get();

        foreach ($oldDatas as $old) {
            DB::connection('prcpwb')->table('PRCPWB_TRDFORECASTLINES')->insert([
                'VPERIOD'           => $old->Period,
                'IREVNO'            => $old->Rev_No,
                'VVENDORNO'         => $old->Vendor_No,
                'VPARTNO'           => $old->Part_No,
                'DRECEIPTDATE'      => $old->Receipt_Date,
                'VDESCRIPTION'      => mb_convert_encoding($old->Description, 'UTF-8', 'ISO-8859-1'),
                'EDUEQTY'           => $old->Due_Qty,
                'VUNITMEAS'         => $old->Unit_Meas,
                'VDIMQUALITY'       => mb_convert_encoding($old->Dim_Quality, 'UTF-8', 'ISO-8859-1'),
                'EQTYONHAND'        => $old->qty_on_hand,
                'EQTYTOORDERMAKER'  => $old->qty_to_order_maker,
                'DETATOORDERMAKER'  => $old->eta_to_order_maker,
                'VDESTINATIONID'    => $old->Destination_id,
                'VCREA'             => $old->Created_By,
                'DCREA'             => $old->Created_Date,
                'VMODI'             => $old->Modified_By,
                'DMODI'             => $old->Modified_Date,
            ]);
        }

        $maxId = DB::connection('prcpwb')->table('PRCPWB_TRDFORECASTLINES')->max('IID');
        if ($maxId) {
            DB::connection('prcpwb')->statement("SELECT setval('\"SQ_TRDFORECASTLINES_IID\"', $maxId)");
        }
    }
}
