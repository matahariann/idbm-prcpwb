<?php

namespace App\Exports\FACTWM\FACTWM03;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class FACTWMF011 implements FromView
{
    public function view(): View
    {
        $poQuery = $this->getPOQuery();
        $nonPoQuery = $this->getNonPOQuery();

        $unionQuery = $poQuery->unionAll($nonPoQuery);

        $data = DB::query()
            ->fromSub($unionQuery, 'x') // 🔥 wrap union
            ->when(
                Auth::user()?->supplierUser?->VSUPPLIER_CODE,
                fn($q, $supplierCode) =>
                $q->where('supplier_code', $supplierCode)
            )
            ->where("VSTATUS", 'submit')
            ->orderBy('DMODI', 'desc') // ✅ Tambahkan ini
            ->get()
            ->map(function ($item) {
                $item->aging_ap = $item->aging_ap
                    ? (int) \Carbon\Carbon::parse($item->aging_ap)
                        ->diffInDays(\Carbon\Carbon::today())
                    : 0;

                return $item;
            });

        return view(
            'modules.FACTWM.FACTWM03.FACTWMF011.FACTWMF011_Export',
            [
                'data' => $data
            ]
        );
    }


    public function getPOQuery()
    {
        return DB::table('FACTWM_TRDVERIFY_PO_DETAILS as d')
            ->leftJoin('FACTWM_TRDGR_NOTE_DETAILS as grd', 'd.TRDGR_NOTE_DETAILS_IID', 'grd.IID')
            ->leftJoin('FACTWM_TRHVERIFY_PO as p', 'd.TRHVERIFY_PO_IID', 'p.IID')
            ->leftJoin('FACTWM_MSHSUPPLIERS as fm', 'fm.VSUPPLIER_CODE', '=', 'p.VSUPPLIER_CODE')
            ->select([
                DB::raw('p."IID" AS "id"'),
                DB::raw('p."VBILLING_STATEMENT" AS "bs_no"'),
                DB::raw('p."VUNIQUE_CODE" AS "unique_code"'),
                DB::raw('p."VINVOICE_NUMBER" AS "inv_no"'),
                DB::raw('p."DINVOICE_DATE" AS "inv_date"'),
                DB::raw('p."VTAX_INVOICE_NUMBER" AS "tax_inv_no"'),
                DB::raw('p."DTAX_INVOICE_DATE" AS "tax_inv_date"'),
                DB::raw('fm."VSUPPLIER_CODE" AS "supplier_code"'),
                DB::raw('fm."VNAME" AS "supplier_name"'),
                DB::raw('fm."VNPWP" AS "npwp"'),
                DB::raw('fm."VNIK" AS "nik"'),

                // 🔥 CAST NUMERIC (PENTING)
                DB::raw('CAST(p."INET_AMOUNT" AS NUMERIC) AS "sub_total"'),
                DB::raw('CAST(p."IDPP" AS NUMERIC) AS "dpp_nilai_lain"'),
                DB::raw('CAST(p."IPPN" AS NUMERIC) AS "tarif_ppn"'),
                DB::raw('CAST(p."IDPP_PPH" AS NUMERIC) AS "dpp_pph"'),

                DB::raw('p."VPPH" AS "pph_pasal"'),
                DB::raw('p."VOBJECT" AS "nama_objek_pajak"'),
                DB::raw('CAST(p."FTARRIF" AS NUMERIC) AS "tarif_pph"'),
                DB::raw('CAST(p."FVALUE" AS NUMERIC) AS "nilai_pph"'),
                DB::raw('CAST(p."ITOTAL" AS NUMERIC) AS "grand_total"'),
                DB::raw('COALESCE(p."VREQUIRE_MATERAI_OCR", \'N\') AS "require_materai_ocr"'),
                DB::raw('COALESCE(p."VOCR_MATERAI_STATUS", \'-\') AS "ocr_materai_status"'),

                DB::raw('p."VINVOICE_FILE" AS "pdf_invoice"'),
                DB::raw('p."VTAX_INVOICE_FILE" AS "pdf_tax_invoice"'),
                DB::raw("'PO' AS \"transaction_category\""),
                DB::raw('p."VSTATUS_INVOICE" AS "status_invoice"'),
                DB::raw('p."DSUBMITTED" AS "date_submitted"'),
                DB::raw('p."DAPPROVED" AS "date_approved"'),
                DB::raw('p."VPYHSICAL_DOC_STATUS" AS "doc_status"'),

                DB::raw('grd."VMATERIAL_CODE" AS "part_number"'),
                DB::raw('grd."VDESCRIPTION" AS "description"'),
                DB::raw('grd."IQTY" AS "qty"'),
                DB::raw('grd."VPRICE" AS "price"'),
                DB::raw('grd."VCURRENCY" AS "curr"'),
                DB::raw('grd."VAMOUNT" AS "detail_subtotal"'),
                DB::raw('NULL AS "detail_dpp_nilai_lain"'),
                DB::raw('NULL AS "detail_ppn"'),
                DB::raw('d."FACTWM_TRHGR_NOTES_DGR" AS "aging_ap"'),
                DB::raw('p."VSTATUS" AS "VSTATUS"'),
                DB::raw('p."DMODI" AS "DMODI"'),
            ]);

        return $query;
    }


    public function getNonPOQuery()
    {
        return DB::table('FACTWM_TRHVERIFY_NON_PO as p')
            ->leftJoin('FACTWM_TRDVERIFY_NON_PO_DETAILS as dp', 'dp.TRHVERIFY_NON_PO_IID', '=', 'p.IID')
            ->leftJoin('FACTWM_MSHSUPPLIERS as fm', 'fm.VSUPPLIER_CODE', '=', 'p.VSUPPLIER_CODE')
            ->select([
                DB::raw('p."IID" AS "id"'),
                DB::raw('p."VBILLING_STATEMENT" AS "bs_no"'),
                DB::raw('p."VUNIQUE_CODE" AS "unique_code"'),
                DB::raw('p."VINV_NO_SUPPLIER" AS "inv_no"'),
                DB::raw('p."DINV_DATE" AS "inv_date"'),
                DB::raw('p."VTAX_NUMBER" AS "tax_inv_no"'),
                DB::raw('p."DTAX_DATE" AS "tax_inv_date"'),
                DB::raw('p."VSUPPLIER_CODE" AS "supplier_code"'),
                DB::raw('fm."VNAME" AS "supplier_name"'),
                DB::raw('fm."VNPWP" AS "npwp"'),
                DB::raw('fm."VNIK" AS "nik"'),

                // ✅ NUMERIC
                DB::raw('CAST(p."INET_AMOUNT" AS NUMERIC) AS "sub_total"'),
                DB::raw('CAST(p."VDPP" AS NUMERIC) AS "dpp_nilai_lain"'),
                DB::raw('CAST(p."VPPN" AS NUMERIC) AS "tarif_ppn"'),
                DB::raw('CAST(p."IDPP_PPH" AS NUMERIC) AS "dpp_pph"'),

                // 🔥 INI YANG KURANG
                DB::raw('p."VPPH" AS "pph_pasal"'),
                DB::raw('p."VOBJECT" AS "nama_objek_pajak"'),
                DB::raw('CAST(p."FTARRIF" AS NUMERIC) AS "tarif_pph"'),
                DB::raw('CAST(p."FVALUE" AS NUMERIC) AS "nilai_pph"'),
                DB::raw('CAST(p."ITOTAL" AS NUMERIC) AS "grand_total"'),
                DB::raw('\'N\' AS "require_materai_ocr"'),
                DB::raw('\'-\' AS "ocr_materai_status"'),

                DB::raw('p."VPDF_INVOICE" AS "pdf_invoice"'),
                DB::raw('p."VPDF_TAX" AS "pdf_tax_invoice"'),
                DB::raw("'NON PO' AS \"transaction_category\""),
                DB::raw('p."VSTATUS_INVOICE" AS "status_invoice"'),
                DB::raw('p."DSUBMITTED" AS "date_submitted"'),
                DB::raw('p."DAPPROVED" AS "date_approved"'),
                DB::raw('p."VPYHSICAL_DOC_STATUS" AS "doc_status"'),

                DB::raw('NULL AS "part_number"'),
                DB::raw('dp."VDESCRIPTION" AS "description"'),
                DB::raw('dp."IQTY" AS "qty"'),
                DB::raw('dp."IPRICE" AS "price"'),
                DB::raw('NULL AS "curr"'),
                DB::raw('dp."ITOTAL" AS "detail_subtotal"'),
                DB::raw('NULL AS "detail_dpp_nilai_lain"'),
                DB::raw('NULL AS "detail_ppn"'),
                DB::raw('dp."DCREA" AS "aging_ap"'),
                DB::raw('p."VSTATUS" AS "VSTATUS"'),
                DB::raw('p."DMODI" AS "DMODI"'),
            ]);
    }
}
