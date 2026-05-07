<?php

namespace Database\Seeders\prcpwb;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::connection('prcpwb')->statement('TRUNCATE TABLE "PRCPWB_TRHSTOCKVENDORS" RESTART IDENTITY CASCADE');
        $oldDatas = DB::connection('mysql_legacy')->table('sim_proc_poweb_part_stock_vendor')->get();

        foreach ($oldDatas as $old) {
            DB::connection('prcpwb')->table('PRCPWB_TRHSTOCKVENDORS')->insert([
                'VVENDORNO'   => $old->vendor_no,
                'VPARTNO'     => $old->part_no,
                'EQTYONHAND'  => $old->qty_on_hand,
                'DUPLOADDATE' => $old->upload_date,
                'VREMARK'     => $old->remark,
                'VCREA'       => $old->Created_By,
                'DCREA'       => $old->Created_Date,
                'VMODI'       => $old->Modified_By,
                'DMODI'       => $old->Modified_Date,
            ]);
        }

        $maxId = DB::connection('prcpwb')->table('PRCPWB_TRHSTOCKVENDORS')->max('IID');
        if ($maxId) {
            DB::connection('prcpwb')->statement("SELECT setval('\"SQ_TRHSTOCKVENDORS_IID\"', $maxId)");
        }

    }
}
