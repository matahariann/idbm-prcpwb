@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Report Invoice')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@section('page-style')
    <style>
        .bg-blue {
            background-color: #0d6efd !important;
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
                <a href="javascript:void(0);">Report</a>
            </li>
            <li class="breadcrumb-item active">Report Invoice</li>
        </ol>
    </nav>

    @include('modules.FACTWM.FACTWM03.FACTWMF011._partials._filter_datatable')
    @include('modules.FACTWM.FACTWM03.FACTWMF011._partials._datatable')
@endsection

@section('page-script')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
    @vite(['resources/js/pages/factwm/factwm03/report-invoice/report-invoice.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection
