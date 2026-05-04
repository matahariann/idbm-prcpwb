<div class="modal fade" id="import-modal" tabindex="-1" aria-labelledby="import-title" aria-modal="true"
    role="dialog">

    <div class="modal-dialog" tabindex="-1">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title w-100 pb-5 text-white" id="import-title">
                    Vendor Form
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close modal"></button>
            </div>

            <div class="modal-body">
                <form id="import-form">
                    @csrf
                    <div class="row">
                        <div class="col">
                            <label for="file">File</label>
                            <input type="file" class="form-control" name="file" id="file" required>
                            <a href="javascript:void(0)" id='template-download'>Download Template</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-top pt-8 pb-8 px-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    aria-label="Cancel">Cancel</button>

                <button type="submit" form="import-form" class="btn btn-primary"
                    id="btn-save">Submit</button>
            </div>

        </div>
    </div>
</div>
