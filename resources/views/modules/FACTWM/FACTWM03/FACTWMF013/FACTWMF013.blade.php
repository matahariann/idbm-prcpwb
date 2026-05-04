@extends('layouts/layoutMaster')

@section('title', 'FACTWM - Document Managements')
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
            <li class="breadcrumb-item active">Document Managements</li>
        </ol>
    </nav>

    <div class="row">
        @include('modules.FACTWM.FACTWM03.FACTWMF013._partials._file-manager')
        @include('modules.FACTWM.FACTWM03.FACTWMF013._partials._progress-bar-chart')
    </div>

    @include('modules.FACTWM.FACTWM03.FACTWMF013._partials._datatable')
    @include('modules.FACTWM.FACTWM03.FACTWMF013._partials._modal-upload-file')
    @include('modules.FACTWM.FACTWM03.FACTWMF013._partials._upload-requirement-modal')
@endsection

@section('page-script')
    {{ $dataTable->scripts(attributes: ['type' => 'module']) }}
    @vite(['resources/js/pages/factwm/factwm03/document-management/document-management.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection
