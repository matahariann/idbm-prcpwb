<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM02;

use App\DataTables\Original\FACTWM02\FACTWMF007DataTable;
use App\Helpers\Helpers;
use App\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\FACTWM\FACTWM02\VerifyPoRequest;
use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION as Config;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER as Supplier;
use App\Models\FACTWM02\FACTWM_LOGVERIFY_PO_OTHER_FILES;
use App\Models\FACTWM02\FACTWM_TRDGR_NOTE_DETAILS as GRNoteDetail;
use App\Models\FACTWM02\FACTWM_TRDVERIFY_PO_DETAILS as VerifyPoDetail;
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES as GRNote;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_PO as VerifyPo;
use App\Services\FACTWM\DMSService;
use App\Services\FACTWM\InvoiceService;
use App\Services\FACTWM\OCRService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FACTWMF007 extends Controller
{
    public function __construct(private OCRService $ocrService, private DMSService $dmsService, private InvoiceService $invoiceService) {}

    public function index(FACTWMF007DataTable $dataTable)
    {
        Gate::authorize('create', VerifyPo::class);

        return $dataTable->render('modules.FACTWM.FACTWM02.FACTWMF007.FACTWMF007');
    }

    public function store(VerifyPoRequest $request)
    {
        Gate::authorize('create', VerifyPo::class);
        $verifyPo = DB::transaction(function () use ($request) {
            $supplierCode = Auth::user()->supplierUser->VSUPPLIER_CODE;
            $validated = $request->validated();
            // $this->assertInvoiceLock($validated);
            $draftId = $request->input('draft_id');
            $existingDraft = null;

            if (! empty($draftId)) {
                $existingDraft = VerifyPo::where('IID', $draftId)
                    ->where('VSUPPLIER_CODE', $supplierCode)
                    ->where('VSTATUS', 'draft')
                    ->first();
            }

            $billing_statement = $existingDraft?->VBILLING_STATEMENT ?? $this->generateBillingNumber();

            $folderPath = 'po' . '/' . $billing_statement . '/' . $supplierCode . '_' . $validated['invoice_number'];

            $invoiceFile = $existingDraft?->VINVOICE_FILE;
            $taxFile = $existingDraft?->VTAX_INVOICE_FILE;

            if ($request->has('invoice_file')) {

                $file = $request->file('invoice_file');

                $baseName = Str::slug($file->getClientOriginalName());

                $extension = $file->getClientOriginalExtension();

                $fileName = $baseName . '-' . time() . '.' . $extension;

                $invoiceFile = $file
                    ->storeAs('invoices/' . $folderPath, $fileName, 'public');

                if (! empty($existingDraft?->VINVOICE_FILE) && Storage::disk('public')->exists($existingDraft->VINVOICE_FILE)) {
                    Storage::disk('public')->delete($existingDraft->VINVOICE_FILE);
                }
            }

            if ($request->has('tax_file')) {

                $file = $request->file('tax_file');

                $baseName = Str::slug($file->getClientOriginalName());

                $extension = $file->getClientOriginalExtension();

                $fileName = $baseName . '-' . time() . '.' . $extension;

                $taxFile = $file
                    ->storeAs('taxes/' . $folderPath, $fileName, 'public');

                if (! empty($existingDraft?->VTAX_INVOICE_FILE) && Storage::disk('public')->exists($existingDraft->VTAX_INVOICE_FILE)) {
                    Storage::disk('public')->delete($existingDraft->VTAX_INVOICE_FILE);
                }
            }

            $cacheKey = 'selected_gr_verify_po_' . Auth::user()->IID;

            $grList = Cache::get($cacheKey, '');

            $arrGrList = is_array($grList) ? $grList : [$grList];

            $grNumbers = array_map('trim', explode(',', $grList));

            $iids = GRNote::query()
                ->whereIn('VGR_NUMBER', $grNumbers)
                ->pluck('IID')
                ->toArray();

            // check old verify po
            $checkOldVerifyPoQuery = VerifyPo::where('VINVOICE_NUMBER', $validated['invoice_number'])
                ->where('VSTATUS', 'draft');
            if (! empty($existingDraft)) {
                $checkOldVerifyPoQuery->where('IID', '<>', $existingDraft->IID);
            }
            $check_old_verify_po = $checkOldVerifyPoQuery->first();
            if (! empty($check_old_verify_po)) {
                $check_old_detail_verify_po = VerifyPoDetail::where('TRHVERIFY_PO_IID', $check_old_verify_po->IID)->get();
                // delete old file db
                $check_old_log = FACTWM_LOGVERIFY_PO_OTHER_FILES::where('TRHVERIFY_PO_IID', $check_old_verify_po->IID)->get();
                if (count($check_old_log) > 0) {
                    foreach ($check_old_log as $key => $log) {
                        if (Storage::disk('public')->exists($log->VPATH)) {
                            Storage::disk('public')->delete($log->VPATH);
                        }
                    }

                    FACTWM_LOGVERIFY_PO_OTHER_FILES::where('TRHVERIFY_PO_IID', $check_old_verify_po->IID)->delete();
                }

                if (count($check_old_detail_verify_po) > 0) {
                    VerifyPoDetail::where('TRHVERIFY_PO_IID', $check_old_verify_po->IID)->delete();
                }

                $files = [
                    $check_old_verify_po->VINVOICE_FILE,
                    $check_old_verify_po->VTAX_INVOICE_FILE,
                    $check_old_verify_po->VREKAP_JASA_FILE,
                ];

                foreach ($files as $file) {
                    if (! empty($file) && Storage::disk('public')->exists($file)) {
                        Storage::disk('public')->delete($file);
                    }
                }

                VerifyPo::where('IID', $check_old_verify_po->IID)->delete();
            }

            $create = [
                'VSUPPLIER_CODE' => $supplierCode,
                'VGRN_NUMBER' => $grList,
                'VBILLING_STATEMENT' => $billing_statement,
                'VUNIQUE_CODE' => random_int(100000, 999999),
                'VINVOICE_NUMBER' => $validated['invoice_number'],
                'DINVOICE_DATE' => $validated['invoice_date'],
                'VTAX_INVOICE_NUMBER' => isset($validated['tax_invoice'])
                    ? $validated['tax_invoice']
                    : null,
                'DTAX_INVOICE_DATE' => isset($validated['tax_invoice_date'])
                    ? $validated['tax_invoice_date']
                    : null,
                'ITOTAL' => (int) str_replace('.', '', $validated['total']),
                'IPPN' => (int) str_replace('.', '', $validated['ppn']),
                'IDPP' => (int) str_replace('.', '', $validated['dpp']),
                'INET_AMOUNT' => (int) str_replace('.', '', $validated['net-amount']),
                'VNPWP_SUPPLIER' => isset($validated['npwp_supplier'])
                    ? (int) $validated['npwp_supplier']
                    : null,
                'VINVOICE_FILE' => $invoiceFile,
                'VTAX_INVOICE_FILE' => $taxFile,
                'VGR_NUMBER_IID' => $iids,
                'VPPH' => isset($validated['pph'])
                    ? $validated['pph']
                    : null,
                'VOBJECT' => isset($validated['object'])
                    ? $validated['object']
                    : null,
                'IDPP_PPH' => isset($validated['dpp-pph'])
                    ? (int) str_replace('.', '', $validated['dpp-pph'])
                    : null,
                'FTARRIF' => isset($validated['tarrif'])
                    ? (int) str_replace('.', '', $validated['tarrif'])
                    : null,
                'FVALUE' => isset($validated['value'])
                    ? (int) str_replace('.', '', $validated['value'])
                    : null,
            ];

            $total = (int) str_replace('.', '', $validated['total']);
            $requireMateraiOcr = $this->resolveRequireMateraiOcr($total);
            $materaiOcrCache = $this->getMateraiOcrCache();

            $create['VREQUIRE_MATERAI_OCR'] = $requireMateraiOcr;
            $create['VOCR_MATERAI_STATUS'] = $requireMateraiOcr === 'Y'
                ? ($materaiOcrCache['status'] ?? $existingDraft?->VOCR_MATERAI_STATUS ?? 'PENDING')
                : null;

            $selectedPph = strtolower((string) ($validated['pph'] ?? ''));

            if ($selectedPph === 'none') {
                $create['VREKAP_JASA_FILE'] = null;
                $create['IDPP_PPH'] = null;
                $create['FTARRIF'] = null;
                $create['FVALUE'] = null;
                $create['VOBJECT'] = null;

                if (! empty($existingDraft?->VREKAP_JASA_FILE) && Storage::disk('public')->exists($existingDraft->VREKAP_JASA_FILE)) {
                    Storage::disk('public')->delete($existingDraft->VREKAP_JASA_FILE);
                }
            } elseif ($request->has('rekap_jasa_file')) {
                $file = $request->file('rekap_jasa_file');

                $baseName = Str::slug($file->getClientOriginalName());

                $extension = $file->getClientOriginalExtension();

                $fileName = $baseName . '-' . time() . '.' . $extension;

                $rekapFile = $file
                    ->storeAs('rekap/' . $folderPath, $fileName, 'public');

                $create['VREKAP_JASA_FILE'] = $rekapFile;
                if (! empty($existingDraft?->VREKAP_JASA_FILE) && Storage::disk('public')->exists($existingDraft->VREKAP_JASA_FILE)) {
                    Storage::disk('public')->delete($existingDraft->VREKAP_JASA_FILE);
                }
            } elseif (! empty($existingDraft?->VREKAP_JASA_FILE)) {
                $create['VREKAP_JASA_FILE'] = $existingDraft->VREKAP_JASA_FILE;
            }

            // simpan gambar other file di cache agar tidak duplicate

            // $userId = Auth::user()->IID;
            // $cacheKey = 'list_other_file_verify_po_' . $userId;

            // $files = Cache::get($cacheKey, []);

            // $files[] = $fileName;

            // Cache::put($cacheKey, $files, now()->endOfDay());

            $cacheKey = 'selected_gr_scan_ocrverify_po_unverified_' . Auth::user()->IID;

            $config_limit_eskalated = Config::where('VVARIABLE', 'limit_eskalated')->first();

            $limit_eskalated = (int) $config_limit_eskalated->VVALUE ?? 3;

            $unverifyOCR = Cache::get($cacheKey, '');

            // if ($unverifyOCR >= $limit_eskalated) {
            if ($request->status == 'ESCALATED') {
                // $arrGrList = is_array($grList)
                //     ? $grList
                //     : [$grList];

                $this->updateSubmittedStatusForSelectedAndReturnGr($iids, 'ESCALATED');

                $create['VSTATUS_INVOICE'] = 'ESCALATED';
                $create['VSTATUS'] = 'submit';
            } else {
                $create['VSTATUS_INVOICE'] = 'WAITING';
                $create['VSTATUS'] = 'draft';
            }

            if (! empty($existingDraft)) {
                $existingDraft->update($create);
                $verifyPo = $existingDraft;
                VerifyPoDetail::where('TRHVERIFY_PO_IID', $verifyPo->IID)->delete();
            } else {
                $verifyPo = VerifyPo::create($create);
            }

            // verify po detail
            $grNoteDetail = GRNoteDetail::whereIn('VGR_NUMBER', $grNumbers)->get();
            if (count($grNoteDetail) > 0) {
                foreach ($grNoteDetail as $grd) {
                    $grNote = GRNote::where('VGR_NUMBER', $grd->VGR_NUMBER)->first();
                    VerifyPoDetail::create([
                        'TRDGR_NOTE_DETAILS_IID' => $grd->IID,
                        'TRHVERIFY_PO_IID' => $verifyPo->IID,
                        'FACTWM_TRHGR_NOTES_DGR' => $grNote->DGR,
                    ]);
                }
            }

            // $fileNames = [];
            if ($request->has('otherFiles')) {
                foreach ($request->otherFiles as $file) {
                    $uploadedFile = $file['file'];

                    $baseName = Str::slug($file['name']);

                    $extension = $uploadedFile->getClientOriginalExtension();

                    $fileName = $baseName . '-' . time() . '.' . $extension;

                    $path = $uploadedFile->storeAs(
                        'others/' . $folderPath,
                        $fileName,
                        'public'
                    );

                    FACTWM_LOGVERIFY_PO_OTHER_FILES::create([
                        'TRHVERIFY_PO_IID' => $verifyPo->IID,
                        'VNAME' => $file['name'],
                        'VPATH' => $path,
                    ]);

                    // $fileNames[] = $fileName;
                }
            }

            $deletedExistingOtherFileIds = json_decode($request->input('deleted_existing_other_file_ids', '[]'), true);
            if (is_array($deletedExistingOtherFileIds) && count($deletedExistingOtherFileIds) > 0) {
                $logsToDelete = FACTWM_LOGVERIFY_PO_OTHER_FILES::where('TRHVERIFY_PO_IID', $verifyPo->IID)
                    ->whereIn('IID', $deletedExistingOtherFileIds)
                    ->get();

                foreach ($logsToDelete as $log) {
                    if (! empty($log->VPATH) && Storage::disk('public')->exists($log->VPATH)) {
                        Storage::disk('public')->delete($log->VPATH);
                    }
                }

                FACTWM_LOGVERIFY_PO_OTHER_FILES::where('TRHVERIFY_PO_IID', $verifyPo->IID)
                    ->whereIn('IID', $deletedExistingOtherFileIds)
                    ->delete();
            }

            return $verifyPo;
        });

        $this->clearInvoiceLockCache();
        Cache::put($this->getLastDraftCacheKey(), $verifyPo->IID, now()->endOfDay());

        return Response::success(message: "Invoice $verifyPo->VINVOICE_NUMBER submitted", data: $verifyPo);
    }

    public function view()
    {
        Gate::authorize('create', VerifyPo::class);

        $config_ppn = Config::where('VVARIABLE', 'ppn')->first();
        $config_rumus_dpp = Config::where('VVARIABLE', 'rumus_dpp')->first();

        $rumus = $config_rumus_dpp->VVALUE ?? '11/12';
        [$num, $den] = array_map('floatval', explode('/', $rumus));

        $rumus_dpp = ($den == 0) ? 0 : ($num / $den);

        return view('modules.FACTWM.FACTWM02.FACTWMF007.partials._view', [
            'ppn' => (int) $config_ppn->VVALUE ?? 12,
            'rumus_dpp' => $rumus_dpp,
        ]);
    }

    public function ocr()
    {
        Gate::authorize('create', VerifyPo::class);

        $config_ppn = Config::where('VVARIABLE', 'ppn')->first();
        $config_rumus_dpp = Config::where('VVARIABLE', 'rumus_dpp')->first();
        $config_limit_eskalated = Config::where('VVARIABLE', 'limit_eskalated')->first();
        $config_list_pph_pasal = Config::where('VVARIABLE', 'list_pph_pasal')->first();

        $rumus = $config_rumus_dpp->VVALUE ?? '11/12';
        [$num, $den] = array_map('floatval', explode('/', $rumus));

        $rumus_dpp = ($den == 0) ? 0 : ($num / $den);

        $userId = Auth::user()->IID;
        $cacheKey = 'selected_gr_scan_ocrverify_po_unverified_' . $userId;

        // hapus cache
        Cache::forget($cacheKey);
        $this->clearInvoiceLockCache();
        $this->clearMateraiOcrCache();

        // get pkp supplier
        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;

        $idViewers = $userSupplier->ISUPPLIER_ID;
        $supplier = Supplier::findOrFail($idViewers);
        // $supplier_code = $supplier->VSUPPLIER_CODE;
        $npwp = ! empty($supplier->VNPWP) ? $supplier->VNPWP : '';
        $pkp_supplier = $supplier->BPKP;
        $list_pph_pasal = $config_list_pph_pasal->VVALUE ?? null;

        return view('modules.FACTWM.FACTWM02.FACTWMF007.partials._ocr', [
            'ppn' => (int) $config_ppn->VVALUE ?? 12,
            'rumus_dpp' => $rumus_dpp,
            'limit_eskalated' => (int) $config_limit_eskalated->VVALUE ?? 3,
            'pkp_supplier' => $pkp_supplier,
            'config_list_pph_pasal' => $list_pph_pasal,
        ]);
    }

    public function draftLast()
    {
        Gate::authorize('create', VerifyPo::class);

        $draftId = Cache::get($this->getLastDraftCacheKey());
        if (empty($draftId)) {
            return Response::success(data: null);
        }

        $supplierCode = Auth::user()->supplierUser->VSUPPLIER_CODE;
        $draft = VerifyPo::where('IID', $draftId)
            ->where('VSUPPLIER_CODE', $supplierCode)
            ->where('VSTATUS', 'draft')
            ->first();

        if (! $draft) {
            Cache::forget($this->getLastDraftCacheKey());

            return Response::success(data: null);
        }

        $otherFiles = FACTWM_LOGVERIFY_PO_OTHER_FILES::where('TRHVERIFY_PO_IID', $draft->IID)
            ->get()
            ->map(fn($item) => [
                'id' => $item->IID,
                'name' => $item->VNAME,
            ])
            ->values();

        return Response::success(data: [
            'id' => $draft->IID,
            'invoice_number' => $draft->VINVOICE_NUMBER,
            'invoice_date' => optional($draft->DINVOICE_DATE)->format('Y-m-d'),
            'tax_invoice' => $draft->VTAX_INVOICE_NUMBER,
            'tax_invoice_date' => optional($draft->DTAX_INVOICE_DATE)->format('Y-m-d'),
            'pph' => $draft->VPPH,
            'object' => $draft->VOBJECT,
            'dpp_pph' => $draft->IDPP_PPH ? number_format((int) $draft->IDPP_PPH, 0, ',', '.') : '',
            'tarrif' => $draft->FTARRIF ?? '',
            'value' => $draft->FVALUE ? number_format((int) $draft->FVALUE, 0, ',', '.') : '',
            'invoice_file_name' => $draft->VINVOICE_FILE ? basename($draft->VINVOICE_FILE) : '',
            'tax_file_name' => $draft->VTAX_INVOICE_FILE ? basename($draft->VTAX_INVOICE_FILE) : '',
            'rekap_jasa_file_name' => $draft->VREKAP_JASA_FILE ? basename($draft->VREKAP_JASA_FILE) : '',
            'require_materai_ocr' => $draft->VREQUIRE_MATERAI_OCR,
            'ocr_materai_status' => $draft->VOCR_MATERAI_STATUS,
            'other_files' => $otherFiles,
        ]);
    }

    public function clearOcrState(Request $request)
    {
        Gate::authorize('create', VerifyPo::class);

        $userId = Auth::user()->IID;
        $supplierCode = Auth::user()->supplierUser->VSUPPLIER_CODE;
        $unverifiedCacheKey = 'selected_gr_scan_ocrverify_po_unverified_' . $userId;
        $draftId = Cache::get($this->getLastDraftCacheKey());

        if (! empty($draftId)) {
            $draft = VerifyPo::where('IID', $draftId)
                ->where('VSUPPLIER_CODE', $supplierCode)
                ->where('VSTATUS', 'draft')
                ->first();

            if ($draft) {
                $this->deleteDraftVerifyPo($draft);
            }
        }

        Cache::forget($this->getLastDraftCacheKey());
        Cache::forget($unverifiedCacheKey);
        $this->clearInvoiceLockCache();
        $this->clearMateraiOcrCache();

        return Response::success(data: true);
    }

    public function finalPreview(VerifyPo $verifyPo)
    {
        Gate::authorize('finalPreview', $verifyPo);

        $supplier = Supplier::where('VSUPPLIER_CODE', $verifyPo->VSUPPLIER_CODE)->first();

        if (! $supplier) {
            throw new Exception('Data supplier not found');
        }

        $taxCode = $supplier->BPKP ? 'V11' : 'V0';

        // $iids = json_decode($verifyPo->VGR_NUMBER_IID, true);

        $folderPath = 'po' . '/' . $verifyPo->VBILLING_STATEMENT . '/' . $verifyPo->VSUPPLIER_CODE . '_' . $verifyPo->VINVOICE_NUMBER;

        $folder = 'others/' . $folderPath;

        $otherFiles = FACTWM_LOGVERIFY_PO_OTHER_FILES::where('TRHVERIFY_PO_IID', $verifyPo->IID)->get();

        $config_tnc_verify_po_non_po = Config::where('VVARIABLE', 'tnc_verify_po_non_po')->first();

        $tnc_verify_po_non_po = $config_tnc_verify_po_non_po->VVALUE ?? '-';

        $payload = $this->mapDataPayload($verifyPo);

        return view('modules.FACTWM.FACTWM02.FACTWMF007.partials._final-preview', compact('verifyPo', 'otherFiles', 'tnc_verify_po_non_po', 'payload'));
    }

    public function previewPdf(VerifyPo $verifyPo)
    {
        // Gate::authorize('previewPdf', $verifyPo);

        return Pdf::loadView('modules.FACTWM.FACTWM02.FACTWMF007.partials._print-preview', compact('verifyPo'))
            ->setPaper('A4', 'portrait')
            ->stream('billing_statement.pdf');
        // return view('modules.FACTWM.FACTWM02.FACTWMF007.partials._print-preview', compact('verifyPo'));
    }

    public function previewPdfGRN($grId)
    {
        // Gate::authorize('previewPdf', $verifyPo);

        $grn = GRNote::find($grId);
        // dd($grn);
        $verifyPo = VerifyPo::whereJsonContains('VGR_NUMBER_IID', (int) $grId)->first();

        return Pdf::loadView('modules.FACTWM.FACTWM02.FACTWMF007.partials._print-preview', compact('verifyPo'))
            ->setPaper('A4', 'portrait')
            ->stream('billing_statement.pdf');
        // return view('modules.FACTWM.FACTWM02.FACTWMF007.partials._print-preview', compact('verifyPo'));
    }

    public function submitFinalPreview(Request $request, VerifyPo $verifyPo)
    {
        Gate::authorize('finalPreview', $verifyPo);
        try {
            $result = DB::transaction(function () use ($request, $verifyPo) {
                $filesToDelete = [];

                $folderPath = 'po/' . $verifyPo->VBILLING_STATEMENT . '/'
                    . $verifyPo->VSUPPLIER_CODE . '_' . $verifyPo->VINVOICE_NUMBER;

                $supplier = Supplier::where('VSUPPLIER_CODE', $verifyPo->VSUPPLIER_CODE)->first();

                if (! $supplier) {
                    throw new \Exception('Data supplier not found', 422);
                }

                $fileInvoice = null;
                $fileTax = null;
                $fileRekapJasa = null;

                // send ke API IFS
                $config_verify_api = Config::where('VVARIABLE', 'verify_api')->first();
                $verify_api = filter_var($config_verify_api->VVALUE ?? false, FILTER_VALIDATE_BOOLEAN);

                $status = 'WAITING';
                $date_approved = null;
                // $action = 'updated';
                $message = null;
                $billing_statement = null;

                if ($verify_api) {
                    // $payload = $this->mapDataPayload($verifyPo);
                    $payload = json_decode($request->payload, true);
                    $postSI = $this->invoiceService->sendInvoice($payload);

                    if (! $postSI['success']) {
                        $message = data_get($postSI, 'message.message')
                            ?? data_get($postSI, 'message')
                            ?? data_get($postSI, 'errors.0.message')
                            ?? 'Submit SI Failed';

                        throw new \Exception(
                            json_encode([
                                'message' => $message,
                                'payload' => $postSI,
                            ]),
                            (int) ($postSI['status'] ?? 500)
                        );
                        $status = 'FAILED';
                    }

                    $status = $postSI['status'] ?? 'WAITING';
                    $date_approved = now();
                    $data = $postSI['data'];

                    $message = $data['message'] ?? null;
                    $status = $data['status'] ?? 'WAITING';
                    $billing_statement = $data['Billing_Statement_No'] ?? null;
                } else {
                    $status = 'WAITING';
                    $message = 'Data sedang proses, API berhasil dipanggil';
                    $date_approved = now();
                    $billing_statement = $verifyPo->VBILLING_STATEMENT;
                }

                // upload invoice
                // upload invoice
                if (
                    ! empty($verifyPo->VINVOICE_FILE)
                    && Storage::disk('public')->exists($verifyPo->VINVOICE_FILE)
                ) {

                    $fileInvoice = $this->dmsService->uploadFilePO(
                        $verifyPo->VINVOICE_FILE,
                        'file_invoice',
                        $supplier,
                        $verifyPo->VBILLING_STATEMENT
                    );

                    $filesToDelete[] = $verifyPo->VINVOICE_FILE;
                }

                // upload tax invoice
                if (
                    ! empty($verifyPo->VTAX_INVOICE_FILE)
                    && Storage::disk('public')->exists($verifyPo->VTAX_INVOICE_FILE)
                ) {

                    $fileTax = $this->dmsService->uploadFilePO(
                        $verifyPo->VTAX_INVOICE_FILE,
                        'file_faktur_pajak',
                        $supplier,
                        $verifyPo->VBILLING_STATEMENT
                    );

                    $filesToDelete[] = $verifyPo->VTAX_INVOICE_FILE;
                }

                // upload rekap jasa
                if (
                    ! empty($verifyPo->VREKAP_JASA_FILE)
                    && Storage::disk('public')->exists($verifyPo->VREKAP_JASA_FILE)
                ) {
                    $fileRekapJasa = $this->dmsService->uploadFilePO(
                        $verifyPo->VREKAP_JASA_FILE,
                        'file_rekap_jasa',
                        $supplier,
                        $verifyPo->VBILLING_STATEMENT
                    );

                    $filesToDelete[] = $verifyPo->VREKAP_JASA_FILE;
                }

                // other files
                $folder = 'others/' . $folderPath;
                $otherFiles = Storage::disk('public')->files($folder);
                $logOtherFiles = FACTWM_LOGVERIFY_PO_OTHER_FILES::where('TRHVERIFY_PO_IID', $verifyPo->IID)->get();

                foreach ($logOtherFiles ?? [] as $key => $file) {
                    if (Storage::disk('public')->exists($file->VPATH)) {
                        $fileOther = $this->dmsService->uploadFilePO(
                            $file->VPATH,
                            'other_document',
                            $supplier,
                            $verifyPo->VBILLING_STATEMENT,
                            $file->VNAME
                        );

                        $filesToDelete[] = $file->VPATH;

                        FACTWM_LOGVERIFY_PO_OTHER_FILES::where('IID', $file->IID)->update([
                            'VPATH' => $fileOther,
                        ]);
                    }
                }

                // update GR
                if (! empty($verifyPo->VGR_NUMBER_IID)) {
                    $this->updateSubmittedStatusForSelectedAndReturnGr($verifyPo->VGR_NUMBER_IID, $status);
                }

                // update verify PO
                VerifyPo::where('IID', $verifyPo->IID)->update([
                    'VSTATUS' => 'submit',
                    'DAPPROVED' => $date_approved,
                    'VSTATUS_INVOICE' => $status,
                    'VINVOICE_FILE' => $fileInvoice,
                    'VTAX_INVOICE_FILE' => $fileTax,
                    'VREKAP_JASA_FILE' => $fileRekapJasa,
                    'VNOTES' => $request->notes,
                ]);

                foreach ($filesToDelete as $file) {
                    Storage::disk('public')->delete($file);
                }

                return [
                    'message' => $message,
                    'status' => $status,
                    'billing_statement' => $billing_statement ?? null,
                ];
            });

            Cache::forget($this->getLastDraftCacheKey());

            return Response::success(message: $result['message'], data: [
                'action' => 'updated',
                'status' => $result['status'],
                'Billing_Statement_No' => $result['billing_statement'],
            ]);
        } catch (\Throwable $e) {

            $code = (int) $e->getCode();
            $status = ($code >= 400 && $code <= 599) ? $code : 500;

            $error = json_decode($e->getMessage(), true);

            return response()->json([
                'success' => false,
                'message' => $error['message'] ?? $e->getMessage(),
                // 'payload' => $error['payload'] ?? null
            ], $status);
        }
    }

    public function removeGR(Request $request)
    {
        $cacheKey = 'selected_gr_verify_po_' . Auth::user()->IID;
        $grToRemove = $request->gr_number;

        $grList = Cache::get($cacheKey, '');

        $grList = explode(',', $grList);
        $grList = array_values(array_filter($grList, function ($gr) use ($grToRemove) {
            return ! in_array($gr, $grToRemove, true);
        }));

        Cache::put($cacheKey, implode(',', $grList), now()->endOfDay());

        $query = GRNote::query()->with('details');

        if ($grList) {

            $count = $query->whereIn('VGR_NUMBER', $grList)->count();
        } else {
            $count = 0;
        }

        return Response::success(data: $count);
    }

    public function viewTable(Request $request)
    {
        $query = $this->buildVerifyPoViewTableQuery();

        $config_ppn = Config::where('VVARIABLE', 'ppn')->value('VVALUE') ?? 12;
        $config_rumus_dpp = Config::where('VVARIABLE', 'rumus_dpp')->value('VVALUE') ?? '11/12';

        [$num, $den] = array_map('floatval', explode('/', $config_rumus_dpp));
        $rumus_dpp = ($den == 0) ? 0 : ($num / $den);

        $dataTable = datatables($query)
            ->filter(function ($query) use ($request) {
                $this->applyVerifyPoViewTableFilters($query, $request);
            }, false)
            ->filterColumn('DGR', function ($query, $keyword) {
                $keyword = trim((string) $keyword);

                if ($keyword === '') {
                    return;
                }

                if (str_contains($keyword, ' to ')) {
                    [$startDate, $endDate] = array_map('trim', explode(' to ', $keyword, 2));

                    if ($startDate !== '' && $endDate !== '') {
                        $query->whereBetween('DGR', [
                            Carbon::parse($startDate)->startOfDay(),
                            Carbon::parse($endDate)->endOfDay(),
                        ]);
                    }

                    return;
                }

                $query->whereDate('DGR', Carbon::parse($keyword)->toDateString());
            })
            ->orderColumn('VREF_TYPE', function ($query, $order) {
                $query->orderBy('ref_group_number', $order)
                    ->orderBy('ref_type_sort', $order)
                    ->orderBy('VGR_NUMBER', $order);
            })
            ->addColumn('PAIR_VGR_NUMBER', function ($gr) {
                if ($gr->VREF_TYPE === 'RETURN') {
                    return $gr->VRETURN_REF;
                }

                if ($gr->VREF_TYPE === 'RECEIPT') {
                    return $gr->returnGr?->VGR_NUMBER;
                }

                return null;
            })
            ->addColumn('PAIR_VGR_NUMBERS', function ($gr) {
                if ($gr->VREF_TYPE === 'RETURN') {
                    return collect([$gr->VRETURN_REF])
                        ->filter()
                        ->values()
                        ->toArray();
                }

                if ($gr->VREF_TYPE === 'RECEIPT') {
                    return $gr->returnGrs
                        ->pluck('VGR_NUMBER')
                        ->filter()
                        ->values()
                        ->toArray();
                }

                return [];
            })

            ->addColumn('payload', function ($gr) use ($config_ppn, $rumus_dpp) {
                $verifyPo = VerifyPo::whereJsonContains('VGR_NUMBER_IID', (int) $gr->IID)->first();

                if (! $verifyPo) {
                    return [];
                }

                $supplier = Supplier::where('VSUPPLIER_CODE', $verifyPo->VSUPPLIER_CODE)->first();
                if (! $supplier) {
                    return [];
                }

                $taxCode = $supplier->BPKP ? 'V11' : 'V0';

                return $this->buildVerifyPoPayload($verifyPo, $taxCode, $config_ppn, $rumus_dpp);
            });

        return $dataTable->toJson();
    }

    public function selectableGrns(Request $request)
    {
        $query = $this->buildVerifyPoViewTableQuery();
        $this->applyVerifyPoViewTableFilters($query, $request);

        $rows = $query
            ->where('VREF_TYPE', 'RECEIPT')
            ->where('VSTATUS_SUBMITTED', 'PENDING')
            ->get()
            ->map(function ($gr) {
                return [
                    'VGR_NUMBER' => $gr->VGR_NUMBER,
                    'PAIR_VGR_NUMBERS' => $gr->returnGrs
                        ->pluck('VGR_NUMBER')
                        ->filter()
                        ->values()
                        ->toArray(),
                    'AMOUNT' => (float) $gr->details->sum('VAMOUNT'),
                ];
            })
            ->values();

        return Response::success(data: $rows);
    }

    public function ocrTable(Request $request)
    {
        $cacheKey = 'selected_gr_verify_po_' . Auth::user()->IID;
        $query = GRNote::query()->with('details');
        $summaryQuery = GRNote::query()->with('details');
        $grList = Cache::get($cacheKey);

        if ($grList) {
            $grList = explode(',', $grList);

            // Memastikan GR Return nya tetap terbawa
            $additionalGrNumbers = GRNote::whereIn('VGR_NUMBER', $grList)
                ->where('VREF_TYPE', 'RECEIPT')
                ->with('returnGrs')
                ->get()
                ->flatMap(fn($gr) => $gr->returnGrs->pluck('VGR_NUMBER'))
                ->filter(fn($vrNumber) => $vrNumber && ! in_array($vrNumber, $grList))
                ->values()
                ->toArray();

            $grList = array_merge($grList, $additionalGrNumbers);

            if (count($additionalGrNumbers) > 0) {
                Cache::set($cacheKey, implode(',', $grList), now()->endOfDay());
            }

            $query->whereIn('VGR_NUMBER', $grList);
            $summaryQuery->whereIn('VGR_NUMBER', $grList);
        } else {
            $query->where('IID', null);
            $summaryQuery->where('IID', null);
        }

        $summaryRows = $summaryQuery->get();

        $allGrNumberIids = $summaryRows
            ->pluck('IID')
            ->filter()
            ->values()
            ->all();

        $receiptTotalAmount = (float) $summaryRows
            ->where('VREF_TYPE', 'RECEIPT')
            ->sum(fn($gr) => (float) $gr->details->sum('VAMOUNT'));

        $dataTable = datatables($query)
            ->with([
                'all_gr_number_iids' => $allGrNumberIids,
                'receipt_total_amount' => $receiptTotalAmount,
            ]);

        return $dataTable->toJson();
    }

    public function validateInvoice(Request $request)
    {
        $request->validate(
            [
                'params' => 'required',
                'invoice_number' => [
                    'required',
                    Rule::unique('FACTWM_TRHVERIFY_PO', 'VINVOICE_NUMBER')
                        ->where(
                            fn($q) => $q->where('VSTATUS_INVOICE', '!=', 'REJECTED')
                                ->where('VSTATUS', 'submit')
                        ),
                ],
                'invoice_file' => 'required|file|max:2048',
                'render_dpi' => 'nullable|integer|min:140',
            ],
            [
                'invoice_file.uploaded' => 'Invoice file must not exceed 2 MB.',
                'invoice_file.max' => 'Invoice file must not exceed 2 MB.',
            ]
        );

        $params = $this->mapParamsInvoice($request->params);
        $renderDpi = $this->resolveRenderDpi($request);
        $total = $this->extractInvoiceTotalFromParams($request->params);
        $requireMateraiOcr = $this->resolveRequireMateraiOcr($total);

        try {
            $ocrResult = $this->ocrService->validate($params, $request->invoice_file, $renderDpi);
            $valid = data_get($ocrResult, 'valid', []);
            $text = data_get($ocrResult, 'text');
            $materaiStatus = $this->resolveMateraiStatusFromValid($valid, $requireMateraiOcr);
            $this->putMateraiOcrCache([
                'require' => $requireMateraiOcr,
                'status' => $materaiStatus,
            ]);
        } catch (\Throwable $e) {
            if ($requireMateraiOcr === 'Y') {
                $this->putMateraiOcrCache([
                    'require' => $requireMateraiOcr,
                    'status' => 'ERROR',
                ]);
            } else {
                $this->clearMateraiOcrCache();
            }

            throw $e;
        }
        $this->putInvoiceLockCache([
            'invoice_number' => trim((string) $request->invoice_number),
            'invoice_date' => trim((string) data_get(explode(',', (string) $request->params), 1, '')),
        ]);

        $validUnchecked = $this->hasUncheckedValidationItems($valid);

        if ($validUnchecked) {
            // update cache unverified
            $this->updateSelectedGrCache();
        }

        $cacheKey = 'selected_gr_scan_ocrverify_po_unverified_' . Auth::user()->IID;

        $unverifyOCR = Cache::get($cacheKey, 0);

        return Response::success([
            'valid' => $valid,
            'text' => $text,
            'require_materai_ocr' => $requireMateraiOcr,
            'ocr_materai_status' => $materaiStatus,
            'unverifyOCR' => $unverifyOCR,
        ]);
    }

    public function validateTax(Request $request)
    {
        $request->validate(
            [
                'params' => 'required',
                'tax_invoice' => [
                    'required',
                    Rule::unique('FACTWM_TRHVERIFY_PO', 'VTAX_INVOICE_NUMBER')
                        ->where(
                            fn($q) => $q->where('VSTATUS_INVOICE', '!=', 'REJECTED')
                                ->where('VSTATUS', 'submit')
                        ),
                ],
                'tax_file' => 'required|file|max:2048',
                'render_dpi' => 'nullable|integer|min:140',
            ],
            [
                'tax_file.uploaded' => 'Tax file must not exceed 2 MB.',
                'tax_file.max' => 'Tax file must not exceed 2 MB.',
            ]
        );

        $params = $this->mapParamsTax($request->params);
        $renderDpi = $this->resolveRenderDpi($request);
        $ocrResult = $this->ocrService->validate($params, $request->tax_file, $renderDpi);
        $valid = data_get($ocrResult, 'valid', []);
        $text = data_get($ocrResult, 'text');

        // $validUnchecked = $this->hasUncheckedValidationItems($valid);

        // if ($validUnchecked) {
        //     // update cache unverified
        //     $this->updateSelectedGrCache();
        // }

        // $cacheKey = 'selected_gr_scan_ocrverify_po_unverified_' . Auth::user()->IID;

        // $unverifyOCR = Cache::get($cacheKey, 0);

        return Response::success([
            'valid' => $valid,
            'text' => $text,
            // 'unverifyOCR' => $unverifyOCR,
        ]);
    }

    public function validateRekapJasa(Request $request)
    {
        $request->validate(
            [
                'params' => 'required',
                'rekap_jasa_file' => 'required|file|max:2048',
                'render_dpi' => 'nullable|integer|min:140',
            ],
            [
                'rekap_jasa_file.uploaded' => 'Rekap Jasa file must not exceed 2 MB.',
                'rekap_jasa_file.max' => 'Rekap Jasa file must not exceed 2 MB.',
            ]
        );

        $params = $this->mapParamsRekapJasa($request->params);
        $renderDpi = $this->resolveRenderDpi($request);
        $ocrResult = $this->ocrService->validate($params, $request->rekap_jasa_file, $renderDpi);
        $valid = data_get($ocrResult, 'valid', []);
        $text = data_get($ocrResult, 'text');

        $validUnchecked = $this->hasUncheckedValidationItems($valid);

        if ($validUnchecked) {
            // update cache unverified
            $this->updateSelectedGrCache();
        }

        $cacheKey = 'selected_gr_scan_ocrverify_po_unverified_' . Auth::user()->IID;

        $unverifyOCR = Cache::get($cacheKey, 0);

        return Response::success([
            'valid' => $valid,
            'text' => $text,
            'unverifyOCR' => $unverifyOCR,
        ]);
    }

    public function getIdbmNpwpMatch()
    {
        $roles = count(Auth::user()->roles) > 0 ? Auth::user()->roles->pluck('VROLENAME')->toArray()[0] : 'Guest';

        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;

        $idViewers = $userSupplier->ISUPPLIER_ID;
        $supplier = Supplier::findOrFail($idViewers);
        // $supplier_code = $supplier->VSUPPLIER_CODE;
        $npwp = ! empty($supplier->VNPWP) ? $supplier->VNPWP : '';
        $pkp_supplier = $supplier->BPKP;
        $config_npwp_idbm_match = Config::where('VVARIABLE', 'npwp_idbm_match')->first();

        return Response::success([
            'npwp' => $npwp,
            'config_npwp_idbm_match' => $config_npwp_idbm_match->VVALUE ?? '',
            'pkp_supplier' => $pkp_supplier,
        ]);
    }

    public function resendSI(Request $request, $grId)
    {
        try {
            $grn = GRNote::find($grId);
            if (! $grn) {
                throw new \Exception(
                    'GRN Not Found',
                    422
                );
            }

            $verifyPo = VerifyPo::whereJsonContains('VGR_NUMBER_IID', (int) $grId)->first();
            if (! $verifyPo) {
                throw new \Exception(
                    'PO Not Found',
                    422
                );
            }

            $result = DB::transaction(function () use ($verifyPo, $request) {

                $config_verify_api = Config::where('VVARIABLE', 'verify_api')->first();
                $verify_api = filter_var($config_verify_api->VVALUE ?? false, FILTER_VALIDATE_BOOLEAN);

                $status = 'WAITING';
                $date_approved = null;

                if ($verify_api) {
                    // $payload = $this->mapDataPayload($verifyPo);
                    $payload = json_decode($request->payload, true);
                    $postSI = $this->invoiceService->sendInvoice($payload);
                    if (! $postSI['success']) {
                        $message = is_array($postSI['message'])
                            ? ($postSI['message']['message'] ?? 'Resend SI Failed')
                            : $postSI['message'];

                        throw new \Exception(
                            $message,
                            (int) ($postSI['status'] ?? 500)
                        );
                    }

                    // $status = 'WAITING';
                    $date_approved = now();
                    $data = $postSI['data'];

                    $message = $data['message'] ?? null;
                    $status = $data['status'] ?? 'WAITING';
                    $billing_statement = $data['Billing_Statement_No'] ?? null;
                } else {
                    $status = 'WAITING';
                    $message = 'Data sedang proses, API berhasil dipanggil';
                    $date_approved = now();
                    $billing_statement = $verifyPo->VBILLING_STATEMENT;
                }

                $this->updateSubmittedStatusForSelectedAndReturnGr($verifyPo->VGR_NUMBER_IID, $status);

                VerifyPo::where('IID', $verifyPo->IID)->update([
                    'DAPPROVED' => $date_approved,
                    'VSTATUS_INVOICE' => $status,
                ]);

                return [
                    'message' => $message,
                    'status' => $status,
                    'billing_statement' => $billing_statement ?? null,
                ];
            });

            return Response::success(message: $result['message'], data: [
                'action' => 'updated',
                'status' => $result['status'],
                'Billing_Statement_No' => $result['billing_statement'],
            ]);
        } catch (\Throwable $e) {
            $code = (int) $e->getCode();
            $status = ($code >= 400 && $code <= 599) ? $code : 500;

            return Response::error($e->getMessage(), $status);
        }
    }

    private function mapParamsInvoice($params)
    {
        $mapParams = explode(',', $params);
        $invoice = $mapParams[0] ?? null;
        $tgl_invoice = $mapParams[1] ?? null;
        $amount = (int) str_replace('.', '', $mapParams[2] ?? 0);
        $ppn = (int) str_replace('.', '', $mapParams[3] ?? 0);
        $npwp_idbm = $mapParams[4] ?? null;
        $pkp_supplier = $mapParams[5] ?? false;
        $dpp = (int) str_replace('.', '', $mapParams[6] ?? 0);
        $total = (int) str_replace('.', '', $mapParams[7] ?? 0);
        $inputPPh = $mapParams[8] ?? null;
        if (! empty($inputPPh)) {
            $pph = (int) str_replace('.', '', $inputPPh ?? 0);
        }
        $config_ocr_astemo_value = Config::where('VVARIABLE', 'ocr_astemo_value')->first();
        $ocr_astemo_value = $config_ocr_astemo_value->VVALUE ?? 'ASTEMO BEKASI';
        $config_toleransi_ppn = Config::where('VVARIABLE', 'toleransi_ppn')->first();
        $tolerasi_ppn_value = (int) $config_toleransi_ppn->VVALUE ?? 0;
        $ppn_plus_toleransi = $ppn + $tolerasi_ppn_value;
        $ppn_minus_toleransi = $ppn - $tolerasi_ppn_value;
        $config_minimum_validasi_materai = Config::where('VVARIABLE', 'minimum_validasi_materai')->first();
        $minimum_validasi_materai = (int) $config_minimum_validasi_materai->VVALUE ?? 0;

        $listFormatTglInvoice = Helpers::listDateFormat($tgl_invoice);
        $keywords = [
            [
                'key' => 'invoice_number',
                'value' => $invoice,
                'checked' => false,
            ],
            // [
            //     'key' => 'dpp-nilai-lain',
            //     'id'  => 'dpp-nilai-lain-status',
            //     'value' => number_format((float) $dpp, 0, ',', '.'),
            //     'checked' => false,
            // ],
            // [
            //     'key' => 'dpp-nilai-lain',
            //     'id'  => 'dpp-nilai-lain-status',
            //     'value' => number_format((float) $dpp, 0, '.', ','),
            //     'checked' => false,
            // ],
            // [
            //     'key' => 'dpp-nilai-lain',
            //     'id'  => 'dpp-nilai-lain-status',
            //     'value' => strval($dpp),
            //     'checked' => false,
            // ],
        ];

        // tanggal invoice → banyak format
        foreach ($listFormatTglInvoice as $date) {
            $keywords[] = [
                'key' => 'invoice_date',
                'value' => $date,
                'checked' => false,
            ];
        }

        // amount tetap seperti sebelumnya
        $keywords = array_merge($keywords, [
            [
                'key' => 'net-amount',
                'id' => 'net-amount-status',
                'value' => number_format((float) $amount, 0, ',', '.'),
                'checked' => false,
            ],
            [
                'key' => 'net-amount',
                'id' => 'net-amount-status',
                'value' => number_format((float) $amount, 0, '.', ','),
                'checked' => false,
            ],
            [
                'key' => 'net-amount',
                'id' => 'net-amount-status',
                'value' => strval($amount),
                'checked' => false,
            ],
            // [
            //     'key' => 'astemo',
            //     'value' => $ocr_astemo_value,
            //     'checked' => false,
            // ],
        ]);

        if ($pkp_supplier) {
            $keywords = array_merge($keywords, [
                [
                    'key' => 'ppn',
                    'id' => 'ppn-status',
                    'value' => number_format((float) $ppn, 0, ',', '.'),
                    'checked' => false,
                ],
                [
                    'key' => 'ppn',
                    'id' => 'ppn-status',
                    'value' => number_format((float) $ppn, 0, '.', ','),
                    'checked' => false,
                ],
                [
                    'key' => 'ppn',
                    'id' => 'ppn-status',
                    'value' => strval($ppn),
                    'checked' => false,
                ],
                // [
                //     'key' => 'ppn',
                //     'id'  => 'ppn-status',
                //     'value' => number_format((float) $ppn_plus_toleransi, 0, ',', '.'),
                //     'checked' => false,
                // ],
                // [
                //     'key' => 'ppn',
                //     'id'  => 'ppn-status',
                //     'value' => number_format((float) $ppn_plus_toleransi, 0, '.', ','),
                //     'checked' => false,
                // ],
                // [
                //     'key' => 'ppn',
                //     'id'  => 'ppn-status',
                //     'value' => strval($ppn_plus_toleransi),
                //     'checked' => false,
                // ],
                // [
                //     'key' => 'ppn',
                //     'id'  => 'ppn-status',
                //     'value' => number_format((float) $ppn_minus_toleransi, 0, ',', '.'),
                //     'checked' => false,
                // ],
                // [
                //     'key' => 'ppn',
                //     'id'  => 'ppn-status',
                //     'value' => number_format((float) $ppn_minus_toleransi, 0, '.', ','),
                //     'checked' => false,
                // ],
                // [
                //     'key' => 'ppn',
                //     'id'  => 'ppn-status',
                //     'value' => strval($ppn_minus_toleransi),
                //     'checked' => false,
                // ],
            ]);
        }

        if (! empty($inputPPh)) {
            $keywords = array_merge($keywords, [
                [
                    'key' => 'nilai',
                    'id' => 'nilai-status',
                    'value' => number_format((float) $pph, 0, ',', '.'),
                    'checked' => false,
                ],
                [
                    'key' => 'nilai',
                    'id' => 'nilai-status',
                    'value' => number_format((float) $pph, 0, '.', ','),
                    'checked' => false,
                ],
                [
                    'key' => 'nilai',
                    'id' => 'nilai-status',
                    'value' => strval($pph),
                    'checked' => false,
                ],
            ]);
        }

        if ($total >= $minimum_validasi_materai) {
            $keywords = array_merge($keywords, [
                [
                    'key' => 'materai',
                    // 'id' => 'materai',
                    'value' => 'METERAI',
                    'checked' => false,
                ],
            ]);
        }

        return $keywords;
    }

    private function updateSubmittedStatusForSelectedAndReturnGr(array $iids, string $status): void
    {
        $normalizedIids = collect($iids)
            ->filter(fn($id) => ! is_null($id) && $id !== '')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        if (empty($normalizedIids)) {
            return;
        }

        GRNote::whereIn('IID', $normalizedIids)
            ->update(['VSTATUS_SUBMITTED' => $status]);

        $receiptNumbers = GRNote::query()
            ->whereIn('IID', $normalizedIids)
            ->where('VREF_TYPE', 'RECEIPT')
            ->pluck('VGR_NUMBER')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($receiptNumbers)) {
            return;
        }

        GRNote::query()
            ->where('VREF_TYPE', 'RETURN')
            ->whereIn('VRETURN_REF', $receiptNumbers)
            ->update(['VSTATUS_SUBMITTED' => $status]);
    }

    private function mapParamsRekapJasa($params)
    {
        $mapParams = explode(',', $params);
        $nilai = (int) str_replace('.', '', $mapParams[0] ?? 0);

        $keywords = [
            [
                'key' => 'nilai',
                'id' => 'nilai-status',
                'value' => number_format((float) $nilai, 0, ',', '.'),
                'checked' => false,
            ],
            [
                'key' => 'nilai',
                'id' => 'nilai-status',
                'value' => number_format((float) $nilai, 0, '.', ','),
                'checked' => false,
            ],
            [
                'key' => 'nilai',
                'id' => 'nilai-status',
                'value' => strval($nilai),
                'checked' => false,
            ],
            // [
            //     'key' => 'astemo',
            //     'value' => 'ASTEMO BEKASI MANUFACTURING',
            //     'checked' => false,
            // ],
        ];

        return $keywords;
    }

    private function mapParamsTax($params)
    {
        $mapParams = explode(',', $params);
        $tax = $mapParams[0] ?? null;
        $tgl_tax = $mapParams[1] ?? null;
        $amount = (int) str_replace('.', '', $mapParams[2] ?? 0);
        $ppn = (int) str_replace('.', '', $mapParams[3] ?? 0);
        $npwp_supplier = $mapParams[4] ?? null;
        $npwp_idbm = $mapParams[5] ?? null;
        $pkp_supplier = $mapParams[6] ?? false;
        $dpp = (int) str_replace('.', '', $mapParams[7] ?? 0);
        $config_ocr_astemo_value = Config::where('VVARIABLE', 'ocr_astemo_value')->first();
        $ocr_astemo_value = $config_ocr_astemo_value->VVALUE ?? 'ASTEMO BEKASI';
        $config_toleransi_ppn = Config::where('VVARIABLE', 'toleransi_ppn')->first();
        $tolerasi_ppn_value = (int) $config_toleransi_ppn->VVALUE ?? 0;
        $ppn_plus_toleransi = $ppn + $tolerasi_ppn_value;
        $ppn_minus_toleransi = $ppn - $tolerasi_ppn_value;

        $listFormatTglInvoice = Helpers::listDateFormat($tgl_tax);

        $keywords = [
            [
                'key' => 'tax_invoice_number',
                'value' => $tax,
                'checked' => false,
            ],
            [
                'key' => 'dpp-nilai-lain',
                'id' => 'dpp-nilai-lain-status',
                'value' => number_format((float) $dpp, 0, ',', '.'),
                'checked' => false,
            ],
            [
                'key' => 'dpp-nilai-lain',
                'id' => 'dpp-nilai-lain-status',
                'value' => number_format((float) $dpp, 0, '.', ','),
                'checked' => false,
            ],
            [
                'key' => 'dpp-nilai-lain',
                'id' => 'dpp-nilai-lain-status',
                'value' => strval($dpp),
                'checked' => false,
            ],
        ];

        // tanggal invoice → banyak format
        foreach ($listFormatTglInvoice as $date) {
            $keywords[] = [
                'key' => 'tax_invoice_date',
                'value' => $date,
                'checked' => false,
            ];
        }

        // amount tetap seperti sebelumnya
        $keywords = array_merge($keywords, [
            // [
            //     'key' => 'ppn',
            //     'value' => number_format((float) $ppn, 0, ',', '.'),
            //     'checked' => false,
            // ],
            // [
            //     'key' => 'ppn',
            //     'value' => number_format((float) $ppn, 0, '.', ','),
            //     'checked' => false,
            // ],
            // [
            //     'key' => 'ppn',
            //     'value' => strval($ppn),
            //     'checked' => false,
            // ],
            [
                'key' => 'npwp_supplier',
                'value' => $npwp_supplier,
                'checked' => false,
            ],
            [
                'key' => 'npwp_idbm',
                'id' => 'npwp-idbm-status',
                'value' => $npwp_idbm,
                'checked' => false,
            ],
            // [
            //     'key' => 'astemo',
            //     'value' => $ocr_astemo_value,
            //     'checked' => false,
            // ],
        ]);

        if ($pkp_supplier) {
            $keywords = array_merge($keywords, [
                [
                    'key' => 'ppn',
                    'id' => 'ppn-status',
                    'value' => number_format((float) $ppn, 0, ',', '.'),
                    'checked' => false,
                ],
                [
                    'key' => 'ppn',
                    'id' => 'ppn-status',
                    'value' => number_format((float) $ppn, 0, '.', ','),
                    'checked' => false,
                ],
                [
                    'key' => 'ppn',
                    'id' => 'ppn-status',
                    'value' => strval($ppn),
                    'checked' => false,
                ],
                // [
                //     'key' => 'ppn',
                //     'id'  => 'ppn-status',
                //     'value' => number_format((float) $ppn_plus_toleransi, 0, ',', '.'),
                //     'checked' => false,
                // ],
                // [
                //     'key' => 'ppn',
                //     'id'  => 'ppn-status',
                //     'value' => number_format((float) $ppn_plus_toleransi, 0, '.', ','),
                //     'checked' => false,
                // ],
                // [
                //     'key' => 'ppn',
                //     'id'  => 'ppn-status',
                //     'value' => strval($ppn_plus_toleransi),
                //     'checked' => false,
                // ],
                // [
                //     'key' => 'ppn',
                //     'id'  => 'ppn-status',
                //     'value' => number_format((float) $ppn_minus_toleransi, 0, ',', '.'),
                //     'checked' => false,
                // ],
                // [
                //     'key' => 'ppn',
                //     'id'  => 'ppn-status',
                //     'value' => number_format((float) $ppn_minus_toleransi, 0, '.', ','),
                //     'checked' => false,
                // ],
                // [
                //     'key' => 'ppn',
                //     'id'  => 'ppn-status',
                //     'value' => strval($ppn_minus_toleransi),
                //     'checked' => false,
                // ],
            ]);
        }

        return $keywords;
    }

    private function generateBillingNumber(): string
    {
        $prefix = 'PO' . date('Y') . date('m'); // contoh: PO202512

        $lastData = VerifyPo::where('VBILLING_STATEMENT', 'like', $prefix . '%')
            ->orderBy('IID', 'desc')
            ->first();

        if ($lastData) {
            $lastNumber = substr($lastData->VBILLING_STATEMENT, -4);
            $nextNumber = str_pad(((int) $lastNumber + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix . $nextNumber;
    }

    private function buildVerifyPoViewTableQuery()
    {
        $query = GRNote::query()
            ->select('FACTWM_TRHGR_NOTES.*')
            ->selectRaw("
                COALESCE(NULLIF(\"VRETURN_REF\", ''), \"VGR_NUMBER\") as ref_group_number
            ")
            ->selectRaw("
                CASE
                    WHEN \"VREF_TYPE\" = 'RECEIPT' THEN 0
                    WHEN \"VREF_TYPE\" = 'RETURN' THEN 1
                    ELSE 2
                END as ref_type_sort
            ")
            ->where('VVENDOR_CODE', Auth::user()->supplierUser->VSUPPLIER_CODE)
            ->whereIn('VSTATUS', ['APPROVED', 'CLOSED'])
            ->with(['details', 'returnGr', 'returnGrs']);

        $monthRange = Cache::get('month_verify_po_' . Auth::user()->IID);

        if ($monthRange) {
            $startDate = Carbon::createFromFormat('Y-m', $monthRange)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $monthRange)->endOfMonth();

            $query->whereBetween('DGR', [$startDate, $endDate]);
        } else {
            $query->where('IID', null);
        }

        return $query;
    }

    private function applyVerifyPoViewTableFilters($query, Request $request): void
    {
        $globalSearch = trim((string) data_get($request->input('search'), 'value', ''));

        if ($globalSearch !== '') {
            $query->where(function ($innerQuery) use ($globalSearch) {
                $likeValue = '%' . $globalSearch . '%';

                $innerQuery->where('VSTATUS_SUBMITTED', 'like', $likeValue)
                    ->orWhere('VREF_TYPE', 'like', $likeValue)
                    ->orWhere('VRETURN_REF', 'like', $likeValue)
                    ->orWhere('VGR_NUMBER', 'like', $likeValue)
                    ->orWhere('VPO_NUMBER', 'like', $likeValue)
                    ->orWhere('VDELIVERY_NUMBER', 'like', $likeValue)
                    ->orWhereRaw('TO_CHAR("DGR", \'YYYY-MM-DD\') like ?', [$likeValue]);
            });
        }

        foreach ((array) $request->input('columns', []) as $column) {
            $columnName = $column['name'] ?? null;
            $columnSearch = trim((string) data_get($column, 'search.value', ''));

            if ($columnSearch === '' || empty($columnName)) {
                continue;
            }

            if ($columnName === 'DGR') {
                if (str_contains($columnSearch, ' to ')) {
                    [$startDate, $endDate] = array_map('trim', explode(' to ', $columnSearch, 2));

                    if ($startDate !== '' && $endDate !== '') {
                        $query->whereBetween('DGR', [
                            Carbon::parse($startDate)->startOfDay(),
                            Carbon::parse($endDate)->endOfDay(),
                        ]);
                    }
                } else {
                    $query->whereDate('DGR', $columnSearch);
                }

                continue;
            }

            if (in_array($columnName, ['VSTATUS_SUBMITTED', 'VREF_TYPE', 'VRETURN_REF', 'VGR_NUMBER', 'VPO_NUMBER', 'VDELIVERY_NUMBER'], true)) {
                $query->where($columnName, 'like', '%' . $columnSearch . '%');
            }
        }
    }

    private function updateSelectedGrCache()
    {
        $userId = Auth::user()->IID;
        $cacheKey = 'selected_gr_scan_ocrverify_po_unverified_' . $userId;

        $lastUnverified = Cache::get($cacheKey, 0);

        $payload = $lastUnverified + 1;
        $expiresAt = now()->endOfDay();

        Cache::put($cacheKey, $payload, $expiresAt);

        return true;
    }

    private function hasUncheckedValidationItems(array $valid): bool
    {
        return collect($valid)->contains(function ($item) {
            $key = strtolower((string) data_get($item, 'key', ''));

            return data_get($item, 'checked') === false
                && ! in_array($key, ['nilai', 'materai'], true);
        });
    }

    private function extractInvoiceTotalFromParams(string $params): int
    {
        $mapParams = explode(',', $params);

        return (int) str_replace('.', '', $mapParams[7] ?? 0);
    }

    private function getMinimumValidasiMaterai(): int
    {
        $config = Config::where('VVARIABLE', 'minimum_validasi_materai')->first();

        return (int) ($config->VVALUE ?? 0);
    }

    private function resolveRequireMateraiOcr(int $total): string
    {
        return $total >= $this->getMinimumValidasiMaterai() ? 'Y' : 'N';
    }

    private function resolveMateraiStatusFromValid(array $valid, string $requireMateraiOcr): ?string
    {
        if ($requireMateraiOcr !== 'Y') {
            return null;
        }

        $materaiItem = collect($valid)->first(function ($item) {
            return strtolower((string) data_get($item, 'key')) === 'materai';
        });

        return data_get($materaiItem, 'checked') ? 'VERIFIED' : 'INVALID';
    }

    private function getInvoiceLockCacheKey(): string
    {
        return 'verify_po_ocr_invoice_lock_' . Auth::user()->IID;
    }

    private function getMateraiOcrCacheKey(): string
    {
        return 'verify_po_materai_ocr_' . Auth::user()->IID;
    }

    private function getLastDraftCacheKey(): string
    {
        return 'verify_po_last_draft_' . Auth::user()->IID;
    }

    private function resolveRenderDpi(Request $request): int
    {
        return $request->integer('render_dpi') ?: 140;
    }

    private function putInvoiceLockCache(array $payload): void
    {
        Cache::put($this->getInvoiceLockCacheKey(), $payload, now()->endOfDay());
    }

    private function clearInvoiceLockCache(): void
    {
        Cache::forget($this->getInvoiceLockCacheKey());
    }

    private function putMateraiOcrCache(array $payload): void
    {
        Cache::put($this->getMateraiOcrCacheKey(), $payload, now()->endOfDay());
    }

    private function getMateraiOcrCache(): array
    {
        $payload = Cache::get($this->getMateraiOcrCacheKey(), []);

        return is_array($payload) ? $payload : [];
    }

    private function clearMateraiOcrCache(): void
    {
        Cache::forget($this->getMateraiOcrCacheKey());
    }

    private function deleteDraftVerifyPo(VerifyPo $draft): void
    {
        $otherFiles = FACTWM_LOGVERIFY_PO_OTHER_FILES::where('TRHVERIFY_PO_IID', $draft->IID)->get();
        foreach ($otherFiles as $file) {
            if (! empty($file->VPATH) && Storage::disk('public')->exists($file->VPATH)) {
                Storage::disk('public')->delete($file->VPATH);
            }
        }

        FACTWM_LOGVERIFY_PO_OTHER_FILES::where('TRHVERIFY_PO_IID', $draft->IID)->delete();
        VerifyPoDetail::where('TRHVERIFY_PO_IID', $draft->IID)->delete();

        $files = [
            $draft->VINVOICE_FILE,
            $draft->VTAX_INVOICE_FILE,
            $draft->VREKAP_JASA_FILE,
        ];

        foreach ($files as $file) {
            if (! empty($file) && Storage::disk('public')->exists($file)) {
                Storage::disk('public')->delete($file);
            }
        }

        $draft->delete();
    }

    private function assertInvoiceLock(array $validated): void
    {
        $locked = Cache::get($this->getInvoiceLockCacheKey());

        if (! is_array($locked) || empty($locked['invoice_number']) || empty($locked['invoice_date'])) {
            throw ValidationException::withMessages([
                'invoice_number' => ['Please upload invoice and run OCR validation before submit.'],
            ]);
        }

        $invoiceNumberMatch = (string) $validated['invoice_number'] === (string) $locked['invoice_number'];
        $invoiceDateMatch = (string) $validated['invoice_date'] === (string) $locked['invoice_date'];

        if (! $invoiceNumberMatch || ! $invoiceDateMatch) {
            throw ValidationException::withMessages([
                'invoice_number' => ['Invoice number/date changed after OCR. Please re-upload invoice and validate OCR again.'],
            ]);
        }
    }

    private function mapDataPayload($verifyPo)
    {
        $supplier = Supplier::where('VSUPPLIER_CODE', $verifyPo->VSUPPLIER_CODE)->first();
        $config_ppn = Config::where('VVARIABLE', 'ppn')->first();
        $config_rumus_dpp = Config::where('VVARIABLE', 'rumus_dpp')->first();
        if (! $supplier) {
            throw new Exception('Data supplier not found');
        }

        $rumus = $config_rumus_dpp->VVALUE ?? '11/12';
        [$num, $den] = array_map('floatval', explode('/', $rumus));

        $rumus_dpp = ($den == 0) ? 0 : ($num / $den);
        $ppn = $config_ppn->VVALUE ?? 12;

        $taxCode = $supplier->BPKP ? 'V11' : 'V0';

        return $this->buildVerifyPoPayload($verifyPo, $taxCode, $ppn, $rumus_dpp);
    }

    private function buildVerifyPoPayload($verifyPo, string $taxCode, float|int $ppn, float $rumusDpp): array
    {
        $grNotes = GRNote::with('details')
            ->whereIn('IID', $verifyPo->VGR_NUMBER_IID)
            ->get();

        return $grNotes->map(function ($gr) use ($verifyPo, $taxCode, $ppn, $rumusDpp) {
            $sign = $gr->VREF_TYPE === 'RETURN' ? -1 : 1;
            $netAmount = $gr->details->sum('VAMOUNT');
            $taxAmount = round(($rumusDpp * floatval($netAmount ?? 0)) * ($ppn / 100), 2);
            $grossAmount = $netAmount + $taxAmount;

            return [
                'Billing_Stat_No' => $verifyPo->VBILLING_STATEMENT,
                'Supplier' => $verifyPo->VSUPPLIER_CODE,
                'Currency' => $gr->details[0]->VCURRENCY ?? null,
                'Order_No' => $gr->VPO_NUMBER ?? null,
                'Reference_No' => $gr->VGR_NUMBER ?? null,
                'Invoice_No' => $verifyPo->VINVOICE_NUMBER,
                'Invoice_Date' => $verifyPo->DINVOICE_DATE->format('Y-m-d') ?? null,
                'TaxCode' => $taxCode,
                'NetAmount' => (string) (($netAmount ?? 0) * $sign),
                'TaxAmount' => (string) (($taxAmount ?? 0) * $sign),
                'GrossAmount' => (string) (($grossAmount ?? 0) * $sign),
            ];
        })->values()->toArray();
    }

    public function download($id, $type)
    {
        $verifyPo = VerifyPo::where('IID', $id)->first();
        if (! $verifyPo) {
            throw new Exception('Verify PO Not Found');
        }
        if ($type == 'invoice') {
            $file = $verifyPo->VINVOICE_FILE;
        } elseif ($type == 'rekap-jasa') {
            $file = $verifyPo->VREKAP_JASA_FILE;
        } else {
            $file = $verifyPo->VTAX_INVOICE_FILE;
        }

        if (Storage::disk('public')->exists($file)) {
            $fileContent = Storage::disk('public')->get($file);
        } else {
            $fileContent = Storage::disk('sftp')->get($file);
        }

        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . basename($file) . '"');
    }

    public function downloadOtherFile($id)
    {
        $logOtherFiles = FACTWM_LOGVERIFY_PO_OTHER_FILES::where('IID', $id)->first();
        if (! $logOtherFiles) {
            throw new Exception('Verify PO Not Found');
        }

        $file = $logOtherFiles->VPATH;

        if (Storage::disk('public')->exists($file)) {
            $fileContent = Storage::disk('public')->get($file);
        } else {
            $fileContent = Storage::disk('sftp')->get($file);
        }

        return response($fileContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . basename($file) . '"');
    }
}
