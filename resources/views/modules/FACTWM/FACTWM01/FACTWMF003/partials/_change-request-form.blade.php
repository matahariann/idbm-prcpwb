<div class="modal modal-lg fade" id="request-modal" tabindex="-1" aria-labelledby="application-title" aria-modal="true"
    role="dialog">

    <div class="modal-dialog" tabindex="-1">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title w-100 pb-5 text-white" id="request-title">
                    Request Form
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close modal"></button>
            </div>

            <div class="modal-body">
                <form id="request-form">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-12 col-md-6">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="name" maxlength="100" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="username" maxlength="100" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12 col-md-6">
                            <label for="position" class="form-label">Description</label>
                            <input type="text" class="form-control" name="position" id="position" maxlength="100" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="type" class="form-label">Communication Method</label>
                            <input type="text" class="form-control" name="type" id="type" maxlength="100" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12 col-md-6">
                            <label for="method" class="form-label">Value</label>
                            <input type="text" class="form-control" name="method" id="method" maxlength="100" required>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-top pt-8 pb-8 px-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    aria-label="Cancel">Cancel</button>

                <button type="submit" form="request-form" class="btn btn-primary"
                    id="btn-save">Submit</button>
            </div>

        </div>
    </div>
</div>
