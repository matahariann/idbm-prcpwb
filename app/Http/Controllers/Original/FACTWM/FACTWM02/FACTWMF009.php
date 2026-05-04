<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM02;

use App\DataTables\Original\FACTWM02\FACTWMF009DataTable;
use App\Http\Controllers\Controller;
use App\Mail\SendNotificationScanVerifyMail;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER_COMMUNICATION_METHOD;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_NON_PO;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_PO;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FACTWMF009 extends Controller
{
    public function index(FACTWMF009DataTable $dataTable)
    {
        return $dataTable->render('modules.FACTWM.FACTWM02.FACTWMF009.FACTWMF009');
    }

    /**
     * Check Billing Statement
     * Validasi apakah billing statement ada di database (PO atau Non-PO)
     */
    public function checkByBilling(Request $request)
    {
        try {
            // Validasi input
            $data = $request->validate([
                'billing_statement' => 'required|string|max:255'
            ], [
                'billing_statement.required' => 'Billing Statement wajib diisi',
                'billing_statement.string' => 'Billing Statement harus berupa text',
                'billing_statement.max' => 'Billing Statement maksimal 255 karakter'
            ]);

            $billingStatement = $data['billing_statement'];

            // Cek di Non-PO terlebih dahulu
            $billingNonPo = FACTWM_TRHVERIFY_NON_PO::where('VBILLING_STATEMENT', $billingStatement)
                ->whereNull('VDELETE')
                ->first();

            // Jika tidak ada di Non-PO, cek di PO
            $billingPo = null;
            $billingType = null;

            if ($billingNonPo) {
                $billing = $billingNonPo;
                $billingType = 'non-po';

                // Check apakah sudah di-scan
                $isScanned = $billingNonPo->DSUBMITTED !== null;
            } else {
                $billingPo = FACTWM_TRHVERIFY_PO::where('VBILLING_STATEMENT', $billingStatement)
                    ->whereNull('VDELETE')
                    ->first();

                if ($billingPo) {
                    $billing = $billingPo;
                    $billingType = 'po';

                    // Check apakah sudah di-scan (untuk PO bisa menggunakan field lain jika ada)
                    $isScanned = false; // Sesuaikan dengan logic PO
                } else {
                    $billing = null;
                }
            }

            if (!$billing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Billing Statement tidak ditemukan atau sudah tidak aktif!'
                ], 404);
            }

            if ($isScanned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Billing Statement ini sudah pernah di-scan sebelumnya!'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Billing Statement ditemukan',
                'data' => [
                    'billing_id' => $billing->IID,
                    'billing_statement' => $billing->VBILLING_STATEMENT,
                    'billing_type' => $billingType,
                    'created_date' => $billing->DCREA,
                    'status' => $billingType === 'non-po' ? ($billing->VSTATUS ?? 'active') : 'active'
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check Unique Code
     * Validasi apakah unique code sesuai dengan billing statement (PO atau Non-PO)
     */
    public function checkByUniqueCode(Request $request)
    {
        try {
            // Validasi input
            $data = $request->validate([
                'billing_statement' => 'required|string|max:255',
                'unique_code' => 'required|string|max:255'
            ], [
                'billing_statement.required' => 'Billing Statement wajib diisi',
                'unique_code.required' => 'Unique Code wajib diisi',
                'unique_code.string' => 'Unique Code harus berupa text',
                'unique_code.max' => 'Unique Code maksimal 255 karakter'
            ]);

            $billingStatement = $data['billing_statement'];
            $uniqueCode = $data['unique_code'];

            // Cek di Non-PO terlebih dahulu
            $invoiceNonPo = FACTWM_TRHVERIFY_NON_PO::where('VBILLING_STATEMENT', $billingStatement)
                ->where('VUNIQUE_CODE', $uniqueCode)
                ->whereNull('VDELETE')
                ->first();

            $invoiceType = null;

            if ($invoiceNonPo) {
                $invoice = $invoiceNonPo;
                $invoiceType = 'non-po';

                // Check apakah sudah di-scan
                $isScanned = $invoiceNonPo->DSUBMITTED !== null;

                // Get supplier name
                $supplier = FACTWM_MSHSUPPLIER::where('VSUPPLIER_CODE', $invoice->VSUPPLIER_CODE)
                    ->whereNull('VDELETE')
                    ->first();

                $responseData = [
                    'invoice_id' => $invoice->IID,
                    'supplier_code' => $invoice->VSUPPLIER_CODE,
                    'supplier_name' => $supplier ? $supplier->VNAME : $invoice->VSUPPLIER_CODE,
                    'no_invoice' => $invoice->VINV_NO_SUPPLIER,
                    'unique_code' => $invoice->VUNIQUE_CODE,
                    'billing_statement' => $invoice->VBILLING_STATEMENT,
                    'invoice_type' => $invoiceType
                ];
            } else {
                // Jika tidak ada di Non-PO, cek di PO
                $invoicePo = FACTWM_TRHVERIFY_PO::where('VBILLING_STATEMENT', $billingStatement)
                    ->where('VUNIQUE_CODE', $uniqueCode)
                    ->whereNull('VDELETE')
                    ->first();

                if ($invoicePo) {
                    $invoice = $invoicePo;
                    $invoiceType = 'po';

                    // Check apakah sudah di-scan (sesuaikan dengan logic PO)
                    $isScanned = $invoicePo->DSUBMITTED !== null;

                    // Get supplier name
                    $supplier = FACTWM_MSHSUPPLIER::where('VSUPPLIER_CODE', $invoice->VSUPPLIER_CODE)
                        ->whereNull('VDELETE')
                        ->first();

                    $responseData = [
                        'invoice_id' => $invoice->IID,
                        'supplier_code' => $invoice->VSUPPLIER_CODE,
                        'supplier_name' => $supplier ? $supplier->VNAME : $invoice->VSUPPLIER_CODE,
                        'no_invoice' => $invoice->VINVOICE_NUMBER,
                        'unique_code' => $invoice->VUNIQUE_CODE,
                        'billing_statement' => $invoice->VBILLING_STATEMENT,
                        'invoice_type' => $invoiceType
                    ];
                } else {
                    $invoice = null;
                }
            }

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unique Code tidak sesuai dengan Billing Statement atau tidak ditemukan!'
                ], 404);
            }

            if ($isScanned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Billing Statement dan Unique Code ini sudah pernah di-scan sebelumnya!'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Unique Code ditemukan dan sesuai',
                'data' => $responseData
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get History
     * Ambil history scan berdasarkan billing statement dan unique code (PO atau Non-PO)
     * Jika salah satu kosong, ambil dari tabel yang memiliki data
     */
    public function history(Request $request)
    {
        try {
            // Query Non-PO
            $queryNonPo = FACTWM_TRHVERIFY_NON_PO::whereNull('VDELETE');

            if ($request->filled('billing_statement')) {
                $queryNonPo->where('VBILLING_STATEMENT', $request->billing_statement);
            }

            if ($request->filled('unique_code')) {
                $queryNonPo->where('VUNIQUE_CODE', $request->unique_code);
            }

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $dateFrom = $request->date_from . ' 00:00:00';
                $dateTo = $request->date_to . ' 23:59:59';
                $queryNonPo->whereBetween('DCREA', [$dateFrom, $dateTo]);
            }

            $historyNonPo = $queryNonPo->orderBy('DCREA', 'desc')->get();

            // Query PO
            $queryPo = FACTWM_TRHVERIFY_PO::whereNull('VDELETE');

            if ($request->filled('billing_statement')) {
                $queryPo->where('VBILLING_STATEMENT', $request->billing_statement);
            }

            if ($request->filled('unique_code')) {
                $queryPo->where('VUNIQUE_CODE', $request->unique_code);
            }

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $dateFrom = $request->date_from . ' 00:00:00';
                $dateTo = $request->date_to . ' 23:59:59';
                $queryPo->whereBetween('DCREA', [$dateFrom, $dateTo]);
            }

            $historyPo = $queryPo->orderBy('DCREA', 'desc')->get();

            // Kondisi: Jika salah satu kosong, ambil dari yang memiliki data
            if ($historyNonPo->isEmpty() && $historyPo->isNotEmpty()) {
                // Hanya ambil dari PO
                $formattedData = $historyPo->map(function ($item) {
                    $supplier = FACTWM_MSHSUPPLIER::where('VSUPPLIER_CODE', $item->VSUPPLIER_CODE)
                        ->whereNull('VDELETE')
                        ->first();

                    return [
                        'id' => $item->IID,
                        'supplier_name' => $supplier ? $supplier->VNAME : $item->VSUPPLIER_CODE,
                        'no_invoice' => $item->VINVOICE_NUMBER,
                        'no_bs' => $item->VBILLING_STATEMENT,
                        'unik_code' => $item->VUNIQUE_CODE,
                        'submitted_date' => $item->DSUBMITTED ? Carbon::parse($item->DSUBMITTED)->format('d-m-Y H:i:s') : '-',
                        'status' => 'completed',
                        'type' => 'PO'
                    ];
                })->values();
            } elseif ($historyPo->isEmpty() && $historyNonPo->isNotEmpty()) {
                // Hanya ambil dari Non-PO
                $formattedData = $historyNonPo->map(function ($item) {
                    $supplier = FACTWM_MSHSUPPLIER::where('VSUPPLIER_CODE', $item->VSUPPLIER_CODE)
                        ->whereNull('VDELETE')
                        ->first();

                    return [
                        'id' => $item->IID,
                        'supplier_name' => $supplier ? $supplier->VNAME : $item->VSUPPLIER_CODE,
                        'no_invoice' => $item->VINV_NO_SUPPLIER,
                        'no_bs' => $item->VBILLING_STATEMENT,
                        'unik_code' => $item->VUNIQUE_CODE,
                        'submitted_date' => $item->DSUBMITTED ? Carbon::parse($item->DSUBMITTED)->format('d-m-Y H:i:s') : '-',
                        'status' => $item->VSTATUS ?? 'completed',
                        'type' => 'Non-PO'
                    ];
                })->values();
            } elseif ($historyNonPo->isNotEmpty() && $historyPo->isNotEmpty()) {
                // Jika keduanya ada data, gabungkan
                $formattedNonPo = $historyNonPo->map(function ($item) {
                    $supplier = FACTWM_MSHSUPPLIER::where('VSUPPLIER_CODE', $item->VSUPPLIER_CODE)
                        ->whereNull('VDELETE')
                        ->first();

                    return [
                        'id' => $item->IID,
                        'supplier_name' => $supplier ? $supplier->VNAME : $item->VSUPPLIER_CODE,
                        'no_invoice' => $item->VINV_NO_SUPPLIER,
                        'no_bs' => $item->VBILLING_STATEMENT,
                        'unik_code' => $item->VUNIQUE_CODE,
                        'submitted_date' => $item->DSUBMITTED ? Carbon::parse($item->DSUBMITTED)->format('d-m-Y H:i:s') : '-',
                        'status' => $item->VSTATUS ?? 'completed',
                        'type' => 'Non-PO'
                    ];
                });

                $formattedPo = $historyPo->map(function ($item) {
                    $supplier = FACTWM_MSHSUPPLIER::where('VSUPPLIER_CODE', $item->VSUPPLIER_CODE)
                        ->whereNull('VDELETE')
                        ->first();

                    return [
                        'id' => $item->IID,
                        'supplier_name' => $supplier ? $supplier->VNAME : $item->VSUPPLIER_CODE,
                        'no_invoice' => $item->VINVOICE_NUMBER,
                        'no_bs' => $item->VBILLING_STATEMENT,
                        'unik_code' => $item->VUNIQUE_CODE,
                        'submitted_date' => $item->DSUBMITTED ? Carbon::parse($item->DSUBMITTED)->format('d-m-Y H:i:s') : '-',
                        'status' => 'completed',
                        'type' => 'PO'
                    ];
                });

                $formattedData = $formattedNonPo->merge($formattedPo)
                    ->sortByDesc('submitted_date')
                    ->values();
            } else {
                // Jika keduanya kosong
                $formattedData = collect();
            }

            return response()->json([
                'success' => true,
                'message' => 'Data history berhasil diambil',
                'data' => $formattedData
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit Data
     * Simpan data hasil scan ke database (PO atau Non-PO)
     */
    public function submit(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validasi input
            $data = $request->validate([
                'billing_statement' => 'required|string|max:255',
                'unique_code' => 'required|string|max:255',
                'supplier_name' => 'required|string|max:255',
                'no_invoice' => 'required|string|max:255'
            ], [
                'billing_statement.required' => 'Billing Statement wajib diisi',
                'unique_code.required' => 'Unique Code wajib diisi',
                'supplier_name.required' => 'Supplier Name wajib diisi',
                'no_invoice.required' => 'No Invoice wajib diisi'
            ]);

            // Cek di Non-PO
            $invoiceNonPo = FACTWM_TRHVERIFY_NON_PO::where('VBILLING_STATEMENT', $data['billing_statement'])
                ->where('VUNIQUE_CODE', $data['unique_code'])
                ->whereNull('VDELETE')
                ->first();

            $invoiceType = null;
            $supplierCode = null;

            if ($invoiceNonPo) {
                $invoice = $invoiceNonPo;
                $invoiceType = 'non-po';
                $supplierCode = $invoice->VSUPPLIER_CODE;

                // Check duplikasi
                if ($invoice->DSUBMITTED) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Data sudah pernah di-submit sebelumnya!'
                    ], 400);
                }

                // Update data invoice
                $invoice->update([
                    'DSUBMITTED' => now(),
                    'VSTATUS' => 'submit',
                    'VPYHSICAL_DOC_STATUS' => 'SUBMITTED',
                    'VMODI' => Auth::user()->name ?? 'system',
                    'DMODI' => now()
                ]);

                $submittedDate = $invoice->DSUBMITTED->format('d-m-Y H:i:s');
            } else {
                // Cek di PO
                $invoicePo = FACTWM_TRHVERIFY_PO::where('VBILLING_STATEMENT', $data['billing_statement'])
                    ->where('VUNIQUE_CODE', $data['unique_code'])
                    ->whereNull('VDELETE')
                    ->first();

                if ($invoicePo) {
                    $invoice = $invoicePo;
                    $invoiceType = 'po';

                    // Update data invoice PO (sesuaikan field yang diperlukan)
                    $invoice->update([
                        'DSUBMITTED' => now(),
                        'VSTATUS' => 'submit',
                        'VPYHSICAL_DOC_STATUS' => 'SUBMITTED',
                        'VMODI' => Auth::user()->name ?? 'system',
                        'DMODI' => now()
                    ]);

                    $submittedDate = now()->format('d-m-Y H:i:s');
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invoice tidak ditemukan!'
                    ], 404);
                }
            }

            DB::commit();

            // Siapkan data untuk email
            $currentDateTime = Carbon::now();
            $emailData = [
                'nomor_billing' => $data['billing_statement'],
                'tanggal' => $currentDateTime->format('d F Y'),
                'jam' => $currentDateTime->format('H:i:s'),
                'status' => 'Received (Security)',
            ];

            // Kirim email notifikasi
            try {
                // Mail::to('fajarawalludin25@gmail.com')->queue(new SendNotificationScanVerifyMail($emailData));
                // Cari supplier berdasarkan supplier_code
                $supplier = FACTWM_MSHSUPPLIER::where('VSUPPLIER_CODE', $supplierCode)
                    ->whereNull('VDELETE')
                    ->first();

                if ($supplier) {
                    // Ambil semua email communication method untuk supplier ini
                    // VMETHOD_ID untuk email biasanya 'E-MAIL' atau sejenisnya
                    $emailCommunications = FACTWM_MSHSUPPLIER_COMMUNICATION_METHOD::where('ISUPPLIER_ID', $supplier->IID)
                        ->where('VMETHOD_ID', 'ilike', '%email%')
                        ->whereNotNull('VADDRESS_ID')
                        ->where('VADDRESS_ID', '!=', '')
                        ->get();

                    // Jika ada email yang ditemukan
                    if ($emailCommunications->isNotEmpty()) {
                        // Cari email default terlebih dahulu
                        $defaultEmail = $emailCommunications->where('BMETHOD_DEFAULT', true)->first();

                        // Jika ada email default, gunakan itu. Jika tidak, gunakan email pertama
                        $emailToSend = $defaultEmail ? $defaultEmail->VADDRESS_ID : $emailCommunications->first()->VADDRESS_ID;

                        // Kirim email ke supplier
                        Mail::to($emailToSend)->queue(
                            new SendNotificationScanVerifyMail($emailData)
                        );

                        Log::info("Email sent to supplier: {$emailToSend} for billing statement: {$data['billing_statement']}");
                    } else {
                        Log::warning("No email found for supplier code: {$supplierCode}");
                    }
                } else {
                    Log::warning("Supplier not found for code: {$supplierCode}");
                }
            } catch (Exception $mailException) {
                Log::error('Failed to send email: ' . $mailException->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'data' => [
                    'history_id' => $invoice->IID,
                    'submitted_date' => $submittedDate,
                    'invoice_type' => $invoiceType
                ]
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
