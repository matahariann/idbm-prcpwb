<?php

namespace App\Http\Controllers\Original\PRCPWB\PRCPWB02;

use App\Models\PRCPWB02\PRCPWB_TRHDAILY_REQUEST as DailyRequest;
use App\DataTables\Original\PRCPWB02\PRCPWBF005DataTable;
use App\Exports\PRCPWB\PRCPWB02\PRCPWBF005 as ExportExcel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

// Daily Request
class PRCPWBF005 extends Controller
{
    public function index(PRCPWBF005DataTable $dataTable)
    {
        return $dataTable->render('modules.PRCPWB.PRCPWB02.PRCPWBF005.PRCPWBF005');
    }

    public function export(Request $request)
    {
        $params = [
            'selectAll'   => $request->boolean('selectAll'),
            'keyword'     => $request->input('keyword', ''),
            'excludedIds' => $request->input('excludedIds', []),
            'ids'         => $request->input('ids', []),
        ];

        if (!$params['selectAll'] && empty($params['ids'])) {
            abort(422, 'Tidak ada data yang dipilih.');
        }

        $filename = 'PRCPWBF005_TransaksiDailyRequest_' . date('YmdHis') . '.xlsx';

        return Excel::download(new ExportExcel($params), $filename);
    }
}
