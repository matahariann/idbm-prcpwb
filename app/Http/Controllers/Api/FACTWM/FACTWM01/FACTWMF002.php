<?php

namespace App\Http\Controllers\Api\FACTWM\FACTWM01;

use App\Helpers\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\FACTWM01\FACTWM_MSHSUPPLIER as Supplier;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER_COMMUNICATION_METHOD as SupplierMethod;
use App\Services\FACTWM\SupplierService;
use Illuminate\Support\Facades\Validator;

class FACTWMF002 extends Controller
{
    public function __construct(
        private SupplierService $supplierService
    ) {}

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            '*.SupplierId' => 'required',
            '*.Name' => 'required',
            '*.CommMethods' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $datas = $request->all();

        DB::transaction(function () use ($datas) {
            foreach ($datas as $data) {
                $supplier = Supplier::updateOrCreate(
                    ['VSUPPLIER_CODE' => $data['SupplierId']],
                    [
                        'VNAME' => $data['Name'],
                        'VADDRESS' => $data['address_tx'] ?? null,
                        'VCOUNTRY' => $data['Country'],
                        'VNPWP' => $data['NPWP'] ?? null,
                        'BPKP' => $data['PKP'] ?? false,
                    ]
                );

                $insertCom = [];
                $methods = $data['CommMethods'];

                foreach ($methods as $method) {
                    $mapped = $this->supplierService->mappingComMethods($method, $supplier);
                    $insertCom[] = $mapped;
                }

                SupplierMethod::upsert(
                    $insertCom,
                    ['ICOMM_ID'],
                    [
                        'VSUPPLIER_CODE',
                        'VSUPPLIER_NAME',
                        'VNAME',
                        'VMETHOD_ID',
                        'VDESCRIPTION',
                        'VADDRESS_ID',
                        'VPARTY_TYPE_DB_VAL',
                        'BMETHOD_DEFAULT',
                        'VVALUE'
                    ]
                );
            }
        });

        return Response::success(message: 'Supplier stored successful');
    }

    public function update()
    {
        return Response::success(message: 'This is GRN update request');
    }

    public function destroy($supplierId)
    {
        $supplier = Supplier::where('VSUPPLIER_CODE', $supplierId)->first();

        if (! $supplier) {
            return Response::error('Supplier not found', 404);
        }

        $supplier->methods()->delete();
        $supplier->delete();

        return Response::success(message: 'Supplier deleted successful');
    }
}
