<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM03;

use App\DataTables\Original\FACTWM03\FACTWMF011Datatable;
use App\Exports\FACTWM\FACTWM03\FACTWMF011 as FACTWMF011Export;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES as GRNote;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_PO as VerifyPo;
use App\Models\FACTWM02\FACTWM_TRDVERIFY_PO_DETAILS as VerifyPoDetail;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_NON_PO as VerifyNonPo;
use App\Models\FACTWM02\FACTWM_TRDVERIFY_NON_PO_DETAILS as VerifyNonPoDetail;
use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION as Config;
use App\Services\FACTWM\InvoiceService;
use Exception;
use Carbon\Carbon;

// Report Invoice
class FACTWMF011 extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function index(FACTWMF011Datatable $dataTable)
    {
        return $dataTable->render('modules.FACTWM.FACTWM03.FACTWMF011.FACTWMF011');
    }

    public function export(Request $request)
    {
        $export = new FACTWMF011Export();

        return Excel::download($export, 'Report_Invoice_' . date('YmdHis') . '.xlsx');
    }

    public function approveInvoice(Request $request, $id)
    {
        $category = $request->input('transaction_category');
        try {
            if ($category === 'PO') {
                $verify = VerifyPo::find($id);

                if (!$verify) {
                    throw new Exception('Data PO Not Found');
                }
            } else {
                $verify = VerifyNonPo::find($id);

                if (!$verify) {
                    throw new Exception('Data Non PO Not Found');
                }
            }

            $config_verify_api = Config::where('VVARIABLE', 'verify_api')->first();
            $verify_api = filter_var($config_verify_api->VVALUE ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($verify_api) {
                $payload = $this->normalizePayload($request->input('payload'));
                $postSI  = $this->invoiceService->sendInvoice($payload);
                if (!$postSI['success']) {
                    $message = is_array($postSI['message'])
                        ? ($postSI['message']['message'] ?? 'Resend SI Failed')
                        : $postSI['message'];

                    throw new \Exception(
                        $message,
                        (int) ($postSI['status'] ?? 500)
                    );
                }

                // $status = 'WAITING';
                // $date_approved = now();
                // $message = $data['message'] ?? null;
                // $billing_statement = $data['Billing_Statement_No'] ?? null;
                $data = $postSI['data'];
                $status  = $data['status'] ?? 'WAITING';
            } else {
                // $message = 'Data sedang proses, API berhasil dipanggil';
                // $date_approved = now();
                // $billing_statement = $verify->VBILLING_STATEMENT;
                $status = 'WAITING';
            }
            // $verify->VSTATUS_INVOICE === 'ESCALATED'
            // ? 'PRELIMENARY'
            // : 'WAITING';

            // if ($category === 'PO' && $verify->VSTATUS_INVOICE == 'PRELIMENARY') {
            //     if (!empty($verifyPo->VGRN_NUMBER)) {
            //         $arrGrList = is_array($verifyPo->VGRN_NUMBER)
            //             ? $verifyPo->VGRN_NUMBER
            //             : [$verifyPo->VGRN_NUMBER];

            //         GRNote::whereIn('VGR_NUMBER', $arrGrList)->update([
            //             'VSTATUS_SUBMITTED' => 'PRELIMENARY'
            //         ]);
            //     }
            // }

            if ($category === 'PO') {
                GRNote::whereIn('IID', $verify->VGR_NUMBER_IID)->update([
                    'VSTATUS_SUBMITTED' => $status,
                ]);
            }

            $verify->VSTATUS_INVOICE = $status;
            $verify->DAPPROVED = now();
            // $verify->VPYHSICAL_DOC_STATUS = 'SUBMITTED';

            $verify->save();

            return response()->json([
                'message' => 'Approve Invoice Successfully',
                'IID' => $id,
                'success' => true,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function rejectInvoice(Request $request, $id)
    {
        $category = $request->input('transaction_category');
        try {
            if ($category === 'PO') {
                $verify = VerifyPo::find($id);

                if (!$verify) {
                    throw new Exception('Data PO Not Found');
                }
            } else {
                $verify = VerifyNonPo::find($id);

                if (!$verify) {
                    throw new Exception('Data Non PO Not Found');
                }
            }

            $verify->VSTATUS_INVOICE = 'REJECTED';

            if ($category === 'PO') {
                GRNote::whereIn('IID', $verify->VGR_NUMBER_IID)->update([
                    'VSTATUS_SUBMITTED' => 'PENDING',
                    'VSTATUS' => 'NEW'
                ]);
            }

            $verify->VBILLING_STATEMENT = null;
            $verify->DAPPROVED = now();
            // $verify->VPYHSICAL_DOC_STATUS = 'SUBMITTED';

            $verify->save();

            return response()->json([
                'message' => 'Reject Invoice Successfully',
                'IID' => $id,
                'success' => true,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function getDetail(Request $request, $id)
    {
        $category = $request->input('transaction_category');
        try {
            if ($category === 'PO') {
                $verify = VerifyPo::find($id);

                if (!$verify) {
                    throw new Exception('Data PO Not Found');
                }

                $details = collect($verify->details ?? []);
            } else {
                $verify = VerifyNonPo::find($id);

                if (!$verify) {
                    throw new Exception('Data Non PO Not Found');
                }

                $details = collect($verify->details ?? []);
            }

            $mapped = $details->map(function ($item) use ($category) {

                // hitung aging AP (dalam hari)
                $agingAp = 0;
                if (!empty($item->DCREA)) {
                    if ($category == 'PO') {
                        $agingAp = (int) Carbon::parse($item->FACTWM_TRHGR_NOTES_DGR)->diffInDays(Carbon::today());
                    } else {
                        $agingAp = (int) Carbon::parse($item->DCREA)->diffInDays(Carbon::today());
                    }
                }

                if ($category == 'PO') {
                    return [
                        'part_number' => $item->gr_details?->VMATERIAL_CODE ?? '-',
                        'description' => $item->gr_details?->VDESCRIPTION ?? '-',
                        'qty'         => (int) ($item->gr_details?->IQTY ?? 0),
                        'price'       => (float) ($item->gr_details?->VPRICE ?? 0),
                        'curr'        => $item->gr_details?->VCURRENCY,
                        'subtotal'    => (float) ($item->gr_details?->VAMOUNT ?? 0),
                        'ppn'         => (float) 0,
                        'aging_ap'    => $agingAp,
                    ];
                } else {
                    return [
                        'part_number' => '-',
                        'description' => $item->VDESCRIPTION ?? '-',
                        'qty'         => (int) ($item->IQTY ?? 0),
                        'price'       => (float) ($item->IPRICE ?? 0),
                        'curr'        => null,
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

    private function normalizePayload($payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                throw new Exception('Invalid payload format');
            }

            return $decoded;
        }

        throw new Exception('Payload must be a JSON string or array');
    }
}
