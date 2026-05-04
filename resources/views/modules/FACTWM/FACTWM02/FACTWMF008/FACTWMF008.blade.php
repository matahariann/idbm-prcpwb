@php
    use App\Models\FACTWM02\FACTWM_TRHVERIFY_NON_PO as VerifyNonPo;
@endphp

@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Transaction Applications')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">3 Way Matching</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">Transaction</a>
            </li>
            <li class="breadcrumb-item active">Verify NON PO</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-datatable">
            <div class="d-flex justify-content-between align-items-center my-5 flex-column flex-xl-row px-6">

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
                <div class="d-flex align-items-center gap-2 justify-content-center">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary" id="btn-export">
                            <i class="icon-base ti tabler-download"></i>
                            <span>Export</span>
                        </button>
                        @can('create', VerifyNonPo::class)
                            <a class="btn btn-primary" href="/FACTWM/ts/verify-non-po/create">
                                <i class="icon-base ti tabler-plus"></i>
                                <span>Create</span>
                            </a>
                        @endcan
                    </div>
                </div>
            </div>

            {{ $dataTable->table(['class' => 'table'], true) }}
        </div>
    </div>
@endsection

@section('page-script')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
    @vite(['resources/js/pages/factwm/factwm02/verify-non-po/verify-non-po.js'])
@endsection
