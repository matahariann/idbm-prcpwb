<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM03;

use App\DataTables\Original\FACTWM03\FACTWMF012Datatable;
use App\Exports\FACTWM\FACTWM03\FACTWMF012 as FACTWMF012Export;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\FACTWM02\FACTWM_TRHVERIFY_PO as VerifyPo;
use App\Models\FACTWM02\FACTWM_TRDVERIFY_PO_DETAILS as VerifyPoDetail;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_NON_PO as VerifyNonPo;
use App\Models\FACTWM02\FACTWM_TRDVERIFY_NON_PO_DETAILS as VerifyNonPoDetail;
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES as GRNote;
use App\Models\FACTWM02\FACTWM_TRDGR_NOTE_DETAILS as GRNoteDetails;
use Exception;
use Carbon\Carbon;

class FACTWMF012 extends Controller
{
    public function index(FACTWMF012Datatable $dataTable)
    {
        return $dataTable->render('modules.FACTWM.FACTWM03.FACTWMF012.FACTWMF012');
    }

    public function export(Request $request)
    {
        $export = new FACTWMF012Export();

        return Excel::download($export, 'Report_Overview' . date('YmdHis') . '.xlsx');
    }

    public function getDetail(Request $request, $id)
    {
        $category = $request->input('transaction_category');
        try {
            if ($category === 'PO') {
                $verify = GRNote::find($id);
                // dd($verify);

                if (!$verify) {
                    throw new Exception('Data GRN Not Found');
                }

                // $details = collect($verify->details ?? []);
                $details = GRNoteDetails::where('VGR_NUMBER', $verify->VGR_NUMBER)->get();
            } else {
                $verify = VerifyNonPo::find($id);

                if (!$verify) {
                    throw new Exception('Data Non PO Not Found');
                }

                $details = collect($verify->details ?? []);
            }

            $mapped = $details->map(function ($item) use ($category, $verify) {

                // dd($item);
                // hitung aging AP (dalam hari)
                $agingAp = 0;
                if (!empty($item->DCREA)) {
                    if ($category == 'PO') {
                        $agingAp = (int) Carbon::parse($verify->DGR)->diffInDays(Carbon::today());
                    } else {
                        $agingAp = (int) Carbon::parse($item->DCREA)->diffInDays(Carbon::today());
                    }
                }

                if ($category == 'PO') {
                    return [
                        'part_number' => $item->VMATERIAL_CODE ?? '-',
                        'description' => $item->VDESCRIPTION ?? '-',
                        'qty'         => (int) ($item->IQTY ?? 0),
                        'price'       => (float) ($item->VPRICE ?? 0),
                        'curr'        => $item->VCURRENCY,
                        'subtotal'    => (float) ($item->VAMOUNT ?? 0),
                        'ppn'         => (float) 0,
                        'aging_ap'    => $agingAp,
                    ];
                } else {
                    return [
                        'part_number' => '-',
                        'description' => $item->VDESCRIPTION ?? '-',
                        'qty'         => (int) ($item->IQTY ?? 0),
                        'price'       => (float) ($item->IPRICE ?? 0),
                        'curr'        => NULL,
                        'subtotal'    => (float) ($item->ITOTAL ?? 0),
                        'ppn'         => (float) 0,
                        'aging_ap'    => $agingAp,
                    ];
                }
            });

            return response()->json([
                'data' => $mapped->values()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }
}
