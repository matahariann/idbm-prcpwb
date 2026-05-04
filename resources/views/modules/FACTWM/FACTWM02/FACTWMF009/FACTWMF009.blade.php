@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Transaction Scan Invoice Receive')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="javascript:void(0);">3 Way Matching</a>
        </li>
        <li class="breadcrumb-item">
            <a href="javascript:void(0);">Transaction</a>
        </li>
        <li class="breadcrumb-item active">Scan Receipt Invoice</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Penerimaan Dokumen</h5>
    </div>
    <div class="card-body">
        <form id="documentForm">
            <div class="row mb-3">
                <label class="col-12 col-lg-3 col-form-label">Billing Statement</label>
                <div class="col-12 col-lg-9">
                    <div class="input-group">
                        <input type="text" class="form-control" id="billingStatement" placeholder="" autocomplete="off" autofocus>
                        <span class="input-group-text">
                            <i class="icon-base ti tabler-scan"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-12 col-lg-3 col-form-label">Unique Code</label>
                <div class="col-12 col-lg-9">
                    <div class="input-group">
                        <input type="text" class="form-control" id="uniqueCode" placeholder="" autocomplete="off">
                        <span class="input-group-text">
                            <i class="icon-base ti tabler-scan"></i>
                        </span>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row mb-4">
                <label class="col-12 col-lg-3 col-form-label">Supplier Name :</label>
                <div class="col-12 col-lg-3 mb-3 mb-lg-0">
                    <input type="text" class="form-control" id="supplierName" placeholder="" readonly>
                </div>
                <label class="col-12 col-lg-2 col-form-label">No Invoice :</label>
                <div class="col-12 col-lg-4">
                    <input type="text" class="form-control" id="noInvoice" placeholder="" readonly>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-lg-9">
                    <button type="submit" class="btn btn-danger me-2">Submit</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- for table d-none -->
<div class="card mt-5" id="for_data_history">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">History Scan Receipt Invoice</h5>
        <div class="d-flex align-items-center gap-2">
            <label class="mb-0 text-nowrap">Select Range Date:</label>
            <div class="input-group">
                <input type="text" class="form-control" id="dateRangeFilter" placeholder="Select date range" style="min-width: 250px;">
                <span class="input-group-text">
                    <a title="clear" id="date-clear">
                        <i class="icon-base ti tabler-x"></i>
                    </a>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body">
        {{ $dataTable->table(['class' => 'table'], true) }}
    </div>
</div>
@endsection

@section('page-script')
{{ $dataTable->scripts(attributes: ['type' => 'module']) }}
@vite(['resources/js/pages/factwm/factwm02/scan-verify-non-po/scan-verify-non-po-form-scan.js'])
@endsection