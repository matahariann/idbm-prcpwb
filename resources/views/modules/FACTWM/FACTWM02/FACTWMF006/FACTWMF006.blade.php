@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Transaction Applications')

@section('page-style')
<style>
    /* Custom Tooltip untuk Text Panjang */
    .custom-tooltip-large .tooltip-inner {
        max-width: 300px;
        /* Lebar maksimal tooltip */
        width: max-content;
        /* Sesuaikan dengan konten */
        min-width: 150px;
        /* Lebar minimal */
        text-align: left;
        /* Alignment text */
        background-color: #2c3e50;
        /* Warna background */
        color: #ffffff;
        /* Warna text */
        font-size: 13px;
        /* Ukuran font */
        padding: 10px 15px;
        /* Padding dalam tooltip */
        border-radius: 6px;
        /* Rounded corners */
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        /* Shadow */
        line-height: 1.6;
        /* Jarak antar baris */
        white-space: normal;
        /* Allow text wrapping */
        word-wrap: break-word;
        /* Break long words */
    }

    /* Arrow tooltip */
    .custom-tooltip-large .tooltip-arrow::before {
        border-top-color: #2c3e50 !important;
    }

    /* Hover effect - Optional */
    .custom-tooltip-large {
        opacity: 1 !important;
    }

    @keyframes tooltipFadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
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
        <li class="breadcrumb-item active">Good Receipt Notes</li>
    </ol>
</nav>

@include('modules.FACTWM.FACTWM02.FACTWMF006.partials._summary-form')

<div class="card">
    <div class="card-datatable">
        <div class="d-flex justify-content-between align-items-center my-5 flex-column flex-xl-row px-6">
            <div class="d-flex align-items-center gap-2 justify-content-center">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" id="btn-export">
                        <i class="icon-base ti tabler-download"></i>
                        <span>Export</span>
                    </button>
                </div>
                <button type="button" class="btn btn-primary waves-effect waves-effect disabled"
                    id="btn-approve-selected">
                    <i class="icon-base ti tabler-check"></i>
                    <span>Approve</span>
                </button>
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
                {{-- <div class="input-group" style="width: 100%; min-width: 200px;">
                        <span class="input-group-text border-end-0">
                            <i class="icon-base ti tabler-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" placeholder="Search"
                            aria-label="Search Shift" id="search-input">
                    </div> --}}
            </div>
        </div>

        {{ $dataTable->table(['class' => 'table'], true) }}
    </div>
</div>

@include('modules.FACTWM.FACTWM02.FACTWMF006.partials._good-receipt-notes-form')
@endsection

@section('page-script')
{{ $dataTable->scripts(attributes: ['type' => 'module']) }}
@vite(['resources/js/pages/factwm/factwm02/good-receipt-notes/good-receipt-notes.js'])
@endsection