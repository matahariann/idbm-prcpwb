@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Report Overview')
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
            <li class="breadcrumb-item active">Report Overview</li>
        </ol>
    </nav>

    @include('modules.FACTWM.FACTWM03.FACTWMF012._partials._filter_datatable')
    @include('modules.FACTWM.FACTWM03.FACTWMF012._partials._datatable')
@endsection

@section('page-script')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
    @vite(['resources/js/pages/factwm/factwm03/report-overview/report-overview.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection
