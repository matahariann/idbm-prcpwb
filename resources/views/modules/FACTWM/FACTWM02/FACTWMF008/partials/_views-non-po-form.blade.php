@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Verify Non PO')
@section('page-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
    <style>
        .select2-container {
            width: 100% !important;
        }

        .select2-dropdown {
            max-width: 100vw !important;
            overflow-x: hidden;
        }

        body {
            overflow-x: hidden;
        }
    </style>
@endsection

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">FACTWM</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">Transaction</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('verify-non-po.index') }}">Verify NON PO</a>
            </li>
            <li class="breadcrumb-item active">Form</li>
        </ol>
    </nav>

    <div class="accordion" id="tax">
        <div class="accordion-item mb-3">
            <h2 class="accordion-header fs-5">
                <button type="button" class="accordion-button" data-bs-toggle="collapse" data-bs-target="#purchase-content"
                    aria-expanded="true" aria-controls="purchase-content">

                    <span class="fs-5">PURCHASE INFORMATION</span>
                </button>
            </h2>
            <div class="accordion-collapse collapse show" data-bs-parent="#tax" id="purchase-content">
                <div class="accordion-body">
                    <form id="non-po-form" data-mode="{{ $nonPo->exists ? 'update' : 'create' }}"
                        data-endpoint="{{ $nonPo->exists ? route('verify-non-po.update', $nonPo->IID) : route('verify-non-po.store') }}"
                        enctype="multipart/form-data">
                        @csrf

                        @if ($nonPo->exists)
                            @method('PUT')
                        @endif

                        @include('modules.FACTWM.FACTWM02.FACTWMF008.partials.form._purchase-information', [
                            'nonPo' => $nonPo,
                            'npwp_supplier' => $npwp_supplier,
                            'npwp_idbm_match' => $npwp_idbm_match,
                            'action' => $action,
                        ])

                    </form>
                </div>
            </div>
        </div>

        <div class="accordion-item mb-3">
            <h2 class="accordion-header fs-5">
                <button type="button" class="accordion-button" data-bs-toggle="collapse"
                    data-bs-target="#detail-purchase-content" aria-expanded="true" aria-controls="detail-purchase-content">

                    <span class="fs-5">DETAIL PURCHASE INFORMATION</span>
                </button>
            </h2>
            <div class="accordion-collapse collapse show" data-bs-parent="#tax" id="detail-purchase-content">
                <div class="accordion-body">
                    @include('modules.FACTWM.FACTWM02.FACTWMF008.partials.form._items', [
                        'nonPo' => $nonPo,
                        // 'options' => $options,
                    ])
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header fs-5">
                <button type="button" class="accordion-button" data-bs-toggle="collapse"
                    data-bs-target="#summary-purchase-content" aria-expanded="true"
                    aria-controls="summary-purchase-content">

                    <span class="fs-5">SUMMARY</span>
                </button>
            </h2>
            <div class="accordion-collapse collapse show" data-bs-parent="#tax" id="summary-purchase-content">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Purchase Net Amount</label>
                                <input class="form-control bg-light" id="summary-subtotal"
                                    value="{{ number_format((int) $nonPo->INET_AMOUNT, 0, ',', '.') }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">NPWP IDBM Match</label>
                                <input class="form-control bg-light" id="npwp_idbm_match" name="npwp_idbm_match"
                                    value="{{ $npwp_idbm_match }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">DPP Nilai Lain</label>
                                <input type="text" class="form-control" id="dpp-lain" name="dpp_lain"
                                    value="{{ number_format((int) ($nonPo->VDPP ?? 0), 0, ',', '.') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">PPN</label>
                                <input type="text" class="form-control" id="ppn" name="ppn"
                                    value="{{ number_format((int) ($nonPo->VPPN ?? 0), 0, ',', '.') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">DPP PPh</label>
                                <input type="text" class="form-control" id="dpp-pph" name="dpp_pph"
                                    value="{{ number_format((int) ($nonPo->IDPP_PPH ?? 0), 0, ',', '.') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tarif</label>
                                <input type="text" class="form-control" id="tarif" name="tarif"
                                    value="{{ number_format((int) ($nonPo->FTARRIF ?? 0), 0, ',', '.') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nilai PPh</label>
                                <input type="text" class="form-control" id="nilai-pph" name="nilai_pph"
                                    value="{{ number_format((int) ($nonPo->FVALUE ?? 0), 0, ',', '.') }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Grand Amount</label>
                                <div class="form-control bg-light fw-bold" id="summary-grand-total">
                                    {{ number_format((int) ($nonPo->ITOTAL ?? 0), 0, ',', '.') }}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Requirements Modal -->
    @include('modules.FACTWM.FACTWM02.FACTWMF008.partials._upload-requirement-modal')
@endsection

@section('page-script')
    <script type="module">
        window.APP_CONFIG = {
            ppn: @json($ppn),
            rumus_dpp: @json($rumus_dpp),
            limit_eskalated: @json($limit_eskalated),
            pkp_supplier: @json($pkp_supplier),
            verify_non_po_list_unit: @json($verify_non_po_list_unit),
            id: @json($nonPo->IID ?? null),
            pph: @json($nonPo->VPPH ?? null),
            object: @json($nonPo->VOBJECT ?? null),
            dpp_pph: @json($nonPo->IDPP_PPH ?? 0),
            tarrif: @json($nonPo->FTARRIF ?? 0),
        };

        function setReadOnlyMode() {
            // Text inputs, textarea
            $('input:not([type=hidden]), textarea').prop('readonly', true);

            // Select, checkbox, radio, file
            $('select, input[type=checkbox], input[type=radio], input[type=file]')
                .prop('disabled', true);

            // Buttons
            $('button').prop('disabled', true);

            // Prevent links
            // $('a').on('click', function(e) {
            //     e.preventDefault();
            // });

            // Optional visual hint
            $('button, input, select, textarea').addClass('readonly-mode');

            $('input').addClass('bg-light');
        }

        // call when needed
        setReadOnlyMode();
    </script>



@endsection
