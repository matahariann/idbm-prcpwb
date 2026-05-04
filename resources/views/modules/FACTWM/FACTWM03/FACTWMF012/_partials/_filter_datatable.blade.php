<div class="card">
    <div class="card-header">
        {{-- <div class="row">
            <div class="col-md-3">
                <div class="input-group input-group-merge">
                    <input type="text" class="form-control" placeholder="Select Range Date" id="filter_date"
                        autocomplete="off">
                    <span class="input-group-text">
                        <i class="icon-base ti tabler-calendar"></i>
                    </span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="input-group input-group-merge">
                    <input type="text" class="form-control" placeholder="Delivery Date" id="filter_delivery_date"
                        autocomplete="off">
                    <span class="input-group-text">
                        <i class="icon-base ti tabler-calendar"></i>
                    </span>
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="type" id="type">
                    <option value="">Select Type</option>
                    <option value="PO">PO</option>
                    <option value="Non PO">Non PO</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" id="status" class="form-select" style="width: 100%; min-width: 200px;">
                    <option value="">Select Status</option>
                    <option value="Paid">Paid</option>
                    <option value="Prelimenary">Prelimenary</option>
                    <option value="Cancel">Cancel</option>
                    <option value="Eskalated">Eskalated</option>
                </select>
            </div>
        </div> --}}
        <div class="d-flex justify-content-between align-items-center my-5 flex-column flex-xl-row">
            <div class="d-flex align-items-center gap-2 justify-content-center">
                <div class="d-flex align-items-center gap-1 justify-content-center">
                    <select class="form-select form-select-sm mx-2" id="entries"
                        style="width: 70px; max-width: 70px; font-size: 1rem; padding: 0.25rem 0.5rem;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="button"
                        class="btn btn-label-secondary waves-effect d-flex align-items-center gap-2 px-2 py-2 w-100"
                        id="btn-export">
                        <i class="icon-base ti tabler-upload"></i>
                        <span>Export</span>
                    </button>
                </div>
                {{-- <div class="d-flex gap-2">
                    <button type="button"
                        class="btn btn-primary waves-effect d-flex align-items-center gap-2 px-2 py-2 w-100"
                        id="btn-show">
                        <i class="icon-base ti tabler-eye"></i>
                        <span>Show Data</span>
                    </button>
                </div> --}}
            </div>
            <div class="d-flex gap-3 align-items-center flex-column flex-lg-row mt-2 mt-xxl-0">
                {{-- <div class="d-flex align-items-center gap-3 justify-content-center">
                    <select name="status" id="status" placeholder="Select Status"
                        style="width: 100%; min-width: 200px;">
                        <option value=""></option>
                        <option value="Paid">Paid</option>
                        <option value="Prelimenary">Prelimenary</option>
                        <option value="Cancel">Cancel</option>
                        <option value="Eskalated">Eskalated</option>
                    </select>
                </div> --}}
                {{-- <div class="d-flex align-items-center gap-2 justify-content-center">
                    <div class="input-group" style="width: 100%; min-width: 200px;">
                        <span class="input-group-text border-end-0">
                            <i class="icon-base ti tabler-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" placeholder="Search"
                            aria-label="Search Shift" id="search-input">
                    </div>
                </div> --}}
            </div>
        </div>
    </div>
</div>
