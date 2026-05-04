<div class="card mt-4">
    <div class="card-header mb-5">
        <div class="row">
            <div class="col-md-4">
                <div class="input-group input-group-merge">
                    <input type="text" class="form-control" placeholder="Select Range Date" id="filter_date"
                        autocomplete="off">
                    <span class="input-group-text">
                        <i class="icon-base ti tabler-calendar"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="card-datatable">
        {{ $dataTable->table() }}
    </div>
</div>
