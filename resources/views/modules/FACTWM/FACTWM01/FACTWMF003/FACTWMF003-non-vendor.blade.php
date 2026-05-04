@extends('layouts/layoutMaster')

@section('title', 'HITUAM - Master Data Change Request')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="javascript:void(0);">User Access Management</a>
        </li>
        <li class="breadcrumb-item">
            <a href="javascript:void(0);">Master Data</a>
        </li>
        <li class="breadcrumb-item active">Change Request Supplier</li>
    </ol>
</nav>

<div class="card mt-3">
    <div class="card-datatable">
        <div class="d-flex justify-content-between align-items-center my-5 flex-column flex-xl-row px-6">
            <div class="d-flex align-items-center gap-2 justify-content-center">
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" id="download-selected">
                        <i class="ti tabler-download me-2"></i>
                        Download
                    </button>
                </div>
            </div>
        </div>
        <table class="table" id="request-table">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" class="form-check-input" id="select-all">
                    </th>
                    <th>Vendor Code</th>
                    <th>Vendor Name</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Communication Method</th>
                    <th>Value</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Download Date</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <th>No</th>
                    <th>Vendor Code</th>
                    <th>Vendor Name</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Communication Method</th>
                    <th>Value</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Download Date</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@include('modules.FACTWM.FACTWM01.FACTWMF003.partials._change-request-form')
@endsection

@section('page-script')
@vite(['resources/js/pages/factwm/factwm01/change-request/change-request-non-vendor.js'])
@endsection