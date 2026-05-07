<?php

namespace Database\Seeders\prcpwb;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('prcpwb')->statement('TRUNCATE TABLE "PRCPWB_MSHVENDORS" RESTART IDENTITY CASCADE');
        $oldDatas = DB::connection('mysql_legacy')->table('sim_proc_poweb_vendor')->get();

        foreach ($oldDatas as $old) {
            DB::connection('prcpwb')->table('PRCPWB_MSHVENDORS')->insert([
                'VVENDORNO'   => $old->Vendor_No,
                'VVENDORNAME' => $old->Vendor_Name,
                'VCONTACT'    => $old->Contact,
                'VADDRESS'    => mb_convert_encoding($old->Address, 'UTF-8', 'ISO-8859-1'),
                'VIMPORT'     => $old->Import,
                'VCREA'       => $old->Created_By,
                'DCREA'       => $old->Created_Date,
                'VMODI'       => $old->Modify_By,
                'DMODI'       => $old->Modify_Date,
            ]);
        }

        $maxId = DB::connection('prcpwb')->table('PRCPWB_MSHVENDORS')->max('IID');
        if ($maxId) {
            DB::connection('prcpwb')->statement("SELECT setval('\"SQ_MSHVENDORS_IID\"', $maxId)");
        }
    }
}