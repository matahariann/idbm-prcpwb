<?php

namespace App\Http\Controllers\Original\PRCPWB\PRCPWB01;

use App\Models\PRCPWB01\PRCPWB_MSHVENDOR as Vendor;
use App\Helpers\Response;
use App\DataTables\Original\PRCPWB01\PRCPWBF002DataTable;
use App\Exports\PRCPWB\PRCPWB01\PRCPWBF002 as ExportExcel;
use App\Http\Requests\PRCPWB\PRCPWB01\VendorRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


// Master Vendor
class PRCPWBF002 extends Controller
{
    public function index(PRCPWBF002DataTable $dataTable)
    {
        return $dataTable->render('modules.PRCPWB.PRCPWB01.PRCPWBF002.PRCPWBF002');
    }

    /**
     * Display the specified resource.
     */
    public function show(Vendor $vendor)
    {
        return Response::success(data: $vendor);
    }

    /**
     * Update the specified resource in storage.
    */
    public function update(VendorRequest $request, Vendor $vendor){
        $data = $request->validated();

        DB::transaction(function () use ($data, $vendor) {
            $vendor->update([
                'VCONTACT' => $data['contact'] ?? null,
                'VADDRESS' => $data['address'] ?? null,
                'VIMPORT'  => $data['import'] ?? null, 
            ]);
        });

        return Response::success(message: "Vendor {$vendor->VVENDORNAME} updated successfully");
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

        $filename = 'PRCPWBF002_MasterVendor_' . date('YmdHis') . '.xlsx';

        return Excel::download(new ExportExcel($params), $filename);
    }
}
