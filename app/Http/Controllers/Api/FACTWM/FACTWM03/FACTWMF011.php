<?php

namespace App\Http\Controllers\Api\FACTWM\FACTWM03;

use App\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\FACTWM\FACTWM03\VerifyInvoiceRequest;
use Illuminate\Http\Request;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_PO as VerifyPo;
use App\Models\FACTWM02\FACTWM_TRDVERIFY_PO_DETAILS as VerifyPoDetail;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_NON_PO as VerifyNonPo;
use App\Models\FACTWM02\FACTWM_TRDVERIFY_NON_PO_DETAILS as VerifyNonPoDetail;
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES as GRNote;
use Illuminate\Support\Facades\DB;
use Exception;

class FACTWMF011 extends Controller
{
    public function store(VerifyInvoiceRequest $request)
    {
        try {
            $validated = $request->validated();

            return DB::transaction(function () use ($validated) {
                foreach ($validated['data'] as $data) {
                    $bsNo = $data['bs_no'];
                    $transaction_category = str_contains(strtoupper($bsNo), 'NP') ? 'NON PO' : 'PO';

                    $verify = $transaction_category === 'PO'
                        ? VerifyPo::where('VBILLING_STATEMENT', $bsNo)->where('VSTATUS', 'submit')->first()
                        : VerifyNonPo::where('VBILLING_STATEMENT', $bsNo)->where('VSTATUS', 'submit')->first();

                    if (!$verify) {
                        throw new \Exception("Data {$transaction_category} Not Found: {$bsNo}");
                    }

                    // update invoice status
                    $verify->VSTATUS_INVOICE = ($data['status'] === 'CANCEL') ? 'PROCESSING' : $data['status'];

                    // update GR only for PO & not CANCEL
                    if (
                        $transaction_category === 'PO'
                        && $data['status'] !== 'CANCEL'
                        && !empty($verify->VGRN_NUMBER)
                    ) {
                        // $arrGrList = is_array($verify->VGRN_NUMBER)
                        //     ? $verify->VGRN_NUMBER
                        //     : [$verify->VGRN_NUMBER];

                        $this->updateFieldsForSelectedAndReturnGr($verify->VGR_NUMBER_IID, [
                            'VSTATUS_SUBMITTED' => $data['status'],
                            'VSTATUS' => 'CLOSED',
                        ]);
                    }

                    $verify->DAPPROVED = now();
                    // $verify->VPYHSICAL_DOC_STATUS = 'SUBMITTED';

                    $verify->save();
                }

                return response()->json(
                    [
                        'status' => 'success',
                        'message' => 'Update data berhasil',
                    ]
                );
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage(),
                // 'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    private function updateFieldsForSelectedAndReturnGr(array $iids, array $fields): void
    {
        $normalizedIids = collect($iids)
            ->filter(fn($id) => !is_null($id) && $id !== '')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        if (empty($normalizedIids) || empty($fields)) {
            return;
        }

        GRNote::whereIn('IID', $normalizedIids)->update($fields);

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
            ->update($fields);
    }
}
