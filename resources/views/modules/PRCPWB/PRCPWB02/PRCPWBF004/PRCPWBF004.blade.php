@extends('layouts/layoutMaster')

@section('title', 'PRCPWB - Transaction Inbox PO')

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
        <li class="breadcrumb-item active">Inbox PO</li>
    </ol>
</nav>

<div class="card">
    <div class="card-datatable">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-center my-5 px-6 gap-4">
            
            <!-- Sorting -->
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 text-nowrap fw-medium">Sort by:</label>
                <select class="form-select form-select-sm" id="sort-column" style="min-width: 160px;">
                    <option value="">-- Column --</option>
                    <option value="VORDERNO">PO Number</option>
                    <option value="VVENDORNO">Vendor ID</option>
                    <option value="VVENDORNAME">Vendor Name</option>
                    <option value="VSTATUS">Status</option>
                    <option value="DRELEASEDATE">Release Date</option>
                    <option value="DCONFIRMDATE">Confirmation Date</option>
                </select>
                <select class="form-select form-select-sm" id="sort-direction" style="min-width: 130px;">
                    <option value="asc">Ascending</option>
                    <option value="desc">Descending</option>
                </select>
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
    @vite(['resources/js/pages/prcpwb/prcpwb02/purchase-order/purchase-order.js'])
@endsection