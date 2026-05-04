<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM01;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Original\FACTWM01\FACTWMF002DataTable as SupplierTable;
use App\Helpers\Response;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER as Supplier;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER_COMMUNICATION_METHOD as SupplierMethod;
use App\Services\FACTWM\SupplierService;
use Illuminate\Support\Facades\DB;
use App\Exports\Templates\FACTWM\FACTWM01\FACTWMF002 as TemplateExcel;
use App\Exports\FACTWM\FACTWM01\FACTWMF002 as ExportExcel;
use App\Http\Requests\FACTWM\FACTWM01\SupplierRequest;
use App\Imports\FACTWM\FACTWM01\FACTWMF002 as ImportExcel;
use Maatwebsite\Excel\Facades\Excel;

class FACTWMF002 extends Controller
{
    public function __construct(
        private SupplierService $supplierService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(SupplierTable $dataTable)
    {
        return $dataTable->render('modules.FACTWM.FACTWM01.FACTWMF002.FACTWMF002');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datas = $this->supplierService->sync();

        DB::transaction(function () use ($datas) {
            foreach ($datas as $data) {
                $supplier = Supplier::updateOrCreate(
                    ['VSUPPLIER_CODE' => $data['SupplierId']],
                    [
                        'VNAME' => $data['Name'],
                        'VADDRESS' => $data['address_tx'] ?? null,
                        'VCOUNTRY' => $data['Country'],
                        'VPAYMENT_TERM' => $data['PayTermId'],
                        'VGROUP' => $data['GroupId'],
                        'VSTAT_GROUP' => $data['SuppGrp'],
                        'VTAX_CODE' => $data['DefVatCode'],
                        'BPKP' => true,
                        // 'VNPWP' => $data['NPWP'] ?? null,
                    ]
                );

                $incomingKeys = [];

                foreach ($data['CommMethods'] as $method) {
                    $mapped = $this->supplierService->mappingComMethods($method, $supplier);

                    $incomingKeys[] = [
                        'ISUPPLIER_ID' => $supplier->IID,
                        'VNAME'      => $mapped['VNAME'],
                        'VMETHOD_ID' => $mapped['VMETHOD_ID'],
                        'VVALUE'    => $mapped['VVALUE'],
                    ];

                    // Cek apakah data sudah ada
                    $existing = SupplierMethod::where('ISUPPLIER_ID', $supplier->IID)
                        ->where('VNAME', $mapped['VNAME'])
                        ->where('VVALUE', $mapped['VVALUE']) // atau field unique lainnya
                        ->where('VMETHOD_ID', $mapped['VMETHOD_ID'])
                        ->first();

                    if ($existing) {
                        // Update manual
                        $existing->ICOMM_ID = $mapped['ICOMM_ID'];
                        $existing->VSUPPLIER_CODE = $mapped['VSUPPLIER_CODE'];
                        $existing->VSUPPLIER_NAME = $mapped['VSUPPLIER_NAME'];
                        $existing->VNAME = $mapped['VNAME'];
                        $existing->VDESCRIPTION = $mapped['VDESCRIPTION'];
                        $existing->VADDRESS_ID = $mapped['VADDRESS_ID'];
                        $existing->VPARTY_TYPE_DB_VAL = $mapped['VPARTY_TYPE_DB_VAL'];
                        $existing->BMETHOD_DEFAULT = $mapped['BMETHOD_DEFAULT'];
                        $existing->save();
                    } else {
                        // Insert manual
                        $newMethod = new SupplierMethod();
                        $newMethod->ICOMM_ID = $mapped['ICOMM_ID'];
                        $newMethod->ISUPPLIER_ID = $mapped['ISUPPLIER_ID'];
                        $newMethod->VSUPPLIER_CODE = $mapped['VSUPPLIER_CODE'];
                        $newMethod->VSUPPLIER_NAME = $mapped['VSUPPLIER_NAME'];
                        $newMethod->VNAME = $mapped['VNAME'];
                        $newMethod->VMETHOD_ID = $mapped['VMETHOD_ID'];
                        $newMethod->VDESCRIPTION = $mapped['VDESCRIPTION'];
                        $newMethod->VADDRESS_ID = $mapped['VADDRESS_ID'];
                        $newMethod->VPARTY_TYPE_DB_VAL = $mapped['VPARTY_TYPE_DB_VAL'];
                        $newMethod->BMETHOD_DEFAULT = $mapped['BMETHOD_DEFAULT'];
                        $newMethod->VVALUE = $mapped['VVALUE'];
                        $newMethod->save();
                    }
                }

                if (empty($incomingKeys)) {
                    // Jika CommMethods kosong → hapus semua
                    SupplierMethod::where('ISUPPLIER_ID', $supplier->IID)->delete();
                } else {
                    SupplierMethod::where('ISUPPLIER_ID', $supplier->IID)
                        ->whereNot(function ($query) use ($incomingKeys) {
                            foreach ($incomingKeys as $key) {
                                $query->orWhere(function ($q) use ($key) {
                                    $q->where('VMETHOD_ID', $key['VMETHOD_ID'])
                                        ->where('ISUPPLIER_ID', $key['ISUPPLIER_ID'])
                                        ->where('VNAME', $key['VNAME'])
                                        ->where('VVALUE', $key['VVALUE']);
                                });
                            }
                        })
                        ->delete();
                }
            }
        });

        return Response::success(message: 'Supplier synced successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $vendor)
    {
        $vendor->load(['methods']);

        return Response::success(data: $vendor);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierRequest $request, Supplier $vendor)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $vendor) {
            $vendor->update([
                'VNPWP' => $data['npwp'],
                'VNIK' => $data['nik'],
                'BPKP' => $data['pkp'],
            ]);
        });

        return Response::success(message: "Vendor {$vendor->VNAME} updated successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function template()
    {
        return Excel::download(new TemplateExcel, 'Master_Vendor-template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required'
        ]);

        $import = new ImportExcel;

        Excel::import($import, $request->file, \Maatwebsite\Excel\Excel::XLSX);
        $result = $import->getResult();
        if ($result->totalError > 0) {
            return response()->json([
                'success' => false,
                'message' => "Validation error in {$result->totalErrorLogs} out of {$result->totalLogs} records",
                'data' => [
                    'total_data' => $result->totalLogs,
                    'total_error_data' => $result->totalErrorLogs,
                    'total_error' => $result->totalError,
                    'error_data' => $result->errorLogs->values(),
                ],
            ], 400);
        }

        return Response::success(message: 'Vendor updated successfully');
    }

    public function export(Request $request)
    {
        $selected = $request->input('ids', []);

        $fileName = 'Master_Vendor_' . date('YmdHis') . '.xlsx';

        return Excel::download(new ExportExcel($selected), $fileName);
    }
}
