@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Verify PO')

@section('page-style')
    <style>
        .bg-gray-50 {
            background-color: var(--bs-gray-50);
        }
    </style>
@endsection

@section('content')
    @php
        $isTaxCodeV0 = strtoupper((string) ($verifyPo->VTAX_CODE ?? '')) === 'V0';
        $taxNumberDisplay = $isTaxCodeV0 ? '-' : $verifyPo->VTAX_NUMBER ?? '-';
        $taxDateDisplay = $isTaxCodeV0
            ? '-'
            : ($verifyPo->DTAX_DATE
                ? \Carbon\Carbon::parse($verifyPo->DTAX_DATE)->format('d-m-Y')
                : '-');
    @endphp
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-md-6">
                    <label for="" class="form-label">No Invoice Supplier</label>
                    <input type="text" class="form-control bg-gray-50" id="invoice-number"
                        value="{{ $verifyPo->VINV_NO_SUPPLIER }}" readonly>
                </div>
                <div class="col-12 col-md-6">
                    <label for="" class="form-label">Tax Invoice Number</label>
                    <input type="text" class="form-control bg-gray-50" id="tax-invoice-number"
                        value="{{ $taxNumberDisplay }}" readonly>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6">
                    <label for="" class="form-label">Invoice Date</label>
                    <input type="text" class="form-control bg-gray-50" id="invoice-date"
                        value="{{ $verifyPo->DINV_DATE->format('d-m-Y') }}" readonly>
                </div>
                <div class="col-12 col-md-6">
                    <label for="" class="form-label">Tax Invoice Date</label>
                    <input type="text" class="form-control bg-gray-50" value="{{ $taxDateDisplay }}" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-md-4">
                    <table class="table table-bordered">
                        <colgroup>
                            <col style="width: 40%">
                            <col style="width: 5%">
                            <col style="width: 55%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th colspan="3" class="text-center">Manual Data Input Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="height: 52px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Date</td>
                                <td>:</td>
                                <td> {{ now()->format('d-m-Y') }}</td>
                            </tr>
                            <tr style="height: 45px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Subtotal</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->INET_AMOUNT ?? '0', 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>DPP Nilai Lain</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->VDPP ?? '0', 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>PPN</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->VPPN ?? '0', 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>DPP PPh</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->IDPP_PPH ?? '0', 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Nama Objek</td>
                                <td>:</td>
                                <td>{{ $verifyPo->VPPH }} - {{ $verifyPo->VOBJECT }}</td>
                            </tr>
                            <tr>
                                <td>Tarif</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->FTARRIF ?? '0', 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Amount PPH</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->FVALUE ?? '0', 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Grand Amount</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->ITOTAL ?? '0', 0, ',', '.') }}</td>
                            </tr>
                            {{-- <tr>
                                <td>GRN</td>
                                <td>:</td>
                                <td>{{ count($verifyPo->VGR_NUMBER_IID) }}</td>
                            </tr> --}}
                            {{-- <tr>
                                <td>DPP Nilai Lain</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->IDPP ?? '0', 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Nama Objek</td>
                                <td>:</td>
                                <td>{{ $verifyPo->VOBJECT ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td>Tarif</td>
                                <td>:</td>
                                <td>{{ $verifyPo->FTARRIF ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td>Grand Amount</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->ITOTAL ?? '0', 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>GRN</td>
                                <td>:</td>
                                <td>{{ count($verifyPo->VGR_NUMBER_IID) }}</td>
                            </tr> --}}
                        </tbody>
                    </table>
                </div>
                <div class="col-12 col-md-4">
                    <table class="table table-bordered">
                        <colgroup>
                            <col style="width: 40%">
                            <col style="width: 5%">
                            <col style="width: 50%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th colspan="3" class="text-center">Invoice Verification Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>File</td>
                                <td>:</td>
                                <td>
                                    @if (!empty($verifyPo->VPDF_INVOICE))
                                        <a href="{{ route('verify-non-po.download', [$verifyPo->IID, 'invoice']) }}"
                                            target="_blank">
                                            <i class="menu-icon ti tabler-file-type-pdf"></i>
                                        </a>
                                        {{-- <a href="{{ asset('storage/' . $verifyPo->VPDF_INVOICE) }}" target="__blank">
                                            <i class="menu-icon ti tabler-file-type-pdf"></i>
                                        </a> --}}
                                    @endif
                                </td>
                                {{-- <td>
                                    <span class="text-success">OK</span>
                                </td> --}}
                            </tr>
                            <tr>
                                <td>Invoice Date</td>
                                <td>:</td>
                                <td>{{ $verifyPo->DINV_DATE->format('d-m-Y') ?? '-' }}</td>
                                {{-- <td>
                                    <span class="text-success">OK</span>
                                </td> --}}
                            </tr>
                            <tr>
                                <td>No Invoice</td>
                                <td>:</td>
                                <td>{{ $verifyPo->VINV_NO_SUPPLIER }}</td>
                            </tr>
                            <tr>
                                <td>Subtotal</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->INET_AMOUNT ?? '0', 0, ',', '.') }}</td>
                                {{-- <td>
                                    <span class="text-success">OK</span>
                                </td> --}}
                            </tr>
                            <tr style="height: 46px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>PPN</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->VPPN ?? '0', 0, ',', '.') }}</td>
                            </tr>
                            <tr style="height: 46px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr style="height: 46px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr style="height: 46px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr style="height: 46px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr style="height: 46px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            {{-- <tr>
                                <td>VAT</td>
                                <td>:</td>
                                <td>-</td>
                                <td>
                                    <span class="text-success">OK</span>
                                </td>
                            </tr>
                            <tr>
                                <td>Amount PPh</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->VPPN ?? '0', 0, ',', '.') }}</td>
                                <td>
                                    <span class="text-success">OK</span>
                                </td>
                            </tr>
                            <tr>
                                <td>Grand Amount</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->ITOTAL ?? '0', 0, ',', '.') }}</td>
                                <td>
                                    <span class="text-success">OK</span>
                                </td>
                            </tr> --}}
                        </tbody>
                        {{-- <tfoot>
                            <tr>
                                <th colspan="4" class="text-center">
                                    <span class="badge bg-label-success">Verified</span>
                                </th>
                            </tr>
                        </tfoot> --}}
                    </table>
                </div>
                <div class="col-12 col-md-4">
                    <table class="table table-bordered">
                        <colgroup>
                            <col style="width: 40%">
                            <col style="width: 5%">
                            <col style="width: 50%">
                            {{-- <col style="width: 5%"> --}}
                        </colgroup>
                        <thead>
                            <tr>
                                <th colspan="3" class="text-center">Tax Invoice Verification Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>File</td>
                                <td>:</td>
                                <td>
                                    @if (!$isTaxCodeV0 && !empty($verifyPo->VPDF_TAX))
                                        <a href="{{ route('verify-non-po.download', [$verifyPo->IID, 'tax']) }}"
                                            target="_blank">
                                            <i class="menu-icon ti tabler-file-type-pdf"></i>
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>Tax Date</td>
                                <td>:</td>
                                <td>{{ $taxDateDisplay }}</td>
                            </tr>
                            <tr>
                                <td>Tax No</td>
                                <td>:</td>
                                <td>{{ $taxNumberDisplay }}</td>
                            </tr>
                            <tr>
                                <td>Tax Code</td>
                                <td>:</td>
                                <td>{{ $verifyPo->VTAX_CODE ?? '-' }}</td>
                            </tr>
                            <tr style="height: 46px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>PPN</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->VPPN ?? '0', 0, ',', '.') }}</td>
                            </tr>
                            {{-- @if ($isTaxCodeV0)
                                <tr>
                                    <td>Info</td>
                                    <td>:</td>
                                    <td>Tax validation skipped because Tax Code is V0</td>
                                </tr>
                            @endif --}}
                            <tr style="height: 46px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr style="height: 46px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr style="height: 46px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr style="height: 46px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr style="height: 46px;">
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            {{-- <tr>
                                <td>DPP Nilai Lain</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->IDPP ?? '0', 0, ',', '.') }}</td>
                                <td>
                                    <span class="text-success">OK</span>
                                </td>
                            </tr>
                            <tr>
                                <td>PPn</td>
                                <td>:</td>
                                <td>{{ number_format($verifyPo->IPPN ?? '0', 0, ',', '.') }}</td>
                                <td>
                                    <span class="text-success">OK</span>
                                </td>
                            </tr> --}}
                        </tbody>
                        {{-- <tfoot>
                            <tr>
                                <th colspan="4" class="text-center">
                                    <span class="badge bg-label-success">Verified</span>
                                </th>
                            </tr>
                        </tfoot> --}}
                    </table>
                </div>
            </div>
            <hr>
            <div class="row mt-3">
                <div class="col-12 col-md-4">
                    <table class="table table-bordered">
                        <colgroup>
                            <col style="width: 5%">
                            <col style="width: 85%">
                            <col style="width: 10%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th colspan="3" class="text-center">Supplier Doc (Review File)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($otherFiles as $other)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>
                                        @php
                                            $filename = basename($other->VPATH);
                                            $short =
                                                strlen($filename) > 15 ? substr($filename, 0, 15) . '...' : $filename;
                                        @endphp

                                        {{ $short }}</td>
                                    </td>
                                    <td>
                                        <a href="{{ route('verify-non-po.download-other-file', $other->IID) }}"
                                            target="_blank">
                                            <i class="menu-icon ti tabler-file-type-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="col-12 col-md-8">
                    <div class="mb-3">
                        <label for="notes" class="form-label d-flex justify-content-between">
                            <span>Note</span>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="hyperlink">
                                <label for="hyperlink" class="form-check-label">I have read and agree to the applicable
                                    Terms and Condition</label>
                            </div>
                        </label>
                        <textarea class="form-control" name="notes" id="notes" cols="200" rows="5"></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <input type="hidden" name="IID" id="IID" value="{{ $verifyPo->IID }}">
                        {{-- <a href="{{ url('/FACTWM/ts/verify-non-po/preview-pdf/' . $verifyPo->IID) }}" target="_blank"
                            class="btn btn-outline-secondary me-2">
                            Print PDF
                        </a> --}}
                        <button class="btn btn-primary" id="submit-final-preview" disabled>Submit</button>
                    </div>
                    <hr class="mt-3 mb-0">
                </div>
            </div>
        </div>
    </div>

    @include('modules.FACTWM.FACTWM02.FACTWMF008.partials._submitted-modal')
    @include('modules.FACTWM.FACTWM02.FACTWMF008.partials._term-condition-modal')
@endsection

@section('page-script')
    <script>
        window.APP_CONFIG = {
            payload: @json($payload),
        };
    </script>
    @vite(['resources/js/pages/factwm/factwm02/verify-non-po/final-form-preview.js'])
@endsection
