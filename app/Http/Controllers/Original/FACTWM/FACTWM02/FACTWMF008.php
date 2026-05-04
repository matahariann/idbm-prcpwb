<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM02;

use App\DataTables\Original\FACTWM02\FACTWMF008DataTable as VerifyNonPoDataTable;
use App\Exports\FACTWM\FACTWM02\FACTWMF008 as FACTWMF008Export;
use App\Helpers\Helpers;
use App\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\FACTWM\FACTWM02\VerifyNonPoRequest;
use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION as Config;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER as Supplier;
use App\Models\FACTWM02\FACTWM_LOGVERIFY_NON_PO_OTHER_FILES;
use App\Models\FACTWM02\FACTWM_TRDVERIFY_NON_PO_DETAILS as VerifyNonPoDetail;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_NON_PO as VerifyNonPo;
use App\Services\FACTWM\DMSService;
use App\Services\FACTWM\InvoiceService;
use App\Services\FACTWM\OCRService;
use App\Services\FACTWM\VerifyNonPoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class FACTWMF008 extends Controller
{
    public function __construct(private VerifyNonPoService $service, private OCRService $ocrService, private DMSService $dmsService, private InvoiceService $invoiceService) {}

    public function index(VerifyNonPoDataTable $dataTable)
    {
        return $dataTable->render('modules.FACTWM.FACTWM02.FACTWMF008.FACTWMF008');
    }

    public function create()
    {
        Gate::authorize('create', VerifyNonPo::class);

        $nonPo = new VerifyNonPo;

        $config_ppn = Config::where('VVARIABLE', 'ppn')->first();
        $config_rumus_dpp = Config::where('VVARIABLE', 'rumus_dpp')->first();
        $config_limit_eskalated = Config::where('VVARIABLE', 'limit_eskalated')->first();
        $config_verify_non_po_list_unit = Config::where('VVARIABLE', 'verify_non_po_list_unit')->first();
        $config_list_pph_pasal = Config::where('VVARIABLE', 'list_pph_pasal')->first();
        $npwp_supplier = $this->getIdbmNpwpMatch();
        $npwp_idbm_match = Config::where('VVARIABLE', 'npwp_idbm_match')->first();
        $npwp_idbm_match = $npwp_idbm_match->VVALUE ?? '-';
        $list_pph_pasal = $config_list_pph_pasal->VVALUE ?? null;

        $rumus = $config_rumus_dpp->VVALUE ?? '11/12';
        [$num, $den] = array_map('floatval', explode('/', $rumus));

        $rumus_dpp = ($den == 0) ? 0 : ($num / $den);
        $ppn = (int) $config_ppn->VVALUE ?? 12;

        $userId = Auth::user()->IID;
        $cacheKey = 'selected_gr_scan_ocrverify_non_po_unverified_' . $userId;
        $limit_eskalated = $config_limit_eskalated->VVALUE ?? 3;
        $verify_non_po_list_unit = $config_verify_non_po_list_unit->VVALUE ?? null;

        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;

        $idViewers = $userSupplier->ISUPPLIER_ID;
        $supplier = Supplier::findOrFail($idViewers);
        // $supplier_code = $supplier->VSUPPLIER_CODE;
        $pkp_supplier = $supplier->BPKP;
        $action = 'Create';
        $existingOtherFiles = collect();

        // hapus cache
        Cache::forget($cacheKey);

        return view('modules.FACTWM.FACTWM02.FACTWMF008.partials._verify-non-po-form', compact('nonPo', 'ppn', 'rumus_dpp', 'npwp_supplier', 'npwp_idbm_match', 'limit_eskalated', 'pkp_supplier', 'verify_non_po_list_unit', 'list_pph_pasal', 'action', 'existingOtherFiles'));
    }

    public function edit(VerifyNonPo $nonPo)
    {
        Gate::authorize('update', $nonPo);

        $config_ppn = Config::where('VVARIABLE', 'ppn')->first();
        $config_rumus_dpp = Config::where('VVARIABLE', 'rumus_dpp')->first();
        $config_limit_eskalated = Config::where('VVARIABLE', 'limit_eskalated')->first();
        $config_verify_non_po_list_unit = Config::where('VVARIABLE', 'verify_non_po_list_unit')->first();
        $config_list_pph_pasal = Config::where('VVARIABLE', 'list_pph_pasal')->first();
        $npwp_supplier = $this->getIdbmNpwpMatch();
        $npwp_idbm_match = Config::where('VVARIABLE', 'npwp_idbm_match')->first();
        $npwp_idbm_match = $npwp_idbm_match->VVALUE ?? '-';
        $list_pph_pasal = $config_list_pph_pasal->VVALUE ?? null;

        $rumus = $config_rumus_dpp->VVALUE ?? '11/12';
        [$num, $den] = array_map('floatval', explode('/', $rumus));

        $rumus_dpp = ($den == 0) ? 0 : ($num / $den);
        $ppn = (int) $config_ppn->VVALUE ?? 12;

        $userId = Auth::user()->IID;
        $cacheKey = 'selected_gr_scan_ocrverify_non_po_unverified_' . $userId;
        $limit_eskalated = $config_limit_eskalated->VVALUE ?? 3;
        $verify_non_po_list_unit = $config_verify_non_po_list_unit->VVALUE ?? null;

        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;

        $idViewers = $userSupplier->ISUPPLIER_ID;
        $supplier = Supplier::findOrFail($idViewers);
        // $supplier_code = $supplier->VSUPPLIER_CODE;
        $pkp_supplier = $supplier->BPKP;
        $action = 'Update';
        $existingOtherFiles = FACTWM_LOGVERIFY_NON_PO_OTHER_FILES::where('TRHVERIFY_NON_PO_IID', $nonPo->IID)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->IID,
                    'name' => $item->VNAME,
                    'path' => $item->VPATH,
                    'download_url' => route('verify-non-po.download-other-file', $item->IID),
                ];
            });

        return view('modules.FACTWM.FACTWM02.FACTWMF008.partials._verify-non-po-form', compact('nonPo', 'ppn', 'rumus_dpp', 'npwp_supplier', 'npwp_idbm_match', 'limit_eskalated', 'pkp_supplier', 'verify_non_po_list_unit', 'list_pph_pasal', 'action', 'existingOtherFiles'));
    }

    public function view(VerifyNonPo $nonPo)
    {
        Gate::authorize('view', $nonPo);

        $config_ppn = Config::where('VVARIABLE', 'ppn')->first();
        $config_rumus_dpp = Config::where('VVARIABLE', 'rumus_dpp')->first();
        $config_limit_eskalated = Config::where('VVARIABLE', 'limit_eskalated')->first();
        $config_verify_non_po_list_unit = Config::where('VVARIABLE', 'verify_non_po_list_unit')->first();
        $config_list_pph_pasal = Config::where('VVARIABLE', 'list_pph_pasal')->first();
        $npwp_supplier = $this->getIdbmNpwpMatch();
        $npwp_idbm_match = Config::where('VVARIABLE', 'npwp_idbm_match')->first();
        $npwp_idbm_match = $npwp_idbm_match->VVALUE ?? '-';

        $rumus = $config_rumus_dpp->VVALUE ?? '11/12';
        [$num, $den] = array_map('floatval', explode('/', $rumus));

        $rumus_dpp = ($den == 0) ? 0 : ($num / $den);
        $ppn = (int) $config_ppn->VVALUE ?? 12;

        $userId = Auth::user()->IID;
        $cacheKey = 'selected_gr_scan_ocrverify_non_po_unverified_' . $userId;
        $limit_eskalated = $config_limit_eskalated->VVALUE ?? 3;
        $verify_non_po_list_unit = $config_verify_non_po_list_unit->VVALUE ?? null;
        $list_pph_pasal = $config_list_pph_pasal->VVALUE ?? null;

        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;

        $idViewers = $userSupplier->ISUPPLIER_ID;
        $supplier = Supplier::findOrFail($idViewers);
        // $supplier_code = $supplier->VSUPPLIER_CODE;
        $pkp_supplier = $supplier->BPKP;
        $action = 'View';

        return view('modules.FACTWM.FACTWM02.FACTWMF008.partials._views-non-po-form', compact('nonPo', 'ppn', 'rumus_dpp', 'npwp_supplier', 'npwp_idbm_match', 'limit_eskalated', 'pkp_supplier', 'verify_non_po_list_unit', 'list_pph_pasal', 'action'));
    }

    /**
     * Display the specified resource with details.
     */
    public function show($id)
    {
        $grNote = VerifyNonPo::with('details')->findOrFail($id);

        if (! $grNote) {
            return Response::error(message: 'GR Note not found');
        }

        return Response::success(data: $grNote);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VerifyNonPoRequest $request)
    {
        $validated = $request->validated();
        $invoice = DB::transaction(function () use ($validated, $request) {
            // $grandTotal = collect($validated['details'])->sum(function ($item) {
            //     return ($item['qty'] ?? 0) * ($item['price'] ?? 0);
            // });
            $supplierCode = Auth::user()->supplierUser->VSUPPLIER_CODE;

            $billing_statement = $this->generateBillingNumber();

            $folderPath = 'non_po' . '/' . $billing_statement . '/' . $supplierCode . '_' . $validated['invoice_number'];

            $invoiceFile = null;
            $taxFile = null;

            // check old verify po
            $check_old_verify_non_po = VerifyNonPo::where('VINV_NO_SUPPLIER', $validated['invoice_number'])
                ->where('VSTATUS', 'draft')->first();
            if (! empty($check_old_verify_non_po)) {
                $check_old_detail_verify_po = VerifyNonPoDetail::where('TRHVERIFY_NON_PO_IID', $check_old_verify_non_po->IID)->get();
                // delete old file db
                $check_old_log = FACTWM_LOGVERIFY_NON_PO_OTHER_FILES::where('TRHVERIFY_NON_PO_IID', $check_old_verify_non_po->IID)->get();
                if (count($check_old_log) > 0) {
                    foreach ($check_old_log as $key => $log) {
                        if (Storage::disk('public')->exists($log->VPATH)) {
                            Storage::disk('public')->delete($log->VPATH);
                        }
                    }

                    FACTWM_LOGVERIFY_NON_PO_OTHER_FILES::where('TRHVERIFY_NON_PO_IID', $check_old_verify_non_po->IID)->delete();
                }

                if (count($check_old_detail_verify_po) > 0) {
                    VerifyNonPoDetail::where('TRHVERIFY_NON_PO_IID', $check_old_verify_non_po->IID)->delete();
                }

                $files = [
                    $check_old_verify_non_po->VPDF_INVOICE,
                    $check_old_verify_non_po->VPDF_TAX,
                    // $check_old_verify_non_po->VREKAP_JASA_FILE,
                ];

                foreach ($files as $file) {
                    if (! empty($file) && Storage::disk('public')->exists($file)) {
                        Storage::disk('public')->delete($file);
                    }
                }

                VerifyNonPo::where('IID', $check_old_verify_non_po->IID)->delete();
            }

            if ($request->has('invoice_pdf')) {

                $file = $request->file('invoice_pdf');

                $baseName = Str::slug($file->getClientOriginalName());

                $extension = $file->getClientOriginalExtension();

                $fileName = $baseName . '-' . time() . '.' . $extension;

                $invoiceFile = $file
                    ->storeAs('invoices/' . $folderPath, $fileName, 'public');
            }

            if ($request->has('tax_pdf')) {

                $file = $request->file('tax_pdf');

                $baseName = Str::slug($file->getClientOriginalName());

                $extension = $file->getClientOriginalExtension();

                $fileName = $baseName . '-' . time() . '.' . $extension;

                $taxFile = $file
                    ->storeAs('taxes/' . $folderPath, $fileName, 'public');
            }

            $cacheKey = 'selected_gr_scan_ocrverify_non_po_unverified_' . Auth::user()->IID;

            $config_limit_eskalated = Config::where('VVARIABLE', 'limit_eskalated')->first();

            $limit_eskalated = (int) $config_limit_eskalated->VVALUE ?? 3;

            $unverifyOCR = Cache::get($cacheKey, '');

            if ($request->status == 'ESCALATED') {
                $status_invoice = 'ESCALATED';
                $status = 'submit';
            } else {
                $status_invoice = 'WAITING';
                $status = 'draft';
            }

            $invoice = VerifyNonPo::create([
                'VBILLING_STATEMENT' => $billing_statement,
                'VUNIQUE_CODE' => random_int(100000, 999999),
                'VSUPPLIER_CODE' => Auth::user()->supplierUser->VSUPPLIER_CODE,
                'VINV_NO_SUPPLIER' => $validated['invoice_number'],
                'DINV_DATE' => $validated['invoice_date'],
                'VTAX_CODE' => $validated['tax_code'],
                'IDPP_PPH' => isset($validated['dpp_pph'])
                    ? (int) preg_replace('/[^\d\-]/u', '', $validated['dpp_pph'])
                    : null,
                'INET_AMOUNT' => str_replace('.', '', $validated['net_amount']),
                'VPPH' => isset($validated['pph'])
                    ? ($validated['pph'])
                    : null,
                'VDPP' => isset($validated['dpp_lain'])
                    ? (int) preg_replace('/[^\d\-]/u', '', $validated['dpp_lain'])
                    : null,
                'VPPN' => str_replace('.', '', $validated['ppn']),
                'VTAX_NUMBER' => isset($validated['tax_number_supplier'])
                    ? $validated['tax_number_supplier']
                    : null,
                'DTAX_DATE' => isset($validated['tax_date'])
                    ? $validated['tax_date']
                    : null,
                'ITOTAL' => isset($validated['grand_total'])
                    ? (int) preg_replace('/[^\d\-]/u', '', $validated['grand_total'])
                    : null,
                'VOBJECT' => isset($validated['object'])
                    ? $validated['object']
                    : null,
                'FTARRIF' => ! empty($validated['tarrif'])
                    ? (int) str_replace('.', '', $validated['tarrif']) : 0,
                'FVALUE' => isset($validated['nilai'])
                    ? (int) preg_replace('/[^\d\-]/u', '', $validated['nilai'])
                    : null,
                'VSTATUS' => $status,
                'VPDF_TAX' => $taxFile,
                'VPDF_INVOICE' => $invoiceFile,
                'VQRCODE' => '',
                'DSUBMITTED' => null,
                'DAPPROVED' => null,
                'DPLAN_PAY_DATE' => null,
                'VSTATUS_INVOICE' => $status_invoice,
            ]);

            // dd(json_decode($request->details, true));
            $details = $this->service->detailsMapping(json_decode($request->details, true), $invoice);
            VerifyNonPoDetail::insert($details);

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

                    FACTWM_LOGVERIFY_NON_PO_OTHER_FILES::create([
                        'TRHVERIFY_NON_PO_IID' => $invoice->IID,
                        'VNAME' => $file['name'],
                        'VPATH' => $path,
                    ]);
                }
            }

            return $invoice;
        });

        Cache::put($this->getLastDraftCacheKey(), $invoice->IID, now()->endOfDay());

        return Response::success(message: "Invoice $invoice->VINV_NO_SUPPLIER submitted", data: $invoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VerifyNonPoRequest $request, VerifyNonPo $nonPo)
    {
        $validated = $request->validated();

        $invoice = DB::transaction(function () use ($validated, $nonPo, $request) {

            $supplierCode = Auth::user()->supplierUser->VSUPPLIER_CODE;

            $billing_statement = $nonPo->VBILLING_STATEMENT;

            $folderPath = 'non_po' . '/' . $billing_statement . '/' . $supplierCode . '_' . $validated['invoice_number'];

            $invoiceFile = null;
            $taxFile = null;

            if ($request->has('invoice_pdf')) {

                $file = $request->file('invoice_pdf');

                $baseName = Str::slug($file->getClientOriginalName());

                $extension = $file->getClientOriginalExtension();

                $fileName = $baseName . '-' . time() . '.' . $extension;

                $invoiceFile = $file
                    ->storeAs('invoices/' . $folderPath, $fileName, 'public');
            } else {
                $invoiceFile = $nonPo->VPDF_INVOICE;
            }

            if ($request->has('tax_pdf')) {

                $file = $request->file('tax_pdf');

                $baseName = Str::slug($file->getClientOriginalName());

                $extension = $file->getClientOriginalExtension();

                $fileName = $baseName . '-' . time() . '.' . $extension;

                $taxFile = $file
                    ->storeAs('taxes/' . $folderPath, $fileName, 'public');
            } else {
                $taxFile = $nonPo->VPDF_TAX;
            }

            $cacheKey = 'selected_gr_scan_ocrverify_non_po_unverified_' . Auth::user()->IID;

            $config_limit_eskalated = Config::where('VVARIABLE', 'limit_eskalated')->first();

            $limit_eskalated = (int) $config_limit_eskalated->VVALUE ?? 3;

            $unverifyOCR = Cache::get($cacheKey, '');

            if ($unverifyOCR > $limit_eskalated) {
                $status_invoice = 'ESCALATED';
            } else {
                $status_invoice = 'WAITING';
            }

            $nonPo->update([
                // 'VUNIQUE_CODE' => random_int(100000, 999999),
                'VSUPPLIER_CODE' => Auth::user()->supplierUser->VSUPPLIER_CODE,
                'VINV_NO_SUPPLIER' => $validated['invoice_number'],
                'DINV_DATE' => $validated['invoice_date'],
                'VTAX_CODE' => $validated['tax_code'],
                'IDPP_PPH' => isset($validated['dpp_pph'])
                    ? (int) preg_replace('/[^\d\-]/u', '', $validated['dpp_pph'])
                    : null,
                'INET_AMOUNT' => str_replace('.', '', $validated['net_amount']),
                'VPPH' => isset($validated['pph'])
                    ? ($validated['pph'])
                    : null,
                'VDPP' => isset($validated['dpp_lain'])
                    ? (int) preg_replace('/[^\d\-]/u', '', $validated['dpp_lain'])
                    : null,
                'VPPN' => str_replace('.', '', $validated['ppn']),
                'VTAX_NUMBER' => isset($validated['tax_number_supplier'])
                    ? $validated['tax_number_supplier']
                    : null,
                'DTAX_DATE' => isset($validated['tax_date'])
                    ? $validated['tax_date']
                    : null,
                'ITOTAL' => isset($validated['grand_total'])
                    ? (int) preg_replace('/[^\d\-]/u', '', $validated['grand_total'])
                    : null,
                'VOBJECT' => isset($validated['object'])
                    ? $validated['object']
                    : null,
                'FTARRIF' => ! empty($validated['tarrif'])
                    ? (int) str_replace('.', '', $validated['tarrif']) : 0,
                'FVALUE' => isset($validated['nilai'])
                    ? (int) preg_replace('/[^\d\-]/u', '', $validated['nilai'])
                    : null,
                'VSTATUS' => 'draft',
                'VPDF_TAX' => $taxFile,
                'VPDF_INVOICE' => $invoiceFile,
                'VQRCODE' => '',
                'DSUBMITTED' => null,
                'DAPPROVED' => null,
                'DPLAN_PAY_DATE' => null,
                'VSTATUS_INVOICE' => $status_invoice,
            ]);

            $nonPo->details()->delete();

            $details = $this->service->detailsMapping(json_decode($request->details, true), $nonPo);

            VerifyNonPoDetail::insert($details);

            $deletedOtherFileIds = json_decode($request->input('deleted_other_file_ids', '[]'), true);
            if (is_array($deletedOtherFileIds) && count($deletedOtherFileIds) > 0) {
                $logsToDelete = FACTWM_LOGVERIFY_NON_PO_OTHER_FILES::where('TRHVERIFY_NON_PO_IID', $nonPo->IID)
                    ->whereIn('IID', $deletedOtherFileIds)
                    ->get();

                foreach ($logsToDelete as $log) {
                    if (! empty($log->VPATH) && Storage::disk('public')->exists($log->VPATH)) {
                        Storage::disk('public')->delete($log->VPATH);
                    }
                }

                FACTWM_LOGVERIFY_NON_PO_OTHER_FILES::where('TRHVERIFY_NON_PO_IID', $nonPo->IID)
                    ->whereIn('IID', $deletedOtherFileIds)
                    ->delete();
            }

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

                    FACTWM_LOGVERIFY_NON_PO_OTHER_FILES::create([
                        'TRHVERIFY_NON_PO_IID' => $nonPo->IID,
                        'VNAME' => $file['name'],
                        'VPATH' => $path,
                    ]);
                }
            }

            return $nonPo;
        });

        Cache::put($this->getLastDraftCacheKey(), $invoice->IID, now()->endOfDay());

        return Response::success(message: "Invoice $invoice->VINV_NO_SUPPLIER submitted", data: $invoice);
    }

    public function draftLast()
    {
        Gate::authorize('create', VerifyNonPo::class);

        $draftId = Cache::get($this->getLastDraftCacheKey());
        if (empty($draftId)) {
            return Response::success(data: null);
        }

        $supplierCode = Auth::user()->supplierUser->VSUPPLIER_CODE;
        $draft = VerifyNonPo::where('IID', $draftId)
            ->where('VSUPPLIER_CODE', $supplierCode)
            ->where('VSTATUS', 'draft')
            ->first();

        if (! $draft) {
            Cache::forget($this->getLastDraftCacheKey());

            return Response::success(data: null);
        }

        $otherFiles = FACTWM_LOGVERIFY_NON_PO_OTHER_FILES::where('TRHVERIFY_NON_PO_IID', $draft->IID)
            ->get()
            ->map(fn($item) => [
                'id' => $item->IID,
                'name' => $item->VNAME,
                'path' => $item->VPATH,
            ])->values();

        $details = VerifyNonPoDetail::where('TRHVERIFY_NON_PO_IID', $draft->IID)
            ->get()
            ->map(fn($item) => [
                'description' => $item->VDESCRIPTION,
                'qty' => (int) $item->IQTY,
                'unit' => $item->VUOM,
                'price' => (int) $item->IPRICE,
                'total' => (int) $item->ITOTAL,
            ])->values();

        return Response::success(data: [
            'id' => $draft->IID,
            'invoice_number' => $draft->VINV_NO_SUPPLIER,
            'invoice_date' => optional($draft->DINV_DATE)->format('Y-m-d'),
            'invoice_file_name' => ! empty($draft->VPDF_INVOICE) ? basename($draft->VPDF_INVOICE) : '',
            'tax_code' => $draft->VTAX_CODE,
            'tax_number_supplier' => $draft->VTAX_NUMBER,
            'tax_date' => $draft->DTAX_DATE ? \Carbon\Carbon::parse($draft->DTAX_DATE)->format('Y-m-d') : '',
            'tax_file_name' => ! empty($draft->VPDF_TAX) ? basename($draft->VPDF_TAX) : '',
            'pph' => $draft->VPPH,
            'object' => $draft->VOBJECT,
            'dpp_pph' => (int) ($draft->IDPP_PPH ?? 0),
            'tarrif' => (int) ($draft->FTARRIF ?? 0),
            'nilai' => (int) ($draft->FVALUE ?? 0),
            'net_amount' => (int) ($draft->INET_AMOUNT ?? 0),
            'dpp_lain' => (int) ($draft->VDPP ?? 0),
            'ppn' => (int) ($draft->VPPN ?? 0),
            'grand_total' => (int) ($draft->ITOTAL ?? 0),
            'details' => $details,
            'other_files' => $otherFiles,
        ]);
    }

    public function clearOcrState(Request $request)
    {
        Gate::authorize('create', VerifyNonPo::class);

        $userId = Auth::user()->IID;
        $unverifiedCacheKey = 'selected_gr_scan_ocrverify_non_po_unverified_' . $userId;

        Cache::forget($this->getLastDraftCacheKey());
        Cache::forget($unverifiedCacheKey);

        return Response::success(data: true);
    }

    public function finalPreview(VerifyNonPo $verifyPo)
    {
        Gate::authorize('finalPreview', $verifyPo);

        $folderPath = 'non_po' . '/' . $verifyPo->VBILLING_STATEMENT . '/' . $verifyPo->VSUPPLIER_CODE . '_' . $verifyPo->VINV_NO_SUPPLIER;

        $folder = 'others/' . $folderPath;

        $otherFiles = FACTWM_LOGVERIFY_NON_PO_OTHER_FILES::where('TRHVERIFY_NON_PO_IID', $verifyPo->IID)->get();

        $config_tnc_verify_po_non_po = Config::where('VVARIABLE', 'tnc_verify_po_non_po')->first();

        $tnc_verify_po_non_po = $config_tnc_verify_po_non_po->VVALUE ?? '-';

        $payload = $this->mapDataPayload($verifyPo);

        return view('modules.FACTWM.FACTWM02.FACTWMF008.partials._final-preview', compact('verifyPo', 'otherFiles', 'tnc_verify_po_non_po', 'payload'));
    }

    public function previewPdf(VerifyNonPo $verifyPo)
    {
        // Gate::authorize('previewPdf', $verifyPo);

        return Pdf::loadView('modules.FACTWM.FACTWM02.FACTWMF008.partials._print-preview', compact('verifyPo'))
            ->setPaper('A4', 'portrait')
            ->stream('billing_statement.pdf');
    }

    public function submitFinalPreview(Request $request, VerifyNonPo $verifyPo)
    {
        Gate::authorize('finalPreview', $verifyPo);
        try {
            $result = DB::transaction(function () use ($request, $verifyPo) {
                $filesToDelete = [];
                $supplier = Supplier::where('VSUPPLIER_CODE', $verifyPo->VSUPPLIER_CODE)->first();

                $disk = Storage::disk(config('filesystems.default'));

                if (! $supplier) {
                    throw new Exception('Data supplier not found');
                }

                // send ke api ifs
                $config_verify_api = Config::where('VVARIABLE', 'verify_api')->first();
                $verify_api = filter_var(
                    $config_verify_api->VVALUE ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );
                $status = 'WAITING';
                $date_approved = null;
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

                // pindahkan file invoice ke dms
                $fileInvoice = null;
                if (! empty($verifyPo->VPDF_INVOICE) && Storage::disk('public')->exists($verifyPo->VPDF_INVOICE)) {
                    $fileInvoice = $disk->exists($verifyPo->VPDF_INVOICE) ? $verifyPo->VPDF_INVOICE :
                        $this->dmsService->uploadFilePO(
                            $verifyPo->VPDF_INVOICE,
                            'file_invoice',
                            $supplier,
                            $verifyPo->VBILLING_STATEMENT
                        );
                    $filesToDelete[] = $verifyPo->VPDF_INVOICE;
                }

                // pindahkan file tax invoice ke dms
                $fileTax = null;
                if (! empty($verifyPo->VPDF_TAX) && Storage::disk('public')->exists($verifyPo->VPDF_TAX)) {
                    $fileTax = $disk->exists($verifyPo->VPDF_TAX)
                        ? $verifyPo->VPDF_TAX
                        : $this->dmsService->uploadFilePO(
                            $verifyPo->VPDF_TAX,
                            'file_faktur_pajak',
                            $supplier,
                            $verifyPo->VBILLING_STATEMENT
                        );
                    $filesToDelete[] = $verifyPo->VPDF_TAX;
                }

                // pindahkan file rekap jasa ke dms
                // if (!empty($verifyPo->VREKAP_JASA_FILE)) {
                //     $this->dmsService->uploadFilePO($verifyPo->VREKAP_JASA_FILE, 'file_rekap_jasa', $supplier);
                // }

                // $folder = 'others/' . $folderPath;

                $folderPath = 'non_po' . '/' . $verifyPo->VBILLING_STATEMENT . '/' . $verifyPo->VSUPPLIER_CODE . '_' . $verifyPo->VINV_NO_SUPPLIER;

                $folder = 'others/' . $folderPath;

                $otherFiles = Storage::disk('public')->files($folder);
                $logOtherFiles = FACTWM_LOGVERIFY_NON_PO_OTHER_FILES::where('TRHVERIFY_NON_PO_IID', $verifyPo->IID)->get();

                // pindahkan other files jika ada
                if (count($logOtherFiles) > 0) {
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

                            FACTWM_LOGVERIFY_NON_PO_OTHER_FILES::where('IID', $file->IID)->update([
                                'VPATH' => $fileOther,
                            ]);
                        }
                    }
                }

                $submitVerifyPO = VerifyNonPo::where('IID', $verifyPo->IID)->update([
                    'VSTATUS' => 'submit',
                    'DAPPROVED' => $date_approved,
                    // 'DSUBMITTED' => date('Y-m-d'),
                    'VSTATUS_INVOICE' => $status,
                    'VPDF_INVOICE' => $fileInvoice,
                    'VPDF_TAX' => $fileTax,
                    'VNOTES' => $request->notes,
                ]);

                foreach ($filesToDelete as $file) {
                    Storage::disk('public')->delete($file);
                }

                return [
                    'message' => $message,
                    'status' => $status,
                    'billing_statement' => $billing_statement,
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

    public function resendSI(Request $request, $id)
    {
        $verifyPo = VerifyNonPo::find($id);
        if (! $verifyPo) {
            return Response::error([
                'message' => 'PO Not Found',
            ], 422);
        }
        try {
            $result = DB::transaction(function () use ($request, $verifyPo) {
                // send ke api ifs
                $config_verify_api = Config::where('VVARIABLE', 'verify_api')->first();
                $verify_api = filter_var(
                    $config_verify_api->VVALUE ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );
                $status = 'WAITING';
                $date_approved = null;
                if ($verify_api) {
                    // $payload = $this->mapDataPayload($verifyPo);
                    $payload = json_decode($request->payload, true);
                    $postSI = $this->invoiceService->sendInvoice($payload);
                    if (! $postSI['success']) {
                        // return Response::error([
                        //     'message' => 'Resend SI Failed',
                        //     'detail'  => $result['message'] ?? null,
                        //     'errors'  => $result['errors'] ?? null,
                        // ], $result['status'] ?? 500);
                        throw new \Exception(
                            $postSI['message'] ?? 'Submit Final Preview PO gagal'
                        );
                        $status = 'FAILED';
                    }

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

                $submitVerifyPO = VerifyNonPo::where('IID', $verifyPo->IID)->update([
                    // 'VSTATUS' => 'submit',
                    'DAPPROVED' => $date_approved,
                    // 'DSUBMITTED' => date('Y-m-d'),
                    'VSTATUS_INVOICE' => $status,
                    // 'VPDF_INVOICE' => $fileInvoice,
                    // 'VPDF_TAX' => $fileTax,
                    // 'VNOTES'  => $request->notes
                ]);

                return [
                    'message' => $message,
                    'status' => $status,
                    'billing_statement' => $billing_statement,
                ];
            });

            return Response::success(message: $result['message'], data: [
                'action' => 'updated',
                'status' => $result['status'],
                'Billing_Statement_No' => $result['billing_statement'],
            ]);
            // code...
        } catch (\Throwable $th) {
            $error = json_decode($th->getMessage(), true);

            return Response::error([
                'message' => $error['message'] ?? 'Submit Final Preview PO gagal',
                'errors' => $error['errors'] ?? null,
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VerifyNonPo $VerifyNonPo)
    {
        DB::transaction(function () use ($VerifyNonPo) {
            $VerifyNonPo->details()->delete();
            $VerifyNonPo->delete();
        });

        return Response::success(message: "GR Note {$VerifyNonPo->VINV_NO_SUPPLIER} deleted successfully");
    }

    public function validateInvoice(Request $request)
    {
        $id = $request->id;
        $request->validate(
            [
                'params' => 'required',
                'invoice_number' => [
                    'required',
                    Rule::unique('FACTWM_TRHVERIFY_NON_PO', 'VINV_NO_SUPPLIER')
                        ->ignore($id, 'IID')
                        ->where(function ($query) {
                            $query->where('VSTATUS_INVOICE', '!=', 'REJECTED')
                                ->where('VSTATUS', 'submit');
                        }),
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
        $ocrResult = $this->ocrService->validate($params, $request->invoice_file, $renderDpi);
        $valid = data_get($ocrResult, 'valid', []);
        $text = data_get($ocrResult, 'text');

        $validUnchecked = collect($valid)->contains(function ($item) {
            return data_get($item, 'checked') === false;
        });

        if ($validUnchecked) {
            // update cache unverified
            $this->updateSelectedGrCache();
        }

        $cacheKey = 'selected_gr_scan_ocrverify_non_po_unverified_' . Auth::user()->IID;

        $unverifyOCR = Cache::get($cacheKey, 0);

        return Response::success([
            'valid' => $valid,
            'text' => $text,
            'unverifyOCR' => $unverifyOCR,
        ]);
    }

    public function validateTax(Request $request)
    {
        $id = $request->id;
        $request->validate(
            [
                'params' => 'required',
                'tax_number' => [
                    'required',
                    Rule::unique('FACTWM_TRHVERIFY_NON_PO', 'VTAX_NUMBER')
                        ->ignore($id, 'IID')
                        ->where(
                            fn($q) => $q->where('VSTATUS_INVOICE', '!=', 'REJECTED')
                                ->where('VSTATUS', 'submit')
                        ),
                ],
                'tax_file' => 'required|file|max:2048',
                'render_dpi' => 'nullable|integer|min:140',
            ],
            [
                'tax_file.uploaded' => 'Invoice file must not exceed 2 MB.',
                'tax_file.max' => 'Invoice file must not exceed 2 MB.',
            ]
        );

        $params = $this->mapParamsTax($request->params);
        $renderDpi = $this->resolveRenderDpi($request);
        $ocrResult = $this->ocrService->validate($params, $request->tax_file, $renderDpi);
        $valid = data_get($ocrResult, 'valid', []);
        $text = data_get($ocrResult, 'text');

        $validUnchecked = collect($valid)->contains(function ($item) {
            return data_get($item, 'checked') === false;
        });

        if ($validUnchecked) {
            // update cache unverified
            $this->updateSelectedGrCache();
        }

        $cacheKey = 'selected_gr_scan_ocrverify_non_po_unverified_' . Auth::user()->IID;

        $unverifyOCR = Cache::get($cacheKey, 0);

        return Response::success([
            'valid' => $valid,
            'text' => $text,
            'unverifyOCR' => $unverifyOCR,
        ]);
    }

    private function mapParamsTax($params)
    {
        $mapParams = explode(',', $params);
        $tax = $mapParams[0] ?? null;
        $tgl_tax = $mapParams[1] ?? null;
        $npwp = $mapParams[2] ?? null;
        $npwp_idbm = $mapParams[3] ?? null;
        $config_ocr_astemo_value = Config::where('VVARIABLE', 'ocr_astemo_value')->first();
        $ocr_astemo_value = $config_ocr_astemo_value->VVALUE ?? 'ASTEMO BEKASI';

        $listFormatTglInvoice = Helpers::listDateFormat($tgl_tax);

        $keywords = [
            [
                'key' => 'tax_invoice_number',
                'value' => $tax,
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
            [
                'key' => 'npwp_supplier',
                'value' => $npwp,
                'checked' => false,
            ],
            [
                'key' => 'npwp_idbm_match',
                'value' => $npwp_idbm,
                'checked' => false,
            ],
            // [
            //     'key' => 'astemo',
            //     'value' => $ocr_astemo_value,
            //     'checked' => false,
            // ],
        ]);

        return $keywords;
    }

    private function mapParamsInvoice($params)
    {
        $mapParams = explode(',', $params);
        $invoice = $mapParams[0] ?? null;
        $tgl_invoice = $mapParams[1] ?? null;
        $config_ocr_astemo_value = Config::where('VVARIABLE', 'ocr_astemo_value')->first();
        $ocr_astemo_value = $config_ocr_astemo_value->VVALUE ?? 'ASTEMO BEKASI';

        $listFormatTglInvoice = Helpers::listDateFormat($tgl_invoice);
        $keywords = [
            [
                'key' => 'invoice_number',
                'value' => $invoice,
                'checked' => false,
            ],
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
            // [
            //     'key' => 'net-amount',
            //     'id'  => 'net-amount-status',
            //     'value' => number_format((float) $amount, 0, ',', '.'),
            //     'checked' => false,
            // ],
            // [
            //     'key' => 'net-amount',
            //     'id'  => 'net-amount-status',
            //     'value' => number_format((float) $amount, 0, '.', ','),
            //     'checked' => false,
            // ],
            // [
            //     'key' => 'net-amount',
            //     'id'  => 'net-amount-status',
            //     'value' => strval($amount),
            //     'checked' => false,
            // ],
            // [
            //     'key' => 'ppn',
            //     'id'  => 'ppn-status',
            //     'value' => number_format((float) $ppn, 0, ',', '.'),
            //     'checked' => false,
            // ],
            // [
            //     'key' => 'ppn',
            //     'id'  => 'ppn-status',
            //     'value' => number_format((float) $ppn, 0, '.', ','),
            //     'checked' => false,
            // ],
            // [
            //     'key' => 'ppn',
            //     'id'  => 'ppn-status',
            //     'value' => strval($ppn),
            //     'checked' => false,
            // ],
            // [
            //     'key' => 'astemo',
            //     'value' => $ocr_astemo_value,
            //     'checked' => false,
            // ],
        ]);

        return $keywords;
    }

    public function export(Request $request)
    {
        $export = new FACTWMF008Export;

        return Excel::download($export, 'Verify_Non_Po' . date('YmdHis') . '.xlsx');
    }

    public function getIdbmNpwpMatch()
    {
        $roles = count(Auth::user()->roles) > 0 ? Auth::user()->roles->pluck('VROLENAME')->toArray()[0] : 'Guest';

        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;

        $idViewers = $userSupplier->ISUPPLIER_ID;
        $supplier = Supplier::findOrFail($idViewers);
        // $supplier_code = $supplier->VSUPPLIER_CODE;
        $npwp = ! empty($supplier->VNPWP) ? $supplier->VNPWP : '';
        $config_npwp_idbm_match = Config::where('VVARIABLE', 'npwp_idbm_match')->first();

        return $npwp;
    }

    private function generateBillingNumber(): string
    {
        $prefix = 'NP' . date('Y') . date('m'); // contoh: PO202512

        $lastData = VerifyNonPo::where('VBILLING_STATEMENT', 'like', $prefix . '%')
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

    private function updateSelectedGrCache()
    {
        $userId = Auth::user()->IID;
        $cacheKey = 'selected_gr_scan_ocrverify_non_po_unverified_' . $userId;

        $lastUnverified = Cache::get($cacheKey, 0);

        $payload = $lastUnverified + 1;
        $expiresAt = now()->endOfDay();

        Cache::put($cacheKey, $payload, $expiresAt);

        return true;
    }

    private function getLastDraftCacheKey(): string
    {
        return 'verify_non_po_last_draft_' . Auth::user()->IID;
    }

    private function resolveRenderDpi(Request $request): int
    {
        return $request->integer('render_dpi') ?: 140;
    }

    private function mapDataPayload($verifyPo)
    {
        $payload = [[
            'Billing_Stat_No' => $verifyPo->VBILLING_STATEMENT,
            'Supplier' => $verifyPo->VSUPPLIER_CODE,
            'Currency' => 'IDR',
            'Order_No' => $verifyPo->VUNIQUE_CODE,
            'Reference_No' => null,
            'Invoice_No' => $verifyPo->VINV_NO_SUPPLIER,
            'Invoice_Date' => $verifyPo->DINV_DATE->format('Y-m-d') ?? null,
            'TaxCode' => $verifyPo->VTAX_CODE,
            'NetAmount' => (string) $verifyPo->INET_AMOUNT ?? 0,
            'TaxAmount' => (string) $verifyPo->VPPN ?? 0,
            'GrossAmount' => (string) $verifyPo->ITOTAL ?? 0,
        ]];

        return $payload;
    }

    public function download($id, $type)
    {
        $verifyPo = VerifyNonPo::where('IID', $id)->first();
        if (! $verifyPo) {
            throw new Exception('Verify PO Not Found');
        }
        if ($type == 'invoice') {
            $file = $verifyPo->VPDF_INVOICE;
        } else {
            $file = $verifyPo->VPDF_TAX;
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
        $logOtherFiles = FACTWM_LOGVERIFY_NON_PO_OTHER_FILES::where('IID', $id)->first();
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
