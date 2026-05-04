<table class="table table-bordered">
    <thead>
        <tr>
            <th>Status Invoice</th>
            <th>Billing Statement</th>
            <th>Unique Code</th>
            <th>INV No Supplier</th>
            <th>INV Date</th>
            <th>Total DPP PPH</th>
            <th>Amount</th>
            <th>PPH</th>
            <th>DPP</th>
            <th>PPN</th>
            <th>Tax Number</th>
            <th>Tax Date</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th>PDF Tax</th>
            <th>PDF Invoice</th>
            <th>QR Code</th>
            <th>Description</th>
            <th>Quantity</th>
            <th>UOM</th>
            <th>Price</th>
            <th>Amount Per Item</th>
            <th>Submitted Date</th>
            <th>Approved Date</th>
            <th>Plan Pay Date</th>
            <th>Created At</th>
            <th>Modified At</th>
        </tr>
    </thead>
    <tbody>
        @if (count($data) > 0)
            @foreach ($data as $item)
                <tr>
                    <td>{{ $item->VSTATUS_INVOICE }}</td>
                    <td>{{ $item->VBILLING_STATEMENT }}</td>
                    <td>{{ $item->VUNIQUE_CODE }}</td>
                    <td>{{ $item->VINV_NO_SUPPLIER }}</td>
                    <td>{{ $item->DINV_DATE }}</td>
                    <td>{{ $item->IDPP_PPH }}</td>
                    <td>{{ $item->INET_AMOUNT }}</td>
                    <td>{{ $item->VPPH }}</td>
                    <td>{{ $item->VDPP }}</td>
                    <td>{{ $item->VPPN }}</td>
                    <td>{{ $item->VTAX_NUMBER }}</td>
                    <td>{{ $item->DTAX_DATE }}</td>
                    <td>{{ $item->ITOTAL }}</td>
                    <td>{{ $item->VSTATUS }}</td>
                    <td>{{ $item->PDF_TAX }}</td>
                    <td>{{ $item->PDF_INVOICE }}</td>
                    <td>{{ $item->VQRCODE }}</td>
                    <td>{{ $item->VDESCRIPTION }}</td>
                    <td>{{ $item->IQTY }}</td>
                    <td>{{ $item->VUOM }}</td>
                    <td>{{ $item->IPRICE }}</td>
                    <td>{{ $item->ITOTAL }}</td>
                    <td>{{ $item->DSUBMITTED }}</td>
                    <td>{{ $item->DAPPROVED }}</td>
                    <td>{{ $item->DPLAN_PAY_DATE }}</td>
                    <td>{{ $item->DCREA }}</td>
                    <td>{{ $item->DMODI }}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
