<div class="col-sm-12 col-xl-8 mb-sm-3">
    <div class="card">
        <div class="card-header">
            <div class="row mt-2">
                <div class="col">
                    <div
                        class="d-flex align-items-center justify-content-start flex-sm-row flex-column row-gap-3 column-gap-3">
                        <div class="d-flex align-items-center">
                            <h5 class="mb-0">File Manager</h5>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="input-group" style="width: 100%; min-width: 200px;">
                                <span class="input-group-text border-end-0">
                                    <i class="icon-base ti tabler-search"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" placeholder="Searching..."
                                    aria-label="Search Shift" id="search-input">
                            </div>
                        </div>
                        {{-- <div class="d-flex align-items-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-create-folder">
                                        <i class="icon-base ti tabler-plus"></i>
                                        <span>New Folder</span>
                                </div> --}}
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-sm btn-primary" id="btn-upload-file">
                                <i class="icon-base ti tabler-upload"></i>
                                <span>Upload Other Document</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-end">
                            <div class="btn-group" role="group" aria-label="Basic outlined example">
                                <button type="button" id="toogle-list-item"
                                    class="btn btn-icon btn-text-secondary rounded-pill waves-effect"
                                    data-bs-toggle="layout">
                                    <i
                                        class="tabler-layout-list icon-base ti icon-22px theme-icon-active text-heading"></i>
                                </button>
                                <button type="button" id="toogle-grid-item"
                                    class="btn btn-icon btn-text-secondary rounded-pill waves-effect"
                                    data-bs-toggle="layout" disabled>
                                    <i
                                        class="tabler-layout-grid icon-base ti icon-22px theme-icon-active text-heading"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-1">
                <nav id="breadcrumb"></nav>
            </div>
        </div>
        <div class="card-body" id="grid-folder" style="max-height: 300px; overflow-x: auto;">

        </div>
        <div class="card-body" id="list-folder" style="display: none;">
            <table id="documentTable" class="table table-hover w-100">
                <thead style="font-size: 18px;">
                    <tr>
                        <th><input type="checkbox" class="form-check-input"></th>
                        <th>NAME</th>
                        <th>CREATED BY</th>
                        <th style="white-space: nowrap;">DATE UPDATE</th>
                        <th style="white-space: nowrap;">FILE SIZE</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="card-body text-center" id="doc-loading" style="display:none;">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2 fw-semibold">Loading data...</div>
        </div>
    </div>
</div>
