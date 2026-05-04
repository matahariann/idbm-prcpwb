@extends('layouts/layoutMaster')

@section('title', isset($information) ? 'FACTWM - Edit Information' : 'FACTWM - Add Information')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="javascript:void(0);">User Access Management</a>
        </li>
        <li class="breadcrumb-item">
            <a href="javascript:void(0);">Master Data</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('information.index') }}">Information</a>
        </li>
        <li class="breadcrumb-item active">{{ isset($information) ? 'Edit' : 'Add' }} Information</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ isset($information) ? 'Edit' : 'Add' }} Information</h5>
    </div>
    <div class="card-body">
        <form id="addInformationForm" method="POST"
            action="{{ isset($information) ? route('information.update', $information->IID) : route('information.store') }}"
            enctype="multipart/form-data">
            @csrf
            @if (isset($information))
            @method('PUT')
            @endif

            <!-- Notes -->
            <div class="mb-3">
                <label class="form-label" for="VNOTES">Notes</label>
                {{-- <textarea class="form-control @error('VNOTES') is-invalid @enderror" id="VNOTES" name="VNOTES" placeholder="Notes"
                        rows="4" required>{{ old('VNOTES', isset($information) ? $information->VNOTES : '') }}</textarea> --}}
                <div id="VNOTES" style="height: 300px;">
                </div>
                @error('VNOTES')
                <span class="invalid-feedback d-block">{{ $message }}</span>
                @enderror
                @if (!empty($information))
                <script>
                    window.information = @json($information);
                </script>
                @endif
            </div>

            <!-- From and To Date -->
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="DFROM">Start Date</label>
                        <input type="text" class="form-control @error('DFROM') is-invalid @enderror" id="DFROM"
                            name="DFROM"
                            value="{{ old('DFROM', isset($information) ? $information->DFROM->format('Y-m-d') : '') }}"
                            required>
                        @error('DFROM')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="DTO">Expire Date</label>
                        <input type="text" class="form-control @error('DTO') is-invalid @enderror" id="DTO"
                            name="DTO"
                            value="{{ old('DTO', isset($information) ? $information->DTO->format('Y-m-d') : '') }}"
                            required>
                        @error('DTO')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- User Type and Category -->
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label" for="VUSER_TYPE">User Type</label>
                        <select class="form-select @error('VUSER_TYPE') is-invalid @enderror" id="VUSER_TYPE"
                            name="VUSER_TYPE" required>
                            <option value="">Select User Type</option>
                            <option value="all"
                                {{ old('VUSER_TYPE', isset($information) ? $information->VUSER_TYPE : '') === 'all' ? 'selected' : '' }}>
                                All
                            </option>
                            <option value="internal"
                                {{ old('VUSER_TYPE', isset($information) ? $information->VUSER_TYPE : '') === 'internal' ? 'selected' : '' }}>
                                Internal
                            </option>
                            <option value="supplier"
                                {{ old('VUSER_TYPE', isset($information) ? $information->VUSER_TYPE : '') === 'supplier' ? 'selected' : '' }}>
                                Supplier
                            </option>
                        </select>
                        @error('VUSER_TYPE')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    {{-- <div class="mb-3">
                            <label class="form-label" for="VCATEGORY">Category</label>
                            <select class="form-select @error('VCATEGORY') is-invalid @enderror" id="VCATEGORY"
                                name="VCATEGORY" required>
                                <option value="">Select Category</option>
                                <option value="category1"
                                    {{ old('VCATEGORY', isset($information) ? $information->VCATEGORY : '') === 'category1' ? 'selected' : '' }}>
                    Category 1
                    </option>
                    <option value="category2"
                        {{ old('VCATEGORY', isset($information) ? $information->VCATEGORY : '') === 'category2' ? 'selected' : '' }}>
                        Category 2
                    </option>
                    </select>
                    @error('VCATEGORY')
                    <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div> --}}
                <div class="mb-3">
                    <label for="VVIEWERS" class="form-label">Publish to Supplier</label>
                    <select class="form-select" id="VVIEWERS" required aria-required="true" name="VVIEWERS[]"
                        multiple aria-label="Publish to Supplier" disabled>
                        @php
                        // Parse selected vendors
                        $selectedVendors = [];
                        if ($information && $information->VVIEWERS) {
                        $selectedVendors = is_array($information->VVIEWERS)
                        ? $information->VVIEWERS
                        : explode(',', $information->VVIEWERS);
                        }

                        // Check if "all" is selected
                        $isAllSelected = in_array('all', $selectedVendors);
                        @endphp

                        <option value="all" {{ $isAllSelected ? 'selected' : '' }}>All Vendors</option>

                        @foreach ($vendorData as $vendor)
                        <option value="{{ $vendor->IID }}"
                            {{ !$isAllSelected && in_array($vendor->IID, $selectedVendors) ? 'selected' : '' }}>
                            [{{ $vendor->VSUPPLIER_CODE }}] - {{ $vendor->VNAME }}
                        </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
    </div>

    <!-- PDF Information and Upload Data Vendor -->
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label" for="VFILE_INFORMATION">
                    PDF Information
                </label>
                <div class="input-group">
                    <input type="file" class="form-control @error('VFILE_INFORMATION') is-invalid @enderror"
                        id="VFILE_INFORMATION" name="VFILE_INFORMATION" accept=".pdf"
                        {{ isset($information) ? '' : 'required' }}>
                    <button class="btn btn-outline-danger" type="button" id="btn-clear-pdf">
                        <i class="icon-base ti tabler-trash"></i>
                    </button>
                </div>
                @if (isset($information) && $information->VFILE_INFORMATION)
                <small class="text-muted d-block mt-2">
                    Current file:
                    <a href="{{ asset('storage/' . $information->VFILE_INFORMATION) }}" target="_blank"
                        class="text-primary">
                        View PDF
                    </a>
                </small>
                @endif
                @error('VFILE_INFORMATION')
                <span class="invalid-feedback d-block">{{ $message }}</span>
                @enderror
            </div>
        </div>
        <div class="col-md-6">
            {{-- <div class="mb-3">
                            <label class="form-label" for="VUPDLOAD_DATA_VENDOR">
                                Upload Data Vendor

                            </label>
                            <div class="input-group">
                                <input type="file"
                                    class="form-control @error('VUPDLOAD_DATA_VENDOR') is-invalid @enderror"
                                    id="VUPDLOAD_DATA_VENDOR" name="VUPDLOAD_DATA_VENDOR"
                                    {{ isset($information) ? '' : 'required' }}>
            <button class="btn btn-outline-danger" type="button" id="btn-clear-vendor">
                <i class="icon-base ti tabler-trash"></i>
            </button>
        </div>
        @if (isset($information) && $information->VUPDLOAD_DATA_VENDOR)
        <small class="text-muted d-block mt-2">
            Current file:
            <a href="{{ asset('storage/' . $information->VUPDLOAD_DATA_VENDOR) }}" target="_blank"
                class="text-primary">
                View File
            </a>
        </small>
        @endif
        @error('VUPDLOAD_DATA_VENDOR')
        <span class="invalid-feedback d-block">{{ $message }}</span>
        @enderror
    </div> --}}
    <!-- Upload Foto Asset -->
    <div class="mb-3">
        <label class="form-label">Upload Foto</label>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-2"
                id="btn-upload-asset">
                <i class="icon-base ti tabler-cloud-upload"></i>
                <span>Upload</span>
            </button>
            <span class="text-muted" id="asset-file-name">
                {{ isset($information) && $information->VUPDLOAD_FOTO_ASSET ? 'File already uploaded' : 'No file chosen' }}
            </span>
        </div>
        <input type="file" class="form-control @error('VUPDLOAD_FOTO_ASSET') is-invalid @enderror"
            id="VUPDLOAD_FOTO_ASSET" name="VUPDLOAD_FOTO_ASSET" style="display: none;">
        @error('VUPDLOAD_FOTO_ASSET')
        <span class="invalid-feedback d-block">{{ $message }}</span>
        @enderror

        <!-- Preview Container -->
        <div id="preview-container" class="d-flex flex-wrap gap-3 mt-3">
            @if (isset($information) && $information->VUPDLOAD_FOTO_ASSET)
            <div class="preview-item existing-preview position-relative">
                <img src="{{ asset('storage/' . $information->VUPDLOAD_FOTO_ASSET) }}"
                    alt="Asset preview"
                    style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                <span class="badge bg-success position-absolute top-0 start-0 m-2">Existing</span>
            </div>
            @endif
        </div>
    </div>
</div>
</div>

<!-- Form Actions -->
<div class="d-flex pt-12 gap-2">
    <button type="submit" class="btn btn-danger"
        data-original-text="{{ isset($information) ? 'Update' : 'Submit' }}">
        <span>Submit</span>
    </button>
    <button type="reset" class="btn btn-secondary">
        <span>Reset</span>
    </button>
</div>
</form>
</div>
</div>
@endsection

@section('page-script')
<script>
    window.APP_CONFIG = {
        information: @json($information ?? null),
    };
</script>
@vite(['resources/js/pages/factwm/factwm01/information/information-form.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection
