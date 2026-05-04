<div class="modal fade" id="dispute-modal" tabindex="-1" aria-labelledby="dispute-title" aria-modal="true" role="dialog">

    <div class="modal-dialog" tabindex="-1">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title w-100 pb-5 text-white" id="dispute-title">
                    Dispute Form
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close modal"></button>
            </div>

            <div class="modal-body">
                <form id="dispute-form">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label" for="dispute-grn">GRN</label>
                            <input class="form-control" type="text" id="dispute-grn" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label" for="dispute-file">File upload</label>
                            <div class="input-group">
                                <input class="form-control" type="file" id="dispute-file" name="file">
                                <button class="btn btn-outline-secondary" type="button"
                                    id="dispute-upload-btn">Upload</button>
                            </div>
                            <small id="dispute-file-label" class="form-text text-muted">No file choosen</small>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label" for="dispute-description">Description</label>
                            <textarea class="form-control" id="dispute-description" name="description" rows="4"
                                placeholder="Input Description" required></textarea>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-top pt-8 pb-8 px-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    aria-label="Cancel">Cancel</button>

                <button type="submit" form="dispute-form" class="btn btn-primary" id="btn-save">Submit</button>
            </div>

        </div>
    </div>
</div>
