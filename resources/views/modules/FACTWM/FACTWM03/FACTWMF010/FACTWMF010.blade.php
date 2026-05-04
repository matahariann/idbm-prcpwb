@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Report GRN')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">3 Way Matching</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">Report</a>
            </li>
            <li class="breadcrumb-item active">Report GRN</li>
        </ol>
    </nav>

    <div class="card mt-4">
        <div class="card-header">
            <div class="row align-items-end">
                <div class="col-md-12">
                    <div class="col-md-3 d-flex align-items-center gap-2 mt-3">
                        <span class="text-nowrap">Show</span>
                        <select class="form-select" id="showEntries" style="width: 80px; flex-shrink: 0;">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary me-2" id="exportBtn">
                            <i class="ti tabler-download me-1"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-datatable">
            {{ $dataTable->table() }}
        </div>
    </div>

    <div class="modal fade" id="grnDetailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="grnDetailModalLabel">GRN Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="grnDetailContent">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
    @vite(['resources/js/pages/factwm/factwm03/report-grn/report-grn.js'])
@endsection
