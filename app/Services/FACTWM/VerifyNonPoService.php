<?php

namespace App\Services\FACTWM;

use App\Models\FACTWM02\FACTWM_TRHVERIFY_NON_PO as VerifyNonPo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class VerifyNonPoService
{
    public function detailsMapping(array $details, VerifyNonPo $invoice)
    {
        $mapped = [];

        foreach ($details as $detail) {
            $mapped[] = [
                'VDESCRIPTION' => $detail['description'],
                'IQTY' => $detail['qty'],
                'VUOM' => $detail['unit'],
                'IPRICE' => $detail['price'],
                'IDPP_NILAI_LAIN' => null,
                'IPPN' => null,
                'ITOTAL' => $detail['total'],
                'TRHVERIFY_NON_PO_IID' => $invoice->IID,
            ];
        }

        return $mapped;
    }
}
