<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Billing Statement</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        .header {
            width: 100%;
        }

        .header td {
            vertical-align: top;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }

        .info-table td {
            padding: 2px 4px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 4px;
        }

        .table th {
            background: #f0f0f0;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .qr-center {
            text-align: center;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    @php
        $isTaxCodeV0 = strtoupper((string) ($verifyPo->VTAX_CODE ?? '')) === 'V0';
        $taxNumberDisplay = $isTaxCodeV0 ? '-' : $verifyPo->VTAX_NUMBER ?? '';
        $taxDateDisplay = $isTaxCodeV0
            ? '-'
            : ($verifyPo->DTAX_DATE
                ? \Carbon\Carbon::parse($verifyPo->DTAX_DATE)->format('Y-m-d')
                : '');
        $ppnLabel = $isTaxCodeV0 ? 'Purchase PPN (0%)' : 'Purchase PPN (12%)';
    @endphp

    {{-- halaman depan amplop --}}
    <div style="page-break-after: always;">
        <strong>PT Astemo Bekasi Manufacturing</strong><br><br>
        <table class="header">
            <tr>
                <td width="20%" class="qr-center">
                    <img src="data:image/png;base64,{{ base64_encode(
                        QrCode::format('png')->size(90)->generate($verifyPo->VBILLING_STATEMENT ?? ''),
                    ) }}"
                        width="90">
                    <div><strong>{{ $verifyPo->VBILLING_STATEMENT ?? '' }}</strong></div>
                </td>

                <td width="60%" class="title">
                    BILLING STATEMENT<br><br>
                </td>

                <td width="20%" class="qr-center">
                    <img src="data:image/png;base64,{{ base64_encode(
                        QrCode::format('png')->size(90)->generate($verifyPo->VUNIQUE_CODE ?? ''),
                    ) }}"
                        width="90">
                    <strong>{{ $verifyPo->VUNIQUE_CODE ?? '' }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <!-- HEADER -->
    <strong>PT Astemo Bekasi Manufacturing</strong><br><br>
    <table class="header">
        <tr>
            <td width="20%" class="qr-center">
                <img src="data:image/png;base64,{{ base64_encode(
                    QrCode::format('png')->size(90)->generate($verifyPo->VBILLING_STATEMENT ?? ''),
                ) }}"
                    width="90">
                <div><strong>{{ $verifyPo->VBILLING_STATEMENT ?? '' }}</strong></div>
            </td>

            <td width="60%" class="title">
                BILLING STATEMENT<br><br>
            </td>

            <td width="20%" class="qr-center">
                <img src="data:image/png;base64,{{ base64_encode(
                    QrCode::format('png')->size(90)->generate($verifyPo->VUNIQUE_CODE ?? ''),
                ) }}"
                    width="90">
                <strong>{{ $verifyPo->VUNIQUE_CODE ?? '' }}</strong>
            </td>
        </tr>
    </table>

    <br>

    <!-- INFO -->
    <table width="100%">
        <tr>
            <td width="50%">
                <table class="info-table">
                    <tr>
                        <td>Billing Statement No</td>
                        <td>:</td>
                        <td>{{ $verifyPo->VBILLING_STATEMENT ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>Supplier No</td>
                        <td>:</td>
                        <td>{{ $verifyPo->VSUPPLIER_CODE ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>Supplier Name</td>
                        <td>:</td>
                        <td>{{ $verifyPo->supplier?->VNAME ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>Currency</td>
                        <td>:</td>
                        <td>IDR</td>
                    </tr>
                </table>
            </td>
            <td width="50%">
                <table class="info-table">
                    <tr>
                        <td>Supplier Invoice No</td>
                        <td>:</td>
                        <td>{{ $verifyPo->VINV_NO_SUPPLIER ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>Supplier Invoice Date</td>
                        <td>:</td>
                        <td>{{ date('Y-m-d', strtotime($verifyPo->DINV_DATE)) ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>Tax Invoice No</td>
                        <td>:</td>
                        <td>{{ $taxNumberDisplay }}</td>
                    </tr>
                    <tr>
                        <td>Tax Invoice Date</td>
                        <td>:</td>
                        <td>{{ $taxDateDisplay }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TABLE -->
    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Material Code</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Uom</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @if (count($verifyPo->details) > 0)
                @foreach ($verifyPo->details ?? [] as $key => $item)
                    <tr>
                        <td align="center">{{ $key + 1 }}</td>
                        <td>-</td>
                        <td>{{ $item->VDESCRIPTION ?? '' }}</td>
                        <td align="center">{{ $item->IQTY ?? '' }}</td>
                        <td align="center">{{ $item->VUOM ?? '' }}</td>
                        <td class="text-right">{{ number_format($item->ITOTAL ?? '', 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="5" width="80%" class="text-right">Purchase Net Amount</td>
                    <td width="20%" class="text-right">
                        {{ number_format((int) $verifyPo->INET_AMOUNT ?? '', 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-right">{{ $ppnLabel }}</td>
                    <td class="text-right">
                        {{ number_format((int) $verifyPo->VPPN ?? '', 0, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td colspan="5" class="text-right"><strong>Purchase Gross Amount</strong></td>
                    <td class="text-right">
                        <strong>{{ number_format((int) $verifyPo->ITOTAL ?? '', 0, ',', '.') }}</strong>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <br>

    {{-- <!-- TOTAL -->
    <table class="table" width="100%">
        <tr>
            <td width="80%" class="text-right">Purchase Nett Amount</td>
            <td width="20%" class="text-right">199,443,680</td>
        </tr>
        <tr>
            <td class="text-right">Purchase PPN (11%)</td>
            <td class="text-right">21,938,805</td>
        </tr>
        <tr>
            <td class="text-right"><strong>Purchase Gross Amount</strong></td>
            <td class="text-right"><strong>221,382,485</strong></td>
        </tr>
    </table> --}}

</body>

</html>
