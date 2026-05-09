@extends('layouts/layoutMaster')

@section('title', 'PRCPWB - Master Data Vendor')

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
            <a href="javascript:void(0);">Master Data</a>
        </li>
        <li class="breadcrumb-item active">Vendor</li>
    </ol>
</nav>

<div class="card">
        <div class="card-datatable">
            <div class="d-flex justify-content-between align-items-center my-5 flex-column flex-xl-row px-6">
                <div class="d-flex align-items-center gap-2 justify-content-center flex-column flex-lg-row mt-2 mt-xxl-0">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="btn-sync">
                            <i class="icon-base ti tabler-refresh"></i>
                            <span>Sync</span>
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary disabled d-flex align-items-center gap-2 py-2 px-3"
                        id="export-excel">
                            <i class="icon-base ti tabler-file-type-xls"></i> Eksport
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        @serve('PRCPWBF002-Update')
                        <button type="button" class="btn btn-primary disabled" id="btn-delete-selected">
                            <i class="icon-base ti tabler-pencil"></i>
                            <span>Edit</span>
                        </button>
                        @endserve
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
                    <div class="input-group" style="width: 100%; min-width: 200px;">
                        <span class="input-group-text border-end-0">
                            <i class="icon-base ti tabler-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" placeholder="Search"
                            aria-label="Search Shift" id="search-input">
                    </div>
                </div>
            </div>

            {{ $dataTable->table(['class' => 'table'], true) }}
        </div>
    </div>

@include('modules.PRCPWB.PRCPWB01.PRCPWBF002.partials._vendor-form')
@endsection

@section('page-script')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
    @vite(['resources/js/pages/prcpwb/prcpwb01/vendor/vendor.js'])
@endsection