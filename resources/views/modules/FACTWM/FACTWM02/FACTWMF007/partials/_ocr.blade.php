@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Verify PO')

@section('page-style')
    @vite(['resources/assets/vendor/scss/uploader.scss']);
    <style>
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

        .prefix {
            height: 38px;
            font-size: 20px;
        }

        .fixed-input {
            /* samakan dengan default bootstrap */
            font-size: 14px;
        }
    </style>
@endsection

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">3 Way Matching</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">Transaction</a>
            </li>
            <li class="breadcrumb-item active">Verify PO</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-datatable">
            <table class="table" id="ocr-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Ref Type</th>
                        <th>Return Reference</th>
                        <th>GRN Number</th>
                        <th>GRN Date</th>
                        <th>Delivery Number</th>
                        <th>Po Number</th>
                        <th>Amount</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="accordion mt-3" id="tax">
        <form id="ocr-form" enctype="multipart/form-data">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button type="button" class="accordion-button" data-bs-toggle="collapse" data-bs-target="#tax-content"
                        aria-expanded="true" aria-controls="tax-content">

                        <div class="container-fluid p-0">
                            <div class="row w-100 m-0">
                                <div class="col-12 col-md-6 fw-bold fs-5 d-none d-md-block">Tax - Invoice Information from
                                    GRN
                                </div>
                                <div class="col-12 col-md-6 fw-bold fs-5 d-none d-md-block">Witholding Tax</div>
                                <div class="p-0 fw-bold fs-5 d-md-none">Invoice Information</div>
                            </div>

                            <div class="border-bottom w-100 mt-2 d-none d-md-block"></div>
                        </div>

                    </button>
                </h2>

                <div class="accordion-collapse collapse show" data-bs-parent="#tax" id="tax-content">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="fw-bold fs-6 d-md-none mt-3 mb-3 text-dark text-center">
                                    Tax - Invoice Information from GRN

                                    <div class="border-bottom w-100 mt-2 d-md-none"></div>
                                </div>

                                <label for="net-amount" class="form-label">Purchase Net Amount</label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-end bg-gray-50" id="net-amount"
                                        name="net-amount" value="0" readonly>
                                    <span class="input-group-text">
                                        <span class="badge badge-center rounded-pill text-bg-danger d-none"
                                            id="net-amount-status">
                                            <i class="ti tabler-x"></i>
                                        </span>
                                    </span>
                                </div>

                                <label for="dpp" class="form-label">DPP Nilai Lain</label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-end bg-gray-50" id="dpp"
                                        name="dpp" value="0" readonly>
                                    <span class="input-group-text">
                                        <span class="badge badge-center rounded-pill text-bg-danger d-none"
                                            id="dpp-nilai-lain-status">
                                            <i class="ti tabler-x"></i>
                                        </span>
                                    </span>
                                </div>

                                <label for="ppn" class="form-label">PPn</label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-end bg-gray-50" id="ppn"
                                        name="ppn" value="0" readonly>
                                    <span class="input-group-text">
                                        <span class="badge badge-center rounded-pill text-bg-danger d-none" id="ppn-status">
                                            <i class="ti tabler-x"></i>
                                        </span>
                                    </span>
                                </div>

                                <label for="npwp" class="form-label">NPWP IDBM Match</label>
                                <div class="input-group">
                                    <input type="text" class="form-control text-end bg-gray-50" name="npwp_idbm"
                                        id="npwp_idbm" value="" readonly>
                                    <span class="input-group-text">
                                        <span class="badge badge-center rounded-pill text-bg-danger d-none"
                                            id="npwp-idbm-status">
                                            <i class="ti tabler-x"></i>
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="fw-bold fs-6 d-md-none mt-3 mb-3 text-dark text-center">
                                    Witholding Tax

                                    <div class="border-bottom w-100 mt-2 d-md-none"></div>
                                </div>

                                <label for="pph" class="form-label">PPh Pasal (PPh23, PPh22, PPh4-02)</label>
                                {{-- list pph pasal --}}
                                @php
                                    // dd($item);
                                    $options = [];
                                    if (!empty($config_list_pph_pasal)) {
                                        $options = explode(',', $config_list_pph_pasal);
                                    }
                                    // $options = ['-', 'Pcs', 'Box', 'Kg'];
                                @endphp
                                <select name="pph" id="pph" class="form-select" required>
                                    <option value="none">Tidak ada PPh</option>
                                    @foreach ($options as $option)
                                        <option value="{{ $option }}">{{ $option }}
                                        </option>
                                    @endforeach
                                </select>

                                <div class="d-none" id="optional-witholding">
                                    <label for="object" class="form-label">Nama Objek</label>
                                    <select name="object" id="object" class="form-select">
                                        <option></option>
                                    </select>

                                    <label for="dpp-pph" class="form-label">DPP PPh</label>
                                    <input type="text" class="form-control" name="dpp-pph" id="dpp-pph"
                                        inputmode="numeric">

                                    {{-- <label for="prefix" class="form-label">Prefix</label>
                                    <input type="text" class="form-control bg-gray-50" name="prefix" id="prefix"
                                        value="-"> --}}

                                    <label for="tarrif" class="form-label">Tarif (%)</label>
                                    <input type="text" class="form-control bg-gray-50" id="tarrif" name="tarrif"
                                        id="tarrif" readonly>

                                    <label for="value" class="form-label">Nilai</label>
                                    <div class="input-group">
                                        <span class="input-group-text prefix" id="prefix">
                                        </span>
                                        <input type="text" class="form-control bg-gray-50 fixed-input" name="value"
                                            id="value" readonly>
                                        <span class="input-group-text">
                                            <span class="badge badge-center rounded-pill text-bg-danger d-none"
                                                id="nilai-status">
                                                <i class="ti tabler-x"></i>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row d-flex justify-content-end">
                            <div class="col-12 col-md-6">
                                <label for="total" class="form-label"
                                    style="font-weight: bold; font-size:18px;">Total</label>
                                <input type="text" class="form-control bg-gray-50" name="total" id="total"
                                    readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button type="button" class="accordion-button" data-bs-toggle="collapse"
                        data-bs-target="#verify-content" aria-expanded="true" aria-controls="verify-content">

                        <div class="container-fluid p-0">
                            <div class="row w-100 m-0">
                                <div class="col-12 col-md-6 fw-bold fs-5 p-0">Invoice Verification</div>
                            </div>

                            <div class="border-bottom w-100 mt-2 d-none d-md-block"></div>
                        </div>

                    </button>
                </h2>

                <div class="accordion-collapse collapse show" data-bs-parent="tax" id="verify-content">
                    <div class="accordion-body">
                        <div class="row mb-3">
                            <div class="col-12 col-md-6">
                                <div class="fw-bold fs-6 mb-3 text-dark text-center text-md-start">
                                    Supplier Invoice Verification

                                    <div class="border-bottom w-100 mt-2"></div>
                                </div>

                                <div class="row">
                                    <div class="col-12 col-md-8">
                                        <label for="invoice_number" class="form-label">No Invoice</label>
                                        <input type="text" class="form-control" name="invoice_number"
                                            id="invoice_number" pattern="^[A-Za-z0-9\-\/\.,]+$" required>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="invoice_date" class="form-label">Invoice Date</label>
                                        <input type="date" class="form-control" name="invoice_date" id="invoice_date"
                                            max="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-8">
                                        <label class="form-label">File upload Invoice</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="file" class="form-control" id="invoice_file"
                                                name="invoice_file" accept=".pdf" style="display: none;">
                                            <input type="text" class="form-control bg-light" id="invoice-file-name"
                                                readonly placeholder="pdf_file_invoice.pdf">
                                        </div>

                                        <div class="d-flex align-items-center mt-2 d-none" id="verified-invoice">
                                            <span class="badge bg-label-success">Verified</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex gap-2" style="margin-top: 25px;">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                id="btn-upload-invoice">
                                                <i class="icon-xs icon-base ti tabler-file-type-pdf me-2"></i>
                                                <span>Upload</span>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning d-none"
                                                id="btn-retry-ocr-invoice" disabled>
                                                <i class="icon-xs icon-base ti tabler-refresh me-2"></i>
                                                <span>Retry OCR</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="fw-bold fs-6 mb-3 text-dark text-center text-md-start mt-3 mt-md-0">
                                    Tax Invoice Verification

                                    <div class="border-bottom w-100 mt-2"></div>
                                </div>

                                <div class="row">
                                    <div class="col-12 col-md-4">
                                        <label for="tax_invoice" class="form-label">Nomor Seri Faktur Pajak</label>
                                        <input type="number" class="form-control" name="tax_invoice" id="tax_invoice"
                                            required>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="npwp_supplier" class="form-label">NPWP</label>
                                        <input type="text" class="form-control bg-gray-50" name="npwp_supplier"
                                            id="npwp_supplier" readonly>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="tax_invoice_date" class="form-label"> Tax Invoice Date</label>
                                        <input type="date" class="form-control" name="tax_invoice_date"
                                            max="{{ date('Y-m-d') }}" id="tax_invoice_date" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <label class="form-label">File upload Tax</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="file" class="form-control" id="tax_file" name="tax_file"
                                                accept=".pdf" style="display: none;">
                                            <input type="text" class="form-control bg-light" id="tax-file-name"
                                                readonly placeholder="pdf_file_tax.pdf">
                                        </div>

                                        <div class="d-flex align-items-center mt-2 d-none" id="verified-tax">
                                            <span class="badge bg-label-success">Verified</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex gap-2" style="margin-top: 25px;">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                id="btn-upload-tax">
                                                <i class="icon-xs icon-base ti tabler-file-type-pdf me-2"></i>
                                                <span>Upload</span>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning d-none"
                                                id="btn-retry-ocr-tax" disabled>
                                                <i class="icon-xs icon-base ti tabler-refresh me-2"></i>
                                                <span>Retry OCR</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr class="mt-3 mb-0">
                        </div>

                        <div class="row mb-3" id="rekap-jasa-content">
                            <div class="col-12">
                                <div class="fw-bold fs-6 mb-3 text-dark text-center text-md-start">
                                    Rekap Jasa PPh
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <label for="rekap-jasa-pph" class="form-label">Rekap jasa PPh</label>
                                    <input type="file" class="form-control d-none" id="rekap_jasa_file"
                                        accept="application/pdf" name="rekap_jasa_file">
                                    <span class="text-danger d-none required-rekap-jasa-pph">*</span>
                                    <input type="text" class="form-control bg-light" id="pph-file-name" readonly
                                        placeholder="pdf_file_pph.pdf">
                                    <div class="d-flex align-items-center mt-2 d-none" id="verified-rekap-jasa">
                                        <span class="badge bg-label-success">Verified</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2" style="margin-top: 25px;">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            id="btn-upload-pph">
                                            <i class="icon-xs icon-base ti tabler-file-type-pdf me-2"></i>
                                            <span>Upload</span>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-warning d-none"
                                            id="btn-retry-ocr-pph" disabled>
                                            <i class="icon-xs icon-base ti tabler-refresh me-2"></i>
                                            <span>Retry OCR</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <hr class="mt-3 mb-0">
                        </div>

                        {{-- list error ocr --}}
                        <div class="row">
                            <div class="alert alert-danger list-error-ocr d-none" role="alert">
                            </div>
                            <div class="alert alert-warning alert-dismissible fade show list-warning-ocr d-none"
                                role="alert">
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                            <input type="hidden" id="unverified_ocr">
                        </div>
                        {{-- end list error ocr --}}

                        <div class="row">
                            <div class="col-12">
                                <div class="fw-bold fs-6 mb-3 text-dark text-center text-md-start">
                                    Other File Upload
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 col-md-2">
                                    <div>
                                        <input type="file" id="other-file" name="other-file" aria-label="Upload File"
                                            accept="application/pdf" style="display: none;">
                                        <button type="button" class="btn btn-outline-primary w-100"
                                            id="other-file-upload-button">
                                            <i class="icon-xs icon-base ti tabler-file-type-pdf me-2"></i>
                                            <span>Upload File</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 mt-3 mt-md-0">
                                    <input class="form-control bg-light" type="text" id="other-file-name"
                                        placeholder="Rename file" readonly>
                                </div>
                                <div
                                    class="col-12 col-md-4 mt-3 mt-md-0 d-flex justify-content-center justify-content-md-start">
                                    <button class="btn btn-primary" type="button"
                                        id="add-other-file-button">Add</button>
                                </div>
                            </div>
                            <div class="row mt-3 gap-4 d-flex justify-content-center" id="other-file-list">

                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-4 mt-3">
                            <button class="btn btn-secondary" type="button" id="back-button">Back</button>
                            <button class="btn btn-primary" type="submit" id="next-button">Next</button>
                            <button class="btn btn-primary d-none" type="button"
                                id="escalated-button">Escalated</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>

    @include('modules.FACTWM.FACTWM02.FACTWMF007.partials._upload-requirement-modal')

@endsection

@section('page-script')
    <script>
        window.APP_CONFIG = {
            ppn: @json($ppn),
            rumus_dpp: @json($rumus_dpp),
            limit_eskalated: @json($limit_eskalated),
            pkp_supplier: @json($pkp_supplier),
            ocr_render_dpi_start: 160,
            ocr_render_dpi_step: 20,
        };
    </script>
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/js/pages/factwm/factwm02/verify-po/ocr.js'])
@endsection
