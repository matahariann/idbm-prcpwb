<div class="modal fade" id="vendor-modal" tabindex="-1" aria-labelledby="vendor-title" aria-modal="true"
    role="dialog">

    <div class="modal-dialog" tabindex="-1">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title w-100 pb-5 text-white" id="vendor-title">
                    Vendor Form
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close modal"></button>
            </div>

            <div class="modal-body">
                <form id="vendor-form">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="vendor_no" class="form-label">Vendor No</label>
                            <input type="text" class="form-control" id="vendor_no" name="vendor_no" disabled>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="vendor_name" class="form-label">Vendor Name</label>
                            <input type="text" class="form-control" id="vendor_name" name="vendor_name" disabled>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="contact" class="form-label">Contact</label>
                            <input type="text" class="form-control" id="contact" name="contact">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="switch">
                                <span class="switch-label">Apakah vendor import?</span>
                                <input type="checkbox" class="switch-input" id="import-check" name="import" value="1">
                                <span class="switch-toggle-slider">
                                    <span class="switch-on">
                                        <i class="icon-base ti tabler-check"></i>
                                    </span>
                                    <span class="switch-off">
                                        <i class="icon-base ti tabler-x"></i>
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-top pt-8 pb-8 px-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    aria-label="Cancel">Cancel</button>

                <button type="submit" form="vendor-form" class="btn btn-primary"
                    id="btn-save">Submit</button>
            </div>

        </div>
    </div>
</div>