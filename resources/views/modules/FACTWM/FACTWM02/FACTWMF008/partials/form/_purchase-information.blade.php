<!-- PURCHASE INFORMATION -->
<style>
    .prefix {
        height: 38px;
        font-size: 20px;
    }

    .fixed-input {
        /* samakan dengan default bootstrap */
        font-size: 14px;
    }
</style>
<div class="row">
    <div class="col-md-6">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label" for="invoice_number">No Invoice Supplier</label>
                <input type="text" class="form-control" id="invoice_number" name="invoice_number"
                    pattern="^[A-Za-z0-9\-\/\.,]+$" value="{{ $nonPo->VINV_NO_SUPPLIER }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="invoice_date">Invoice Date</label>
                <input type="date" class="form-control" id="invoice_date" name="invoice_date"
                    max="{{ date('Y-m-d') }}" value="{{ $nonPo->DINV_DATE?->format('Y-m-d') }}" required>
            </div>
        </div>

        <!-- File Upload Invoice -->
        <div class="row mb-3">
            <div class="col-md-8">
                <label class="form-label">File upload Invoice</label>
                <div class="d-flex align-items-center gap-2">
                    <input type="file" class="form-control" id="invoice_pdf" name="invoice_pdf" accept=".pdf"
                        style="display: none;">
                    <input type="text" class="form-control bg-light" id="invoice-file-name" readonly
                        placeholder="pdf_file_invoice.pdf" value="{{ $nonPo->VPDF_INVOICE }}">
                </div>

                <div class="d-flex align-items-center mt-2 d-none" id="verified-invoice">
                    <span class="badge bg-label-success">Verified</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2 mt-5">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-upload-invoice">
                        <i class="icon-xs icon-base ti tabler-file-type-pdf me-2"></i>
                        <span>Upload</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning d-none" id="btn-retry-ocr-invoice"
                        disabled>
                        <i class="icon-xs icon-base ti tabler-refresh me-2"></i>
                        <span>Retry OCR</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tax Information -->
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label" for="tax_code">Tax Code</label>
                    @php
                        $selectedTaxCode = $nonPo->VTAX_CODE ?? 'V11';
                    @endphp
                    <select class="form-select" id="tax_code" name="tax_code" required>
                        <option value="V0" {{ $selectedTaxCode === 'V0' ? 'selected' : '' }}>V0</option>
                        <option value="V11" {{ $selectedTaxCode === 'V11' ? 'selected' : '' }}>V11</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Conditional Fields for Tax Code -->
        <div id="tax-code-fields" class="{{ $selectedTaxCode === 'V0' ? 'd-none' : '' }}">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="tax_number_supplier">Nomor Seri Faktur Pajak</label>
                        <input type="number" class="form-control" id="tax_number_supplier"
                            value="{{ $nonPo->VTAX_NUMBER }}" name="tax_number_supplier">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="npwp_supplier">NPWP Supplier</label>
                        <input type="text" class="form-control bg-light" id="npwp_supplier" name="npwp_supplier"
                            value="{{ @$npwp_supplier }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label" for="tax_date">Tax Date</label>
                        <input type="date" class="form-control" id="tax_date" name="tax_date"
                            max="{{ date('Y-m-d') }}"
                            value="{{ $nonPo->DTAX_DATE ? \Carbon\Carbon::parse($nonPo->DTAX_DATE)->format('Y-m-d') : '' }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">File upload Tax Vendor</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="file" class="form-control" id="tax_pdf" name="tax_pdf" accept=".pdf"
                                style="display: none;">
                            <input type="text" class="form-control bg-light" id="tax-file-name" readonly
                                placeholder="pdf_file_tax_vendor.pdf" value="{{ $nonPo->VPDF_TAX }}">
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-2 mb-2 d-none" id="verified-tax">
                        <span class="badge bg-label-success">Verified</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2 mt-5">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-upload-tax">
                            <i class="icon-xs icon-base ti tabler-file-type-pdf me-2"></i>
                            <span>Upload</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning d-none" id="btn-retry-ocr-tax"
                            disabled>
                            <i class="icon-xs icon-base ti tabler-refresh me-2"></i>
                            <span>Retry OCR</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Other File Upload -->
        <div class="row">
            <div class="col-md-3 pe-0">
                <label class="form-label">Other File Upload</label>
                <input type="file" class="form-control" id="other-file-input" accept=".pdf"
                    style="display: none;">

                <button type="button" class="btn btn-outline-primary w-100" id="btn-add-other-file">
                    <i class="icon-xs icon-base ti tabler-file-type-pdf me-2"></i>
                    <span>Upload</span>
                </button>

            </div>
            <div class="col-md-5">
                <input type="text" class="form-control mt-5" id="other-file-name"
                    placeholder="Changes File Name">
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-primary mt-5" id="add-other-file-button">
                    Add
                </button>
            </div>
        </div>

        {{-- list error ocr --}}
        <div class="row mt-3">
            <div class="alert alert-danger list-error-ocr d-none" role="alert">
            </div>
            <input type="hidden" id="unverified_ocr">
        </div>
    </div>
    <div class="col-md-6">
        <div class="row">
            <div class="col-md-12">
                <div class="mb-3">
                    <label class="form-label" for="pph">PPH Pasal (PPh23, PPh22, PPh4-02)</label>
                    @php
                        // dd($item);
                        $options = [];
                        if (!empty($list_pph_pasal)) {
                            $options = explode(',', $list_pph_pasal);
                        }
                        // $options = ['-', 'Pcs', 'Box', 'Kg'];
                    @endphp
                    <select name="pph" id="pph" class="form-select" required>
                        <option value="">-- Pilih PPh --</option>
                        <option value="none">Tidak ada PPh
                        </option>
                        @foreach ($options as $option)
                            <option value="{{ $option }}">
                                {{ $option }}
                            </option>
                        @endforeach
                        @if ($action == 'View')
                            <option value="{{ $nonPo->VPPH }}" selected>{{ $nonPo->VPPH }}</option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="{{ $action == 'create' ? 'd-none' : '' }}" id="optional-witholding">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label" for="object">Nama Objek</label>
                        <select class="form-select" id="object" name="object">
                            @if ($action == 'View')
                                <option value="{{ $nonPo->VOBJECT }}" selected>{{ $nonPo->VOBJECT }}</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label" for="dpp">DPP PPh</label>
                        <input type="text" class="form-control" id="dpp_pph" name="dpp_pph"
                            inputmode="numeric" data-raw="{{ $nonPo->IDPP_PPH ?? 0 }}"
                            value="{{ number_format((int) ($nonPo->IDPP_PPH ?? 0), 0, ',', '.') }}">
                    </div>
                </div>
                <div class="col-md-12">
                    {{-- <div class="mb-3">
                        <label class="form-label" for="prefix">Prefix</label>
                        <input type="text" class="form-control bg-light" id="prefix" name="prefix"
                            value="-" readonly>
                    </div> --}}
                </div>
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label" for="tarrif">Tarif (%)</label>
                        <input type="text" class="form-control bg-gray-50" id="tarrif" name="tarrif"
                            value="{{ $nonPo->FTARRIF }}" readonly>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="mb-3">
                        <label class="form-label" for="nilai">Nilai</label>
                        <div class="input-group">
                            <span class="input-group-text prefix" id="prefix">
                            </span>
                            <input type="text" class="form-control bg-gray-50 fixed-input" id="nilai"
                                name="nilai" value="{{ number_format((int) ($nonPo->FVALUE ?? 0), 0, ',', '.') }}"
                                readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- end list error ocr --}}
<div class="row mt-3 gap-4 d-flex justify-content-center" id="other-file-list">

</div>
