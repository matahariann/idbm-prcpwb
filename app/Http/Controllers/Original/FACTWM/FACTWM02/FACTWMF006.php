<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM02;

use App\DataTables\Original\FACTWM02\FACTWMF006DataTable as GRNotesTable;
use App\Exports\FACTWM\FACTWM02\FACTWMF006 as GRNotesExport;
use App\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\FACTWM\FACTWM02\GoodReceiptNotesRequest;
use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION;
use App\Models\FACTWM02\FACTWM_TRDGR_NOTE_DETAILS as GRNoteDetails;
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES;
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES as GRNote;
use App\Services\FACTWM\GoodReceiptNotesService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class FACTWMF006 extends Controller
{
    public function __construct(
        private GoodReceiptNotesService $GoodReceiptNotesService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(GRNotesTable $dataTable)
    {
        return $dataTable->render('modules.FACTWM.FACTWM02.FACTWMF006.FACTWMF006');
    }

    /**
     * Display the specified resource with details.
     */
    public function show($id)
    {
        try {
            $grNote = GRNote::with('details')->findOrFail($id);

            // Hitung dan tambahkan DPP, PPN, Total untuk setiap detail
            $totalDpp = 0;
            $totalPpn = 0;
            $grandTotal = 0;

            $confData = FACTWM_MSHCONFIGURATION::whereIn('VVARIABLE', ['ppn', 'rumus_dpp'])->pluck('VVALUE');
            [$one, $two] = array_map('floatval', explode('/', $confData[1]));

            foreach ($grNote->details as $detail) {
                $amount = floatval($detail->VAMOUNT ?? 0);

                // Hitung DPP, PPN, dan Total per item
                $detail->DPP = round(($one / $two) * $amount, 2);
                $detail->PPN = round($detail->DPP * ((int) $confData[0] / 100), 2);
                $detail->TOTAL = round($amount + $detail->PPN, 2);

                // Akumulasi untuk total keseluruhan
                $totalDpp += $detail->DPP;
                $totalPpn += $detail->PPN;
                $grandTotal += $detail->TOTAL;
            }

            return Response::success(data: $grNote);
        } catch (\Exception $e) {
            return Response::error(message: 'GR Note not found');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // $jsonFile = base_path('dummy/good-receipt-notes.json');
        // $datas = json_decode(file_get_contents($jsonFile), true);
        $datas = $this->GoodReceiptNotesService->sync();
        DB::transaction(function () use ($datas) {
            foreach ($datas as $data) {
                $grNote = GRNote::updateOrCreate(
                    ['VGR_NUMBER' => $data['VGR_NUMBER']],
                    [
                        'VDELIVERY_NUMBER' => $data['VDELIVERY_NUMBER'] ?? null,
                        'VPO_NUMBER' => $data['VPO_NUMBER'] ?? null,
                        'VCURRENCY' => $data['VCURRENCY'] ?? null,
                        'VVENDOR_CODE' => $data['VVENDOR_CODE'] ?? null,
                        'VVENDOR_NAME' => $data['VVENDOR_NAME'] ?? null,
                        'VSTATUS' => $data['VSTATUS'] ?? null,
                        'DGR' => $data['DGR'] ?? null,
                        'DSYNC' => $data['DSYNC'] ?? null,
                        'DAPPROVE' => $data['DAPPROVE'] ?? null,
                        'DDISPUTE' => $data['DDISPUTE'] ?? null,
                    ]
                );

                // Insert GR Note Details
                if (! empty($data['details'])) {
                    $details = [];
                    foreach ($data['details'] as $detail) {
                        $details[] = [
                            'VGR_NUMBER' => $grNote->VGR_NUMBER,
                            'VMATERIAL_CODE' => $detail['VMATERIAL_CODE'] ?? null,
                            'VDESCRIPTION' => $detail['VDESCRIPTION'] ?? null,
                            'IQTY' => $detail['IQTY'] ?? null,
                            'UOM' => $detail['UOM'] ?? null,
                            'VPRICE' => $detail['VPRICE'] ?? null,
                            'VAMOUNT' => $detail['VAMOUNT'] ?? null,
                            'DGR' => $grNote->DGR,
                        ];
                    }

                    GRNoteDetails::upsert(
                        $details,
                        ['VGR_NUMBER', 'VMATERIAL_CODE'],
                        [
                            'VDESCRIPTION',
                            'IQTY',
                            'UOM',
                            'VPRICE',
                            'VAMOUNT',
                            'DGR',
                            'VMODI',
                            'DMODI',
                        ]
                    );
                }
            }
        });

        return Response::success(message: 'GR Notes synced successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GRNote $grNote)
    {
        $data = $request->validate([
            'VSTATUS' => 'nullable|string',
            'DAPPROVE' => 'nullable|date',
            'DDISPUTE' => 'nullable|date',
        ]);

        DB::transaction(function () use ($data, $grNote) {
            $grNote->update([
                'VSTATUS' => $data['VSTATUS'] ?? $grNote->VSTATUS,
                'DAPPROVE' => $data['DAPPROVE'] ?? $grNote->DAPPROVE,
                'DDISPUTE' => $data['DDISPUTE'] ?? $grNote->DDISPUTE,
            ]);
        });

        return Response::success(message: "GR Note {$grNote->VGR_NUMBER} updated successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GRNote $grNote)
    {
        DB::transaction(function () use ($grNote) {
            $grNote->details()->delete();
            $grNote->delete();
        });

        return Response::success(message: "GR Note {$grNote->VGR_NUMBER} deleted successfully");
    }

    /**
     * Approve the specified resource.
     */
    public function toggleApprove($id)
    {
        try {
            $grNote = GRNote::with(['returnGrs', 'returnGRRef'])->findOrFail($id);

            DB::transaction(function () use ($grNote) {
                if ($grNote->VSTATUS === 'CLOSED') {
                    throw new \Exception('Cannot modify a closed GR Note');
                }

                $this->assertReturnHasReceiptRef($grNote);

                if ($grNote->VSTATUS === 'APPROVED') {
                    $grNote->update([
                        'VSTATUS' => 'NEW',
                        'DAPPROVE' => null,
                    ]);

                    $this->syncReturnStatusFromReceipt($grNote, [
                        'VSTATUS' => 'NEW',
                        'DAPPROVE' => null,
                    ]);
                } else {
                    $grNote->update([
                        'VSTATUS' => 'APPROVED',
                        'DAPPROVE' => now(),
                    ]);

                    $this->syncReturnStatusFromReceipt($grNote, [
                        'VSTATUS' => 'APPROVED',
                        'DAPPROVE' => now(),
                    ]);
                }
            });

            $message = $grNote->VSTATUS === 'NEW'
                ? 'GR Note unapproved successfully'
                : 'GR Note approved successfully';

            return Response::success(message: $message);
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }
    }

    public function toggleApproveMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:FACTWM_TRHGR_NOTES,IID',
        ]);

        try {
            $ids = $request->input('ids', []);
            $approvedCount = 0;
            $unapprovedCount = 0;

            DB::transaction(function () use ($ids, &$approvedCount, &$unapprovedCount) {
                // Get all GR Notes yang valid (bukan CLOSED)
                $grNotes = GRNote::query()
                    ->with(['returnGrs', 'returnGRRef'])
                    ->whereIn('IID', $ids)
                    ->where('VSTATUS', '!=', 'CLOSED')
                    ->get();

                if ($grNotes->isEmpty()) {
                    throw new \Exception('No valid GR Notes to process (all are closed)');
                }

                // Ambil data GR dengan type return dan kumpulkan yang tidak memiliki reference receipt yang sedang diproses
                $returnGrNoRef = $grNotes->filter(function ($gr) {
                    return $gr->VREF_TYPE === 'RETURN' && $gr->returnGRRef === null;
                })->pluck('VGR_NUMBER')->toArray();

                if (count($returnGrNoRef) > 0) {
                    $grNoRef = implode(', ', $returnGrNoRef);
                    throw new Exception("RETURN GR {$grNoRef} doesn't have reference receipt.", 400);
                }

                foreach ($grNotes as $grNote) {
                    if ($grNote->VSTATUS === 'APPROVED') {
                        // Unapprove: APPROVED → NEW
                        $grNote->update([
                            'VSTATUS' => 'NEW',
                            'DAPPROVE' => null,
                        ]);

                        $this->syncReturnStatusFromReceipt($grNote, [
                            'VSTATUS' => 'NEW',
                            'DAPPROVE' => null,
                        ]);
                        $unapprovedCount++;
                    } else {
                        // Approve: NEW/DISPUTED → APPROVED
                        $grNote->update([
                            'VSTATUS' => 'APPROVED',
                            'DAPPROVE' => now(),
                        ]);

                        $this->syncReturnStatusFromReceipt($grNote, [
                            'VSTATUS' => 'APPROVED',
                            'DAPPROVE' => now(),
                        ]);

                        $approvedCount++;
                    }
                }
            });

            $message = [];
            if ($approvedCount > 0) {
                $message[] = "{$approvedCount} GR Note(s) approved";
            }
            if ($unapprovedCount > 0) {
                $message[] = "{$unapprovedCount} GR Note(s) unapproved";
            }

            return Response::success(message: implode('. ', $message));
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $fileName = 'GoodReceiptNotes_' . date('YmdHis') . '.xlsx';

        return Excel::download(new GRNotesExport($request), $fileName);
    }

    public function dispute(GoodReceiptNotesRequest $request)
    {
        try {
            $grNoteId = $request->input('grNoteId');
            $description = $request->input('description');
            $file = $request->file('file');

            $grNote = GRNote::with(['returnGrs', 'returnGRRef'])->findOrFail($grNoteId);

            // Handle file upload
            $filePath = null;
            if ($file) {
                $fileName = 'dispute_' . $grNoteId . '_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('disputes', $fileName, 'public');
            }

            // Update GR Note dengan dispute data
            DB::transaction(function () use ($grNote, $description, $filePath) {
                $this->assertReturnHasReceiptRef($grNote);

                $grNote->update([
                    'DDISPUTE' => now(),
                    'VSTATUS' => 'DISPUTED',
                    'VDISPUTEDESC' => $description,
                    'VDISPUTEFILE' => $filePath,
                ]);

                $this->syncReturnStatusFromReceipt($grNote, [
                    'VSTATUS' => 'DISPUTED',
                    'DDISPUTE' => now(),
                ]);
            });

            return Response::success(message: 'Dispute submitted successfully');
        } catch (\Exception $e) {
            return Response::error(message: 'Failed to submit dispute: ' . $e->getMessage());
        }
    }

    public function toggleRejectDispute(Request $request, $id)
    {
        try {
            $grNote = GRNote::with(['returnGrs', 'returnGRRef'])->findOrFail($id);

            $desc = $request->input('description', null);
            DB::transaction(function () use ($grNote, $desc) {
                if ($grNote->VSTATUS === 'CLOSED') {
                    throw new \Exception('Cannot modify a closed GR Note');
                }

                $this->assertReturnHasReceiptRef($grNote);

                if ($grNote->VSTATUS === 'DISPUTED' || str_starts_with($grNote->VSTATUS, 'DISPUTED-')) {
                    $grNote->update([
                        'VSTATUS' => 'NEW',
                        'DDISPUTE' => null,
                        'VDISPUTEREJECTDESC' => $desc,
                    ]);

                    $this->syncReturnStatusFromReceipt($grNote, [
                        'VSTATUS' => 'NEW',
                        'DDISPUTE' => null,
                    ]);
                } else {
                    throw new \Exception('GR Note is not in DISPUTED status');
                }
            });

            return Response::success(message: 'GR Note dispute rejected successfully');
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }
    }

    public function toggleApproveDispute($id)
    {
        try {
            $grNote = GRNote::with(['returnGrs', 'returnGRRef'])->findOrFail($id);

            DB::transaction(function () use ($grNote) {
                if ($grNote->VSTATUS === 'CLOSED') {
                    throw new \Exception('Cannot modify a closed GR Note');
                }

                $this->assertReturnHasReceiptRef($grNote);

                if ($grNote->VSTATUS !== 'APPROVED') {
                    $grNote->update([
                        'VSTATUS' => 'DISPUTED-Finance',
                    ]);

                    $this->syncReturnStatusFromReceipt($grNote, [
                        'VSTATUS' => 'DISPUTED-Finance',
                    ]);
                }
                // else {
                //     $grNote->update([
                //         'VSTATUS' => 'APPROVED',
                //     ]);
                // }
            });

            $message = $grNote->VSTATUS === 'NEW'
                ? 'GR Note unapproved successfully'
                : 'GR Note approved successfully';

            return Response::success(message: $message);
        } catch (\Exception $e) {
            return Response::error(message: $e->getMessage());
        }
    }

    private function syncReturnStatusFromReceipt(GRNote $grNote, array $fields): void
    {
        if ($grNote->VREF_TYPE !== 'RECEIPT') {
            return;
        }

        if (! $grNote->relationLoaded('returnGrs')) {
            $grNote->load('returnGrs');
        }

        if ($grNote->returnGrs->isEmpty()) {
            return;
        }

        $grNote->returnGrs()->update($fields);
    }

    private function assertReturnHasReceiptRef(GRNote $grNote): void
    {
        if ($grNote->VREF_TYPE !== 'RETURN') {
            return;
        }

        if ($grNote->returnGRRef !== null) {
            return;
        }

        throw new \Exception("RETURN GR {$grNote->VGR_NUMBER} doesn't have reference receipt.");
    }

    /**
     * Get summary data for selected GR Notes
     */
    public function getSummary(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        try {
            $ids = $request->input('ids');

            // Get GR Notes with their details
            $grNotes = FACTWM_TRHGR_NOTES::query()
                ->with('details')
                ->whereIn('IID', $ids)
                ->where('VREF_TYPE', 'RECEIPT')
                ->get();

            $confData = FACTWM_MSHCONFIGURATION::whereIn('VVARIABLE', ['ppn', 'rumus_dpp'])->pluck('VVALUE');
            [$one, $two] = array_map('floatval', explode('/', $confData[1]));

            $subtotal = 0;
            $ppn = 0;
            $dppNilaiLain = 0;

            foreach ($grNotes as $grNote) {
                foreach ($grNote->details as $detail) {
                    // Hitung subtotal dari amount
                    $amount = floatval($detail->VAMOUNT ?? 0);
                    $subtotal += $amount;
                }
            }

            // DPP Nilai Lain (sesuaikan dengan logic bisnis Anda)
            // Contoh: ambil dari field tertentu atau perhitungan khusus
            $dppNilaiLain = ($one / $two) * $subtotal; // Atau logic perhitungan lainnya

            // Hitung PPN (11% dari subtotal)
            $ppn = $dppNilaiLain * ((int) $confData[0] / 100);

            // Hitung total
            $total = $subtotal + $ppn;

            return response()->json([
                'success' => true,
                'data' => [
                    'subtotal' => $subtotal,
                    'ppn' => $ppn,
                    'dpp_nilai_lain' => $dppNilaiLain,
                    'total' => $total,
                    'count' => count($ids),
                    'gr_numbers' => $grNotes->pluck('VGR_NUMBER')->toArray(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate summary: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Alternative: Get summary using raw SQL for better performance
     */
    public function getSummaryOptimized(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        try {
            $ids = $request->input('ids');

            // Get summary dengan single query
            $summary = DB::table('FACTWM_TRHGR_NOTES as h')
                ->join('FACTWM_TRDGR_NOTE_DETAILS as d', 'h.VGR_NUMBER', '=', 'd.VGR_NUMBER')
                ->whereIn('h.IID', $ids)
                ->select(
                    DB::raw('SUM(CAST(d.VAMOUNT AS NUMERIC)) as subtotal'),
                    DB::raw('COUNT(DISTINCT h.IID) as gr_count')
                )
                ->first();

            $subtotal = floatval($summary->subtotal ?? 0);
            $ppn = $subtotal * 0.11;
            $dppNilaiLain = 0;
            $total = $subtotal + $ppn + $dppNilaiLain;

            return response()->json([
                'success' => true,
                'data' => [
                    'subtotal' => $subtotal,
                    'ppn' => $ppn,
                    'dpp_nilai_lain' => $dppNilaiLain,
                    'total' => $total,
                    'count' => $summary->gr_count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate summary: ' . $e->getMessage(),
            ], 500);
        }
    }
}
