@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Verify PO')

@section('page-style')
@vite(['resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/flatpickr/monthSelect.scss'])
<style>
    #factwmf007-table thead .dt-orderable-asc.dt-orderable-desc .dt-column-order::before,
    #factwmf007-table thead .dt-orderable-asc.dt-orderable-desc .dt-column-order::after,
    #factwmf007-table thead .dt-ordering-asc .dt-column-order::before,
    #factwmf007-table thead .dt-ordering-asc .dt-column-order::after,
    #factwmf007-table thead .dt-ordering-desc .dt-column-order::before,
    #factwmf007-table thead .dt-ordering-desc .dt-column-order::after {
        visibility: visible !important;
        opacity: 0.3;
    }

    #factwmf007-table thead .dt-ordering-asc .dt-column-order::before,
    #factwmf007-table thead .dt-ordering-desc .dt-column-order::after {
        opacity: 1;
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
        <div class="d-flex justify-content-between align-items-center my-5 flex-column flex-xl-row px-6">
            <div class="col-12 col-md-3 px-0">
                <div class="input-group input-group-merge">
                    <input type="text" class="form-control" id="month" name="month">
                    <span class="input-group-text">
                        <i class="icon-base ti tabler-calendar"></i>
                    </span>
                </div>
            </div>

            <div class="d-flex gap-2 align-items-center flex-column flex-lg-row mt-2 mt-xxl-0">
                <div class="d-flex align-items-center gap-1 justify-content-center">
                    <select class="form-select form-select-sm mx-2" id="entries"
                        style="width: 70px; max-width: 70px; font-size: 1rem; padding: 0.25rem 0.5rem;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="-1">All</option>
                    </select>
                </div>
            </div>
        </div>

        {{ $dataTable->table(['class' => 'table'], true) }}
    </div>
</div>
@endsection

@section('page-script')
{{ $dataTable->scripts(attributes: ['type' => 'module']) }}
@vite(['resources/js/pages/factwm/factwm02/verify-po/verify-po.js'])
@endsection
