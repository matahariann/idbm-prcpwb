@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Login History Report')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">3 Way Matching</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">Report</a>
            </li>
            <li class="breadcrumb-item active">Login History</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-datatable">
            <div class="d-flex justify-content-between align-items-center my-5 flex-column flex-xl-row px-6">
                <div class="d-flex align-items-center gap-2 justify-content-center">
                    <div class="d-flex gap-2">
                        <h4>Login History</h4>
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
                        </select>
                    </div>
                    <button type="button"
                        class="btn btn-label-secondary waves-effect d-flex align-items-center gap-2 px-2 py-2 w-100"
                        id="btn-export">
                        <i class="icon-base ti tabler-upload"></i>
                        <span>Export</span>
                    </button>
                </div>
            </div>
            {{ $dataTable->table() }}
        </div>
    </div>
@endsection

@section('page-script')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
    @vite(['resources/js/pages/factwm/factwm03/login-history/login-history.js']);
@endsection
