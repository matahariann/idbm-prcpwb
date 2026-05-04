<table>
    <thead>
        {{-- <tr>
            <th colspan="11" style="text-align: center">Actual Information</th>
            <th colspan="4" style="text-align: center">Supplier Identity</th>
            <th colspan="10" style="text-align: center">Taxation</th>
            <th colspan="8" style="text-align: center">Detail Barang</th>
            <th colspan="2" style="text-align: center">PDF</th>
        </tr> --}}
        <tr>
            {{-- <th style="color:white; background-color: purple">Action</th> --}}
            <th style="color:white; background-color: purple">STATUS INVOICE</th>
            <th style="color:white; background-color: purple">STATUS GRN</th>
            <th style="color:white; background-color: purple">TRANSACTION CATEGORY</th>
            <th style="color:white; background-color: purple">PHYSICAL DOC STATUS</th>
            <th style="color:white; background-color: purple">BS NO</th>
            <th style="color:white; background-color: purple">INV NO</th>
            <th style="color:white; background-color: purple">TAX INV NO</th>
            <th style="color:white; background-color: purple">GRN NO</th>
            <th style="color:white; background-color: purple">DELIVERY NO</th>
            <th style="color:white; background-color: purple">PO NUMBER</th>
            <th style="color:white; background-color: purple">VENDOR CODE</th>
            <th style="color:white; background-color: purple">VENDOR NAME</th>
            <th style="color:white; background-color: purple">NPWP</th>
            <th style="color:white; background-color: purple">NIK</th>

            <th style="color:white; background-color: purple">Part No</th>
            <th style="color:white; background-color: purple">Desc</th>
            <th style="color:white; background-color: purple">Qty</th>
            <th style="color:white; background-color: purple">Price</th>
            <th style="color:white; background-color: purple">Curr</th>
            <th style="color:white; background-color: purple">Subtotal</th>
            <th style="color:white; background-color: purple">DPP Nilai Lain</th>
            <th style="color:white; background-color: purple">PPN</th>

            <th style="color:white; background-color: purple">TARIF PPN</th>
            <th style="color:white; background-color: purple">AMOUNT BEFORE PPH</th>
            <th style="color:white; background-color: purple">PPH PASAL</th>
            <th style="color:white; background-color: purple">NAMA OBJEK PAJAK</th>
            <th style="color:white; background-color: purple">DPP PPH</th>
            <th style="color:white; background-color: purple">TARIF PPH</th>
            <th style="color:white; background-color: purple">NILAI PPH</th>
            <th style="color:white; background-color: purple">GRAND TOTAL</th>

            <th style="color:white; background-color: purple">GRN DATE</th>
            <th style="color:white; background-color: purple">DATE SUBMIT TO PORTAL</th>
            {{-- <th style="color:white; background-color: purple">PLAN PAYDATE</th> --}}
            <th style="color:white; background-color: purple">INV DATE</th>
            <th style="color:white; background-color: purple">TAX INV DATE</th>
            <th style="color:white; background-color: purple">DATE PHYSICAL SUBMIT</th>
            <th style="color:white; background-color: purple">AGING GRN</th>
            <th style="color:white; background-color: purple">AGING AP</th>
        </tr>
    </thead>
    <tbody>
        @if (count($data) > 0)
        @foreach ($data as $key => $item)
        <tr>
            <td>{{ $item->status_invoice }}</td>
            <td>{{ $item->status_grn }}</td>
            <td>{{ $item->transaction_category }}</td>
            <td>{{ $item->doc_status }}</td>
            <td>{{ $item->bs_no }}</td>
            <td>{{ $item->inv_no }}</td>
            <td>{{ $item->tax_inv_no }}</td>
            <td>{{ $item->grn_no }}</td>
            <td>{{ $item->delivery_no }}</td>
            <td>{{ $item->po_number }}</td>

            {{-- <td>{{ $item->date_submit_to_portal }}</td>
            <td>{{ $item->plan_paydate }}</td>
            <td>{{ $item->inv_date }}</td>
            <td>{{ $item->aging_ap }}</td>
            <td>{{ $item->tax_inv_date }}</td> --}}

            <td>{{ $item->supplier_code }}</td>
            <td>{{ $item->supplier_name }}</td>
            <td>{{ $item->npwp }}</td>
            <td>{{ $item->nik }}</td>

            <td>{{ $item->part_number }}</td>
            <td>{{ $item->description }}</td>
            <td class="text-center">{{ $item->qty }}</td>
            <td class="text-end">{{ (int) $item->price }}</td>
            <td>{{ $item->curr }}</td>
            {{-- <td class="text-end">{{ (int) $item->detail_subtotal }}</td> --}}
            {{-- <td class="text-end">{{ (int) $item->detail_dpp_nilai_lain }}</td> --}}
            {{-- <td class="text-end">{{ (int) $item->detail_ppn }}</td> --}}

            <td class="text-end">{{ (int) $item->sub_total }}</td>
            <td class="text-end">{{ (int) $item->dpp_nilai_lain }}</td>
            <td class="text-center">{{ (int) $item->detail_ppn }}%</td>
            <td class="text-center">{{ (int) $item->tarif_ppn }}%</td>

            <td class="text-end">{{ (int) $item->sub_total }}</td>
            <td>{{ $item->pph_pasal }}</td>
            <td>{{ $item->nama_objek_pajak }}</td>
            <td class="text-end">{{ (int) $item->dpp_pph }}</td>
            <td class="text-center">{{ (int) $item->tarif_pph }}%</td>
            <td class="text-end">{{ (int) $item->nilai_pph }}</td>
            <td class="text-end fw-bold">{{ (int) $item->grand_total }}</td>

            <!-- DETAIL -->
            <td>{{ $item->grn_date }}</td>
            <td>{{ $item->date_submitted }}</td>
            {{-- <td>{{ $item->plan_paydate }}</td> --}}
            <td>{{ $item->inv_date }}</td>
            <td>{{ $item->tax_inv_date }}</td>
            <td>{{ $item->date_approved }}</td>
            <td>{{ $item->aging_grn }}</td>
            <td>{{ $item->aging_ap }}</td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
