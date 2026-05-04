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

        .bg-gray-50 {
            background-color: var(--bs-gray-50);
        }

        .readonly-locked,
        .readonly-locked.form-control,
        input.readonly-locked {
            background-color: var(--bs-gray-200) !important;
        }

        .readonly-locked:focus {
            background-color: var(--bs-gray-200) !important;
            box-shadow: none !important;
            background-image: none !important;
        }

        input.readonly-locked:-webkit-autofill,
        input.readonly-locked:-webkit-autofill:hover,
        input.readonly-locked:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px var(--bs-gray-200) inset !important;
            box-shadow: 0 0 0 1000px var(--bs-gray-200) inset !important;
            -webkit-text-fill-color: var(--bs-body-color) !important;
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
                <button type="button" class="accordion-button" data-bs-toggle="collapse"
                    data-bs-target="#form-purchase-content" aria-expanded="true" aria-controls="form-purchase-content">

                    <span class="fs-5">PURCHASE INFORMATION</span>
                </button>
            </h2>
            <div class="accordion-collapse collapse show" id="form-purchase-content">
                <div class="accordion-body">
                    <form id="non-po-form" data-mode="{{ $nonPo->exists ? 'update' : 'create' }}"
                        data-endpoint="{{ $nonPo->exists ? route('verify-non-po.update', $nonPo->IID) : route('verify-non-po.store') }}"
                        autocomplete="off" enctype="multipart/form-data">
                        @csrf

                        @if ($nonPo->exists)
                            @method('PUT')
                        @endif

                        @include('modules.FACTWM.FACTWM02.FACTWMF008.partials.form._purchase-information', [
                            'nonPo' => $nonPo,
                            'npwp_supplier' => $npwp_supplier,
                            'npwp_idbm_match' => $npwp_idbm_match,
                            'list_pph_pasal' => $list_pph_pasal,
                            'action' => $action,
                        ])

                    </form>
                </div>
            </div>
        </div>

        <div class="accordion-item mb-3">
            <h2 class="accordion-header fs-5">
                <button type="button" class="accordion-button" data-bs-toggle="collapse"
                    data-bs-target="#form-detail-purchase-content" aria-expanded="true"
                    aria-controls="form-detail-purchase-content">

                    <span class="fs-5">DETAIL PURCHASE INFORMATION</span>
                </button>
            </h2>
            <div class="accordion-collapse collapse show" id="form-detail-purchase-content">
                <div class="accordion-body">
                    @include('modules.FACTWM.FACTWM02.FACTWMF008.partials.form._items', [
                        'nonPo' => $nonPo,
                        'verify_non_po_list_unit' => $verify_non_po_list_unit,
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
            <div class="accordion-collapse collapse show" id="summary-purchase-content">
                <div class="accordion-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Purchase Net Amount</label>
                                <input class="form-control bg-light" id="net_amount"
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
                                <input type="text" class="form-control bg-light" id="dpp_lain" name="dpp_lain"
                                    value="{{ number_format((int) ($nonPo->VDPP ?? 0), 0, ',', '.') }}" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">PPN</label>
                                <input type="text" class="form-control bg-light" id="ppn" name="ppn"
                                    value="{{ number_format((int) ($nonPo->VPPN ?? 0), 0, ',', '.') }}" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">DPP PPh</label>
                                <input type="text" class="form-control bg-light" id="dpp-pph" name="dpp_pph"
                                    value="{{ number_format((int) ($nonPo->IDPP_PPH ?? 0), 0, ',', '.') }}" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tarif (%)</label>
                                <input type="text" class="form-control bg-light" id="tarif" name="tarif"
                                    value="{{ number_format((int) ($nonPo->FTARRIF ?? 0), 0, ',', '.') }}" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nilai PPh</label>
                                <input type="text" class="form-control bg-light" id="nilai-pph" name="nilai_pph"
                                    value="{{ number_format((int) ($nonPo->FVALUE ?? 0), 0, ',', '.') }}" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Grand Amount</label>
                                <input type="text" class="form-control bg-light" id="grand_total" name="grand_total"
                                    value="{{ number_format((int) ($nonPo->ITOTAL ?? 0), 0, ',', '.') }}" readonly>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" id="reset-form" class="btn btn-secondary">
                                    <span>Cancel</span>
                                </button>
                                <button type="submit" form="non-po-form" id="next-button" class="btn btn-primary">
                                    Next
                                </button>
                                <button class="btn btn-primary d-none" type="button"
                                    id="escalated-button">Escalated</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('modules.FACTWM.FACTWM02.FACTWMF008.partials._upload-requirement-modal')
    @endsection

    @section('page-script')
        <script>
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
                ocr_render_dpi_start: 160,
                ocr_render_dpi_step: 20,
                existing_other_files: @json($existingOtherFiles ?? []),
            };
        </script>
        @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/js/pages/factwm/factwm02/verify-non-po/verify-non-po-form.js'])
    @endsection
