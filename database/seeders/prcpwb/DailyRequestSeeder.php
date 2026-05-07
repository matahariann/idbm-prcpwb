<?php

namespace Database\Seeders\prcpwb;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DailyRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::connection('prcpwb')->statement('TRUNCATE TABLE "PRCPWB_TRHDAILYREQUESTS" RESTART IDENTITY CASCADE');
        $oldDatas = DB::connection('mysql_legacy')->table('sim_proc_poweb_dailyrequest')->get();

        foreach ($oldDatas as $old) {
            DB::connection('prcpwb')->table('PRCPWB_TRHDAILYREQUESTS')->insert([
                'VVENDORNO'                 => $old->vendor_no,
                'VPARTNO'                   => $old->part_no,
                'VPARTDESCRIPTION'          => $old->part_desc,
                'DWANTEDRECEIPTDATE'        => $old->wanted_receipt_date,
                'DPROPOSEDWANTEDRECEIPTDATE'=> $old->proposed_wanted_receipt_date,
                'VTIME'                     => $old->time,
                'IQUANTITY'                 => $old->quantity,
                'IQUANTITYCONFIRMATION'     => $old->quantity_confirmation,
                'IQUANTITYACTUAL'           => $old->quantity_actual,
                'VSTATUS'                   => $old->status,
                'VDELIVERYNOTENO'           => $old->delivery_note_no,
                'VPONO'                     => $old->po_no,
                'VDAILYREQNO'               => $old->daily_req_no,
                'VPRODUCTFAMILY'            => $old->product_family,
                'IREVNO'                    => $old->rev_no,
                'VFORECAST'                 => $old->forecast,
                'IMSPERIOD'                 => $old->ms_period,
                'IMSYEAR'                   => $old->ms_year,
                'VUNITMEAS'                 => $old->unit_meas,
                'VDEDICATEDLOCATION'        => $old->dedicated_location,
                'VPROCCONTACT'              => $old->proc_contact,
                'VCREA'                     => $old->created_by,
                'DCREA'                     => $old->created_date,
                'VMODI'                     => $old->modified_by,
                'DMODI'                     => $old->modified_date,
            ]);
        }

        $maxId = DB::connection('prcpwb')->table('PRCPWB_TRHDAILYREQUESTS')->max('IID');
        if ($maxId) {
            DB::connection('prcpwb')->statement("SELECT setval('\"SQ_TRHDAILYREQUESTS_IID\"', $maxId)");
        }
    }
}
