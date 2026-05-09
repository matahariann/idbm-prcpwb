@extends('layouts/layoutMaster')

@section('title', 'PRCPWB - Transaction Daily Request')

@section('page-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="javascript:void(0);">PO Web</a>
        </li>
        <li class="breadcrumb-item">
            <a href="javascript:void(0);">Transaction</a>
        </li>
        <li class="breadcrumb-item active">Daily Request</li>
    </ol>
</nav>

<div class="card">
    <div class="card-datatable">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-center my-5 px-6 gap-4">
            
            <!-- Sorting & Export-->
            <div class="d-flex align-items-center gap-3 flex-column flex-md-row w-50 w-xl-auto justify-content-end">
                <!-- Export-->
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary disabled d-flex align-items-center gap-2 py-2 px-3"
                        id="export-excel">
                            <i class="icon-base ti tabler-file-type-xls"></i> Eksport
                    </button>
                </div>
                <!-- Sorting -->
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 text-nowrap fw-medium">Sort by:</label>
                    <select class="form-select form-select-sm" id="sort-column" style="min-width: 160px;">
                        <option value="">-- Column --</option>
                        <option value="VVENDORNO">Vendor ID</option>
                        <option value="VVENDORNAME">Vendor Name</option>
                        <option value="DWANTEDRECEIPTDATE">Wanted Receipt Date</option>
                        <option value="VTIME">Time</option>
                        <option value="VPARTNO">Part Number</option>
                        <option value="VPARTDESCRIPTION">Part Description</option>
                        <option value="IQUANTITY">QTY DR</option>
                        <option value="IQUANTITYCONFIRMATION">QTY SJ</option>
                        <option value="IQUANTITYACTUAL">QTY ACT</option>
                        <option value="VSTATUS">Status</option>
                        <option value="VPONO">PO Number</option>
                        <option value="VDAILYREQNO">DR Number</option>
                        <option value="VDELIVERYNOTENO">SJ Number</option>
                        <option value="VPRODUCTFAMILY">Prod Family</option>
                        <option value="DMODI">Actual Receipt Date</option>
                    </select>
                    <select class="form-select form-select-sm" id="sort-direction" style="min-width: 130px;">
                        <option value="asc">Ascending</option>
                        <option value="desc">Descending</option>
                    </select>
                </div>
            </div>

            <!-- Entries & Search -->
            <div class="d-flex align-items-center gap-3 flex-column flex-md-row w-50 w-xl-auto justify-content-end">
                <!-- Entries -->
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 text-nowrap">Show</label>
                    <select class="form-select form-select-sm" id="entries" style="width: 80px;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="-1">All</option>
                    </select>
                </div>
                
                <!-- Search Box -->
                <div class="input-group input-group-sm" style="min-width: 200px;">
                    <span class="input-group-text border-end-0 bg-transparent">
                        <i class="ti tabler-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" placeholder="Search..." id="search-input">
                </div>
            </div>

        </div>

        {{ $dataTable->table(['class' => 'table'], true) }}
    </div>
</div>
@endsection

@section('page-script')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
    @vite(['resources/js/pages/prcpwb/prcpwb02/daily-request/daily-request.js'])
@endsection