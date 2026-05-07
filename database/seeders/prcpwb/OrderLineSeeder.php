<?php

namespace Database\Seeders\prcpwb;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::connection('prcpwb')->statement('TRUNCATE TABLE "PRCPWB_TRDPOLINES" RESTART IDENTITY CASCADE');
        $oldDatas = DB::connection('mysql_legacy')->table('sim_proc_poweb_podetail')->get();

        foreach ($oldDatas as $old) {
            DB::connection('prcpwb')->table('PRCPWB_TRDPOLINES')->insert([
                'VPONO'          => $old->Order_No,
                'IREVISIONNO'    => $old->Revision_No,
                'VLINENO'        => $old->Line_No,
                'VRELEASENO'     => $old->Release_No,
                'VPARTNO'        => $old->Part_No,
                'VDESCRIPTION'   => $old->Description,
                'EBUYQTYDUE'     => $old->Buy_Qty_Due,
                'VBUYUNITMEAS'   => $old->Buy_Unit_Meas,
                'EBUYUNITPRICE'  => $old->FBuy_Unit_Price,
                'EAMOUNT'        => $old->Amount,
                'VREQUISITIONNO' => $old->Requisition_No,
                'VCREA'          => $old->Created_By,
                'DCREA'          => $old->Created_Date,
                'VMODI'          => $old->Modified_By,
                'DMODI'          => $old->Modified_Date,
            ]);
        }

        $maxId = DB::connection('prcpwb')->table('PRCPWB_TRDPOLINES')->max('IID');
        if ($maxId) {
            DB::connection('prcpwb')->statement("SELECT setval('\"SQ_TRDPOLINES_IID\"', $maxId)");
        }
    }
}
