<?php

namespace Database\Seeders\prcpwb;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::connection('prcpwb')->statement('TRUNCATE TABLE "PRCPWB_TRHPO" RESTART IDENTITY CASCADE');
        $oldDatas = DB::connection('mysql_legacy')->table('sim_proc_poweb_poheader')->get();

        foreach ($oldDatas as $old) {
            DB::connection('prcpwb')->table('PRCPWB_TRHPO')->insert([
                'VORDERNO'     => $old->Order_No,
                'IREVISIONNO'  => $old->Revision_No,
                'VVENDORNO'    => $old->Vendor_No,
                'VSTATUS'      => $old->Status,
                'DRELEASEDATE' => $old->Release_Date,
                'DGETDATE'     => $old->Get_Date,
                'VCONFIRMTEXT' => $old->Confirmation_Text,
                'DCONFIRMDATE' => $old->Confirm_Date,
                'DDATEENTERED' => $old->Date_Entered,
                'VDELIVERYBY'  => $old->Delivery_By,
                'DWANTEDDELIVERYDATE' => $old->Wanted_Delivery_Date,
                'DWANTEDRECEIPTDATE'  => $old->Wanted_Receipt_Date,
                'EVAT'         => $old->Vat,
                'VREMARK'      => $old->Remarks,
                'VCURRENCYCODE'=> $old->Currency_Code,
                'VDELTERMS'    => $old->Del_Terms,
                'VDESTINATION' => $old->Destination,
                'VCREA'        => $old->Created_By,
                'DCREA'        => $old->Created_Date,
                'VMODI'        => $old->Modify_By,
                'DMODI'        => $old->Modify_Date,
            ]);
        }

        $maxId = DB::connection('prcpwb')->table('PRCPWB_TRHPO')->max('IID');
        if ($maxId) {
            DB::connection('prcpwb')->statement("SELECT setval('\"SQ_TRHPO_IID\"', $maxId)");
        }

    }
}
