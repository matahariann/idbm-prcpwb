<table>
    <thead>
        <tr>
            <th colspan="10" style="text-align: center">Actual Information</th>
            <th colspan="4" style="text-align: center">Supplier Identity</th>
            <th colspan="12" style="text-align: center">Taxation</th>
            <th colspan="8" style="text-align: center">Detail Barang</th>
            <th colspan="2" style="text-align: center">PDF</th>
        </tr>
        <tr>
            {{-- <th style="color:white; background-color: purple">Action</th> --}}
            <th style="color:white; background-color: purple">Status Invoice</th>
            <th style="color:white; background-color: purple">Date Approved</th>
            <th style="color:white; background-color: purple">Physical Doc Submission Status</th>
            <th style="color:white; background-color: purple">Transaction Category</th>
            <th style="color:white; background-color: purple">BS No</th>
            <th style="color:white; background-color: purple">Inv No</th>
            <th style="color:white; background-color: purple">Tax Inv No</th>
            <th style="color:white; background-color: purple">Date Submit to Portal</th>
            {{-- <th style="color:white; background-color: purple">Plan Paydate</th> --}}
            <th style="color:white; background-color: purple">Inv Date</th>
            <th style="color:white; background-color: purple">Tax Inv Date</th>

            <th style="color:white; background-color: purple">Vendor Code</th>
            <th style="color:white; background-color: purple">Vendor Name</th>
            <th style="color:white; background-color: purple">NPWP</th>
            <th style="color:white; background-color: purple">NIK</th>

            <th style="color:white; background-color: purple">Subtotal</th>
            <th style="color:white; background-color: purple">DPP Nilai Lain</th>
            <th style="color:white; background-color: purple">Tarif PPN</th>
            <th style="color:white; background-color: purple">Amount Before PPh</th>
            <th style="color:white; background-color: purple">PPH Pasal</th>
            <th style="color:white; background-color: purple">Nama Objek Pajak</th>
            <th style="color:white; background-color: purple">DPP PPh</th>
            <th style="color:white; background-color: purple">Tarif</th>
            <th style="color:white; background-color: purple">Nilai PPh</th>
            <th style="color:white; background-color: purple">Grand Total</th>
            <th style="color:white; background-color: purple">Require Materai OCR</th>
            <th style="color:white; background-color: purple">OCR Materai Status</th>

            <th style="color:white; background-color: darkolivegreen">Part No</th>
            <th style="color:white; background-color: darkolivegreen">Desc</th>
            <th style="color:white; background-color: darkolivegreen">Qty</th>
            <th style="color:white; background-color: darkolivegreen">Price</th>
            <th style="color:white; background-color: darkolivegreen">Curr</th>
            <th style="color:white; background-color: darkolivegreen">Subtotal</th>
            <th style="color:white; background-color: darkolivegreen">DPP Nilai Lain</th>
            <th style="color:white; background-color: darkolivegreen">PPN</th>
            <th style="color:white; background-color: darkolivegreen">Aging AP</th>

            <th style="color:white; background-color: purple">PDF Invoice</th>
            <th style="color:white; background-color: purple">PDF Tax Invoice</th>
        </tr>
    </thead>
    <tbody>
        @if (count($data) > 0)
        @foreach ($data as $key => $item)
        <tr>
            <td>{{ $item->status_invoice }}</td>
            <td>{{ $item->date_approved }}</td>
            <td>{{ $item->doc_status }}</td>
            <td>{{ $item->transaction_category }}</td>
            <td>{{ $item->bs_no }}</td>
            <td>{{ $item->inv_no }}</td>
            <td>{{ $item->tax_inv_no }}</td>
            <td>{{ $item->date_submitted }}</td>
            {{-- <td>{{ $item->plan_paydate }}</td> --}}
            <td>{{ $item->inv_date }}</td>
            <td>{{ $item->tax_inv_date }}</td>

            <td>{{ $item->supplier_code }}</td>
            <td>{{ $item->supplier_name }}</td>
            <td>{{ $item->npwp }}</td>
            <td>{{ $item->nik }}</td>

            <td class="text-end">{{ (int) $item->sub_total }}</td>
            <td class="text-end">{{ (int) $item->dpp_nilai_lain }}</td>
            <td class="text-center">{{ (int) $item->tarif_ppn }}%</td>
            <td class="text-end">{{ (int) $item->sub_total }}</td>
            <td>{{ $item->pph_pasal }}</td>
            <td>{{ $item->nama_objek_pajak }}</td>
            <td class="text-end">{{ (int) $item->dpp_pph }}</td>
            <td class="text-center">{{ (int) $item->tarif_pph }}%</td>
            <td class="text-end">{{ (int) $item->nilai_pph }}</td>
            <td class="text-end fw-bold">{{ (int) $item->grand_total }}</td>
            <td class="text-center">{{ $item->require_materai_ocr ?? '-' }}</td>
            <td class="text-center">{{ $item->ocr_materai_status ?? '-' }}</td>

            <!-- DETAIL -->
            <td>{{ $item->part_number }}</td>
            <td>{{ $item->description }}</td>
            <td class="text-center">{{ $item->qty }}</td>
            <td class="text-end">{{ (int) $item->price }}</td>
            <td>{{ $item->curr }}</td>
            <td class="text-end">{{ (int) $item->detail_subtotal }}</td>
            <td class="text-end">{{ (int) $item->detail_dpp_nilai_lain }}</td>
            <td class="text-end">{{ (int) $item->detail_ppn }}</td>
            <td>{{ $item->aging_ap }}</td>

            <!-- PDF -->
            <td class="text-center">
                @if ($item->pdf_invoice)
                {{ asset('storage/' . $item->pdf_invoice) }}
                @else
                -
                @endif
            </td>
            <td class="text-center">
                @if ($item->pdf_tax_invoice)
                {{ asset('storage/' . $item->pdf_tax_invoice) }}
                @else
                -
                @endif
            </td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
