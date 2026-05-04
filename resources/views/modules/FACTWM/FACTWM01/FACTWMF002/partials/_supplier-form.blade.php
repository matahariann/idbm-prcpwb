<div class="modal fade" id="supplier-modal" tabindex="-1" aria-labelledby="supplier-title" aria-modal="true"
    role="dialog">

    <div class="modal-dialog" tabindex="-1">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title w-100 pb-5 text-white" id="supplier-title">
                    Supplier Form
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close modal"></button>
            </div>

            <div class="modal-body">
                <form id="supplier-form">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="supplier_code" class="form-label">Vendor ID</label>
                            <input type="text" class="form-control" id="supplier_code" name="supplier_code" disabled>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="supplier_name" class="form-label">Vendor Name</label>
                            <input type="text" class="form-control" id="supplier_name" name="supplier_name" disabled>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-8">
                            <label class="switch">
                                <span class="switch-label">Apakah anda tidak punya NPWP?</span>
                                <input type="checkbox" class="switch-input" id="npwp-check">
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
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="npwp" class="form-label">No. NPWP</label>
                            <input type="number" class="form-control" id="npwp" name="npwp">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12 d-none" id="nik-input">
                            <label for="nik" class="form-label">NIK</label>
                            <input type="number" class="form-control" id="nik" name="nik">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="form-label d-block mb-3">Status PKP</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="pkp" id="pkp-yes" value="1">
                                    <label class="form-check-label" for="pkp-yes">
                                        PKP
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="pkp" id="pkp-no" value="0" checked>
                                    <label class="form-check-label" for="pkp-no">
                                        Non PKP
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-top pt-8 pb-8 px-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    aria-label="Cancel">Cancel</button>

                <button type="submit" form="supplier-form" class="btn btn-primary"
                    id="btn-save">Submit</button>
            </div>

        </div>
    </div>
</div>
