<div class="modal fade modal-md" id="upload-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Upload Other Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="upload-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" id="date" name="date"
                            value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <select class="form-select" name="supplier" id="supplier"
                            {{ !empty($supplier) ? 'disabled' : 'required' }}>
                            <option value="">Select Supplier</option>
                            @foreach ($vendorData as $vendor)
                                <option value="{{ $vendor->IID }}"
                                    {{ !empty($supplier) && $vendor->IID == $supplier ? 'selected' : '' }}>
                                    [{{ $vendor->VSUPPLIER_CODE }}] - {{ $vendor->VNAME }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label">File</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="file" name="file" id="file" class="form-control"
                                        accept=".pdf" style="display: none;">
                                    <input type="text" name="file_name" class="form-control bg-light" id="file-name"
                                        readonly placeholder="file.pdf">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-primary" id="btn-upload-file-notif"
                                    style="margin-top: 25px;">
                                    <i class="icon-xs icon-base ti tabler-file-type-pdf me-2"></i>
                                    <span>Upload</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" form="upload-form" class="btn btn-primary" id="btn-save">Save</button>
                </div>
            </form>

        </div>
    </div>
</div>
