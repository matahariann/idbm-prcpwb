@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Verify PO')

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

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <label for="summary" class="form-label">Summary of Selected GRNs : <b id="selected-length">0</b> </label>
            </div>
            <div class="row mb-3">
                <div class="col-8 d-flex align-items-center">
                    Net Amount
                </div>
                <div class="col-4">
                    <input type="text" class="form-control" id="summary" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-8 d-flex align-items-center">
                    DPP Nilai Lain
                </div>
                <div class="col-4">
                    <input type="text" class="form-control" id="dpp" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-8 d-flex align-items-center">
                    PPn
                </div>
                <div class="col-4">
                    <input type="text" class="form-control" id="ppn" disabled>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-8 d-flex align-items-center">
                    Grand Total
                </div>
                <div class="col-4">
                    <input type="text" class="form-control" id="gross-summary" disabled>
                </div>
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn btn-primary" id="match" disabled>
                    <i class="menu-icon ti tabler-scan"></i>
                    Match
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable">
            <div class="d-flex justify-content-start align-items-center my-5 px-6">
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
            <table class="table" id="view-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="form-check-input select-all">
                        </th>
                        <th>No</th>
                        <th>Preview PDF</th>
                        <th>Status</th>
                        <th>Ref Type</th>
                        <th>Return Reference</th>
                        <th>GRN Number</th>
                        <th>GRN Date</th>
                        <th>Po Number</th>
                        <th>Delivery Number</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>
                            <input type="checkbox" class="form-check-input select-all">
                        </th>
                        <th>No</th>
                        <th>Preview PDF</th>
                        <th>Status</th>
                        <th>Ref Type</th>
                        <th>Return Reference</th>
                        <th>GRN Number</th>
                        <th>GRN Date</th>
                        <th>Po Number</th>
                        <th>Delivery Number</th>
                        <th>Amount</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        window.APP_CONFIG = {
            ppn: @json($ppn),
            rumus_dpp: @json($rumus_dpp)
        };
    </script>
    @vite(['resources/js/pages/factwm/factwm02/verify-po/view.js'])
@endsection
