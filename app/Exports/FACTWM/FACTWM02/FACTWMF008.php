<?php

namespace App\Exports\FACTWM\FACTWM02;

use App\Models\FACTWM02\FACTWM_TRDVERIFY_NON_PO_DETAILS as VerifyNonPoDetail;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;

class FACTWMF008 implements FromView
{
    public function view(): View
    {
        $query = VerifyNonPoDetail::leftJoin(
            'FACTWM_TRHVERIFY_NON_PO', // tabel header
            'FACTWM_TRHVERIFY_NON_PO.IID', // kolom header
            '=',
            'FACTWM_TRDVERIFY_NON_PO_DETAILS.TRHVERIFY_NON_PO_IID' // kolom detail
        )
            ->select(
                'FACTWM_TRHVERIFY_NON_PO.*',
                'FACTWM_TRDVERIFY_NON_PO_DETAILS.VDESCRIPTION',
                'FACTWM_TRDVERIFY_NON_PO_DETAILS.IQTY',
                'FACTWM_TRDVERIFY_NON_PO_DETAILS.VUOM',
                'FACTWM_TRDVERIFY_NON_PO_DETAILS.IPRICE',
                'FACTWM_TRDVERIFY_NON_PO_DETAILS.IDPP_NILAI_LAIN',
                'FACTWM_TRDVERIFY_NON_PO_DETAILS.IPPN',
                'FACTWM_TRDVERIFY_NON_PO_DETAILS.ITOTAL',
            )
            ->whereNull('FACTWM_TRHVERIFY_NON_PO.DDELETE')
            ->whereNull('FACTWM_TRDVERIFY_NON_PO_DETAILS.DDELETE');

        $user = Auth::user();
        if ($user?->supplierUser) {
            $supplierCode = trim((string) $user->supplierUser->VSUPPLIER_CODE);

            if ($supplierCode === '') {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('FACTWM_TRHVERIFY_NON_PO.VSUPPLIER_CODE', $supplierCode);
            }
        }

        $data = $query->get();


        return view(
            'modules.FACTWM.FACTWM02.FACTWMF008.FACTWMF008_Export',
            [
                'data' => $data
            ]
        );
    }
}
