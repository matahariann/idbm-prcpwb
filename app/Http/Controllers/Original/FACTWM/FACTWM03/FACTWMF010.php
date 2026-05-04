<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM03;

use App\DataTables\Original\FACTWM03\FACTWMF010DataTable;
use App\Exports\FACTWM\FACTWM03\FACTWMF010 as FACTWM03FACTWMF010;
use App\Http\Controllers\Controller;
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class FACTWMF010 extends Controller
{
    public function index(FACTWMF010DataTable $dataTable)
    {
        return $dataTable->render('modules.FACTWM.FACTWM03.FACTWMF010.FACTWMF010');
    }

    /**
     * Get child items for a specific GRN
     * This endpoint is used for AJAX request when expanding rows
     */
    public function getItems(Request $request, $id): JsonResponse
    {
        try {
            $grn = FACTWM_TRHGR_NOTES::with('details')
                ->whereNull('DDELETE')
                ->find($id);

            if (!$grn) {
                return response()->json([
                    'success' => false,
                    'message' => 'GRN not found'
                ], 404);
            }

            $items = $grn->details->map(function ($detail) use ($grn) {
                return [
                    'part_number' => $detail->VMATERIAL_CODE,
                    'description' => $detail->VDESCRIPTION,
                    'qty' => $detail->IQTY,
                    'uom' => $detail->UOM,
                    'price' => floatval($detail->VPRICE ?? 0),
                    'currency' => $detail->VCURRENCY ?? '-',
                    'sub_total' => floatval($detail->VAMOUNT ?? 0),
                    'dpp_nilai_lain' => 0, // Sesuaikan dengan field yang ada
                    'ppn' => 0, // Sesuaikan dengan field yang ada
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $items
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $grn = FACTWM_TRHGR_NOTES::with('details')
                ->whereNull('DDELETE')
                ->find($id);

            if (!$grn) {
                return response()->json([
                    'success' => false,
                    'message' => 'GRN not found'
                ], 404);
            }

            $data = [
                'id' => $grn->IID,
                'grn_no' => $grn->VGR_NUMBER,
                'grn_date' => $grn->DGR ? Carbon::parse($grn->DGR)->format('Y-m-d') : null,
                'delivery_no' => $grn->VDELIVERY_NUMBER,
                'po_no' => $grn->VPO_NUMBER,
                'currency' => $grn->VCURRENCY,
                'vendor_code' => $grn->VVENDOR_CODE,
                'vendor_name' => $grn->VVENDOR_NAME,
                'status_grn' => $grn->VSTATUS ?? 'New',
                'dispute_file' => $grn->VDISPUTEFILE,
                'dispute_desc' => $grn->VDISPUTEDESC,
                'items' => $grn->details->map(function ($detail) {
                    return [
                        'part_number' => $detail->VMATERIAL_CODE,
                        'description' => $detail->VDESCRIPTION,
                        'qty' => $detail->IQTY,
                        'uom' => $detail->UOM,
                        'price' => floatval($detail->VPRICE ?? 0),
                        'currency' => $detail->VCURRENCY ?? '-',
                        'sub_total' => floatval($detail->VAMOUNT ?? 0),
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve GRN: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approve(string $id)
    {
        try {
            DB::beginTransaction();

            $grn = FACTWM_TRHGR_NOTES::whereNull('DDELETE')->find($id);

            if (!$grn) {
                return response()->json([
                    'success' => false,
                    'message' => 'GRN not found'
                ], 404);
            }

            // Update status to Approved
            $grn->VSTATUS = 'Approved';
            $grn->DAPPROVE = Carbon::now();
            $grn->VMODI = Auth::user()->name ?? 'system';
            $grn->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'GRN approved successfully',
                'data' => [
                    'id' => $grn->IID,
                    'grn_no' => $grn->VGR_NUMBER,
                    'status_grn' => $grn->VSTATUS,
                    'approve_date' => $grn->DAPPROVE->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve GRN: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            return Excel::download(
                new FACTWM03FACTWMF010($request),
                'GRN_Report_' . date('YmdHis') . '.xlsx'
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export: ' . $e->getMessage());
        }
    }
}
