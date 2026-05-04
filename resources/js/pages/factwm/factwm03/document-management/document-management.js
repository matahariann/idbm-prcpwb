import axios from 'axios';
import { _formToJson, _showInvalidError, toast } from '../../../../helpers.js';
import { DocumentChart } from "./document-chart";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import Loader from './document-loader.js';

class DocumentManagement {
    #chart = null;
    #category = 'Folder';
    #type = 'year';
    #mode = 'grid';
    #parentId = null;
    #inputFileElement = null
    #keyword = null;
    #breadcrumbs = [
        {
            id: null,
            name: 'Home',
            type: 'year',
            category: 'Folder'
        },
    ];
    #uploadModal = new bootstrap.Modal(document.getElementById('upload-modal'));
    #uploadRequirementModal = new bootstrap.Modal(document.getElementById('other-file-warning-modal'));
    #uploadForm = document.getElementById('upload-form');
    #data = [];

    constructor() {
        this.clickEvents();
        this.getData();
        this.getFileProgress();
        this.getDataChart();
        this.initFlatpickr();
        this.changeEvents();
        this.initSelect2();
        this.#chart = new DocumentChart();
        // push halaman home
        this.renderBreadcrumb();
        // this.generateListItem();
    }

    changeEvents() {
        const self = this;
        let searchTimeout2;
        $(document).on('keyup', '#search-input', e => {
            clearTimeout(searchTimeout2);
            searchTimeout2 = setTimeout(() => {
                const keyword = $(e.target).val();
                this.#keyword = keyword;
                this.getData();
            }, 1000);
        });

        $('#file').on('change', function () {
            const file = this.files[0];

            if (!file) return;

            const fileName = file.name.replace(/\.[^/.]+$/, '');

            if (!self.appendFileValidation(file, fileName)) {
                return;
            }

            $('#file-name').removeClass('bg-light');
            $('#file-name').attr('readonly', false);
            $('#file-name').val(fileName);
        });
    }

    initFlatpickr() {
        // Initialize all date range pickers
        document.querySelectorAll('#filter_date').forEach((element) => {
            flatpickr(element, {
                mode: "range",
                dateFormat: "Y-m-d",
                allowInput: true,
                onReady: function (_, __, instance) {
                    if (instance.calendarContainer.querySelector('.flatpickr-apply-button')) {
                        return;
                    }

                    const footer = document.createElement('div');
                    footer.className = 'flatpickr-footer px-2 pb-2 pt-1 d-flex justify-content-end gap-2';

                    const clearButton = document.createElement('button');
                    clearButton.type = 'button';
                    clearButton.className = 'btn btn-sm btn-outline-secondary flatpickr-clear-button';
                    clearButton.textContent = 'Clear';
                    clearButton.addEventListener('click', () => {
                        instance.clear();

                        if ($.fn.DataTable.isDataTable('#factwmf013-table')) {
                            const dt = $('#factwmf013-table').DataTable();
                            if (dt.settings()[0].ajax) {
                                dt.ajax.reload();
                            }
                        }

                        instance.close();
                    });

                    const applyButton = document.createElement('button');
                    applyButton.type = 'button';
                    applyButton.className = 'btn btn-sm btn-primary flatpickr-apply-button';
                    applyButton.textContent = 'Apply';
                    applyButton.addEventListener('click', () => {
                        if ($.fn.DataTable.isDataTable('#factwmf013-table')) {
                            const dt = $('#factwmf013-table').DataTable();
                            if (dt.settings()[0].ajax) {
                                dt.ajax.reload();
                            }
                        }

                        instance.close();
                    });

                    footer.appendChild(clearButton);
                    footer.appendChild(applyButton);
                    instance.calendarContainer.appendChild(footer);
                }
            });
        });

        const DFromContainer = document.querySelector('#date');

        if (DFromContainer) {
            flatpickr(DFromContainer, {
                mode: "single",
                dateFormat: "Y-m-d",
                allowInput: true,
                maxDate: "today"
            });
        }
    }

    generateGridItem() {
        let items = "";
        if (this.#data.length > 0) {
            if (this.#category == 'Folder') {
                items += `<div class='row'>`;
                this.#data.forEach(item => {
                    items += `
                        <div class="col-md-3 mt-3">
                            <div class="card border-light folder-card"
                                style="cursor: pointer;"
                                data-id="${item.id}"
                                data-type="${item.type}"
                                data-category="${item.category}"
                                data-name="${item.filename}"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="${item.filename}">

                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between row-gap-3 column-gap-2">
                                        <div class="d-flex">
                                            <i class="icon-base ti tabler-folder icon-px bg-primary"></i>
                                        </div>
                                        <div class="d-flex">
                                            <h6 class="text-center">${item.short_filename}</h6>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start justify-content-between row-gap-3 column-gap-2">
                                        <div class="d-flex">
                                            <b>${item.total_file.toLocaleString()}</b>
                                        </div>
                                        <div class="d-flex align-item-start justify-content-start">
                                            <p class="text-left">Files</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                items += `</div>`;
            } else {
                items += `<div class='row'>`;
                this.#data.forEach(item => {
                    items += `
                        <div class="col-md-3 mt-3">
                            <div class="card border-light download-file"
                                style="cursor: pointer;"
                                data-id="${item.id}"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="${item.filename}">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between row-gap-3 column-gap-2">
                                        <div class="d-flex">
                                            <i class="icon-base ti tabler-folder icon-22px bg-primary"></i>
                                        </div>
                                        <div class="d-flex">
                                            <h6 class="text-center">${item.short_filename}</h6>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start justify-content-between row-gap-3 column-gap-2">
                                        <div class="d-flex">
                                            <b>${item.size.toLocaleString()}</b>
                                        </div>
                                        <div class="d-flex align-item-start justify-content-start">
                                            <p class="text-left">KB</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                items += `</div>`;
            }
        }

        $('#grid-folder').html(items);

        // Event delegation
        $('#grid-folder').off('click', '.folder-card').on('click', '.folder-card', (e) => {
            const id = $(e.currentTarget).data('id');
            const type = $(e.currentTarget).data('type');
            const category = $(e.currentTarget).data('category');
            const name = $(e.currentTarget).data('name');
            this.handleItemClick(id, type, category, name);
        });

        $('#grid-folder').off('click', '.download-file').on('click', '.download-file', (e) => {
            // const path = $(e.currentTarget).data('path');
            const id = $(e.currentTarget).data('id');
            this.handleDownloadFile(id);
        });
    }

    generateListItem() {
        if ($.fn.DataTable.isDataTable('#documentTable')) {
            return;
        }

        // const filteredData = this.#data.filter(item => item.show_folder == true);
        $('#documentTable').DataTable({
            data: [],
            ajax: null,          // ⬅️ penting
            serverSide: false,
            processing: false,
            deferLoading: 0,
            paging: true,
            searching: false,
            ordering: false,
            info: false,
            autoWidth: false,
            lengthChange: false,
            columns: [
                {
                    data: null,
                    render: () =>
                        `<input type="checkbox" class="form-check-input">`
                },
                {
                    data: 'filename',
                    render: (data, type, row) => {
                        if (row.category === 'Folder') {
                            return `
                                <div class="d-flex align-items-center gap-2 folder-card"
                                    data-id="${row.id}"
                                    data-type="${row.type}"
                                    data-category="${row.category}"
                                    data-name="${row.filename}"
                                    style="cursor:pointer">
                                    <i class="ti tabler-folder icon-22px text-primary fs-4"></i>
                                    <span class="fw-semibold">${row.filename}</span>
                                </div>
                            `;
                        }

                        return `
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti tabler-notes icon-22px text-primary fs-4"></i>
                                <span class="fw-semibold">${row.filename}</span>
                            </div>
                        `;
                    }
                },
                {
                    data: null,
                    render: (_, __, row) => `
                        <div class="d-flex align-items-center gap-2">
                            <img src="/assets/img/initial-logo.svg"
                                class="rounded-circle" width="32" height="32">
                            <div>
                                <div class="fw-semibold">${row.user_name ?? '-'}</div>
                                <small class="text-muted">${row.user_email ?? '-'}</small>
                            </div>
                        </div>
                    `
                },
                {
                    data: 'updated_at',
                    defaultContent: '-',
                    render: val => {
                        const parts = val?.split(' ') ?? [];
                        return parts.length
                            ? `
                                <div>
                                    <div class="fw-semibold">${parts[0]}</div>
                                    <small class="text-muted">${parts[1] ?? ''}</small>
                                </div>
                            `
                            : '-';
                    }
                },
                {
                    data: null,
                    defaultContent: '-',
                    render: (_, __, row) => {
                        if (row.category === 'Folder') {
                            return row.total_file != null
                                ? row.total_file.toLocaleString()
                                : '0';
                        }

                        return row.size != null
                            ? row.size.toLocaleString()
                            : '-';
                    }
                },
                {
                    data: null,
                    render: (_, __, row) =>
                        row.category === 'File'
                            ? `<i class="ti tabler-download icon-32px download-file"
                                data-id="${row.id}" style="cursor:pointer"></i>`
                            : '-'
                }
            ],
            dom:
                "<'row mb-2'<'col-sm-6'l><'col-sm-6'f>>" +
                "<'table-responsive'tr>" +
                "<'d-flex justify-content-end'p>"
        });

        $('#documentTable')
            .off('click', '.folder-card')
            .on('click', '.folder-card', e => {
                const el = e.currentTarget;
                this.handleItemClick(
                    $(el).data('id'),
                    $(el).data('type'),
                    $(el).data('category'),
                    $(el).data('name')
                );
            });

        $('#documentTable')
            .off('click', '.download-file')
            .on('click', '.download-file', e => {
                this.handleDownloadFile($(e.currentTarget).data('id'));
            });
    }

    clickEvents() {
        document.getElementById('toogle-list-item').addEventListener('click', event => {
            event.preventDefault();
            $('#grid-folder').hide();
            $('#list-folder').show();
            $('#toogle-list-item').attr('disabled', true);
            $('#toogle-grid-item').attr('disabled', false);
            this.#mode = 'list';
            this.getData();
            // this.generateListItem();
        });
        document.getElementById('toogle-grid-item').addEventListener('click', event => {
            event.preventDefault();
            $('#list-folder').hide();
            $('#grid-folder').show();
            $('#toogle-grid-item').attr('disabled', true);
            $('#toogle-list-item').attr('disabled', false);
            this.#mode = 'grid';
            this.getData();
            // this.generateGridItem();
        });
        document.getElementById('btn-upload-file').addEventListener('click', event => {
            event.preventDefault();
            this.#uploadForm.reset();
            this.#uploadModal.show();
            this.#inputFileElement = $('#file');
        });
        document.getElementById('btn-upload-file-notif').addEventListener('click', event => {
            event.preventDefault();
            this.#uploadRequirementModal.show();
        });
        document.getElementById('btn-accept-requirement').addEventListener('click', event => {
            event.preventDefault();
            this.#uploadRequirementModal.hide();
            this.#inputFileElement.click();
            // this.#uploadRequirementModal.show();
        });
        document.getElementById('close-modal').addEventListener('click', event => {
            event.preventDefault();
            this.#uploadRequirementModal.hide();
            this.#inputFileElement = null;
        });

        this.#uploadForm.addEventListener('submit', event => {
            event.preventDefault();
            this.submitUploadForm();
        });

        $('#breadcrumb').off('click').on('click', 'a', (e) => {
            e.preventDefault();

            const index = $(e.currentTarget).data('index');
            const type = $(e.currentTarget).data('type');
            const category = $(e.currentTarget).data('category');

            // 🔥 CODE KAMU TARUH DI SINI
            const crumb = this.#breadcrumbs[index];

            if (crumb.type === 'year') {
                this.#breadcrumbs = [
                    {
                        id: null,
                        name: 'Home',
                        type: 'year',
                        category: 'Folder'
                    },
                ];
                this.#parentId = null;
            } else {
                this.#breadcrumbs = this.#breadcrumbs.slice(0, index + 1);
                this.#parentId = crumb.id;
            }
            this.#type = type;
            this.#category = category;

            this.renderBreadcrumb();
            this.getData();
        });
    }

    submitUploadForm() {
        const jsonData = _formToJson(this.#uploadForm);
        const supplierSelect = document.getElementById('supplier');
        let type = 'internal';
        if (supplierSelect.disabled) {
            type = 'supplier';
        }
        jsonData['type'] = type;
        axios
            .post(`FACTWM/rt/document-managements/uploadOtherDocument`, jsonData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                // $('#factwmf001-table').DataTable().ajax.reload();
                toast.success(response.data.message)
                this.#uploadForm.reset();
                this.#uploadModal.hide();
                this.getData();
                this.getFileProgress();
                this.getDataChart();
                if (type == 'internal') {
                    $('#supplier').val(null).trigger('change');
                }
                if ($.fn.DataTable.isDataTable('#factwmf013-table')) {
                    const dt = $('#factwmf013-table').DataTable();
                    if (dt.settings()[0].ajax) {
                        dt.ajax.reload();
                    }
                }
                $('#file-name').addClass('bg-light');
                $('#file-name').attr('readonly', true);
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    _showInvalidError(error.response.data.errors);
                } else {
                    toast.error(error.response.data.message);
                }
            });
    }

    handleItemClick(ID, type, category, name) {
        const nextType = this.setType(type)
        this.#category = nextType === 'file' ? 'File' : 'Folder';
        this.#type = nextType;
        this.#parentId = ID
        this.getData();
        if (category === 'Folder') {
            this.#breadcrumbs.push({
                id: ID,
                name: name,
                type: nextType,
                category: nextType === 'file' ? 'File' : 'Folder'
            });
        }

        this.renderBreadcrumb();
    }

    renderStorage(data) {
        let html = '';

        data.forEach(item => {
            html += `
            <div class="d-flex align-items-start column-gap-2 mb-2">
                <div>
                    <i class="icon-base ti tabler-notes icon-32px bg-${item.color}"></i>
                </div>

                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <div>${item.name}</div>
                        <div>${item.size}</div>
                    </div>

                    <div class="progress mt-1" style="height: 6px;">
                        <div class="progress-bar bg-${item.color}"
                            role="progressbar"
                            style="width: ${item.percent}%;"
                            aria-valuenow="${item.percent}"
                            aria-valuemin="0"
                            aria-valuemax="100">
                        </div>
                    </div>
                </div>
            </div>`;
        });

        document.getElementById('storageList').innerHTML = html;
    }


    setType(type) {
        const listType = ['year', 'month', 'supplier', 'date', 'file_type', 'file'];

        // Cari index dari type yang dikirim
        const currentIndex = listType.indexOf(type);

        // Ambil data index + 1
        if (currentIndex !== -1 && currentIndex < listType.length - 1) {
            const nextCategory = listType[currentIndex + 1];
            return nextCategory;
        } else {
            return null;
        }

    }

    renderBreadcrumb() {
        let html = ``;
        const lastIndex = this.#breadcrumbs.length - 1;

        this.#breadcrumbs.forEach((item, index) => {
            if (index > 0) {
                html += `<span class="mx-1">/</span>`;
            }

            // 🔥 breadcrumb aktif (terakhir) → disabled
            if (index === lastIndex) {
                html += `
                    <span class="fw-semibold text-muted">
                        ${item.name}
                    </span>
                `;
            } else {
                html += `
                    <a href="#"
                    data-index="${index}"
                    data-id="${item.id}"
                    data-type="${item.type}"
                    data-category="${item.category}">
                        ${item.name}
                    </a>
                `;
            }
        });

        $('#breadcrumb').html(html);
    }

    initSelect2() {
        $('#month').select2({
            placeholder: 'Select Month',
            allowClear: true,
            dropdownParent: $('#upload-modal'),
        });

        $('#supplier').select2({
            placeholder: 'Select Supplier',
            allowClear: true,
            dropdownParent: $('#upload-modal'),
        });
    }

    async getFileProgress() {
        await axios
            .get(`FACTWM/rt/document-managements/getFileProgress`, {
                // params: {
                //     category: this.#category,
                //     type: this.#type,
                //     parentID: this.#parentId
                // }
            })
            .then(response => {
                // $('#factwmf001-table').DataTable().ajax.reload();
                // toast.success(response.data.message)
                // this.#uploadForm.reset()
                // this.#uploadModal.hide()
                const data = response.data.data;
                if (data.length > 0) {
                    this.renderStorage(data);
                }
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    _showInvalidError(error.response.data.errors);
                } else {
                    toast.error(error.response.data.message);
                }
            });
    }

    async getDataChart() {
        try {
            const response = await axios.get(
                'FACTWM/rt/document-managements/getDataChart'
            );

            const data = response.data.data;
            this.#chart.init();
            if (data && data.series.length > 0) {
                this.#chart.setData(data.series);
            } else {
                this.#chart.setData([]); // atau data default
            }
            this.#chart.setLabels(data.labels);
            // this.#chart.setTotal(data.total);

            $('.time-spending-chart').show();
            document.getElementById('used-files').innerHTML = data.usedBytes;
            document.getElementById('total-files').innerHTML = data.totalBytes;

        } catch (error) {
            toast.error(error.response?.data?.message || 'Error');
        }
    }

    async getData() {
        Loader.show(this.#mode);
        await axios
            .get(`FACTWM/rt/document-managements/getData`, {
                params: {
                    category: this.#category,
                    type: this.#type,
                    parentID: this.#parentId,
                    keyword: this.#keyword,
                }
            })
            .then(response => {
                // $('#factwmf001-table').DataTable().ajax.reload();
                // toast.success(response.data.message)
                // this.#uploadForm.reset()
                // this.#uploadModal.hide()
                const data = response.data.data;
                this.#data = data;
                if (this.#mode == 'grid') {
                    this.generateGridItem();
                } else {
                    this.generateListItem();

                    const table = $('#documentTable').DataTable();

                    table.clear();
                    table.rows.add(data);
                    table.draw();
                }
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    _showInvalidError(error.response.data.errors);
                } else {
                    toast.error(error.response.data.message);
                }
            })
            .finally(() => {
                Loader.hide(this.#mode);
            });
    }


    async handleDownloadFile(id) {
        Loader.show(this.#mode);
        await axios
            .get(`FACTWM/rt/document-managements/download/${id}`, {
                responseType: 'blob',
                // params: {
                //     category: this.#category,
                //     type: this.#type,
                //     parentID: this.#parentId
                // }
            })
            .then(response => {
                const disposition = response.headers['content-disposition'];
                let filename = 'downloaded-file';

                if (disposition && disposition.includes('filename=')) {
                    filename = disposition.split('filename=')[1].replace(/"/g, '');
                }

                const blob = new Blob([response.data]);
                const url = window.URL.createObjectURL(blob);

                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                a.click();

                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    _showInvalidError(error.response.data.errors);
                } else {
                    toast.error(error.response.data.message);
                }
            }).finally(() => {
                Loader.hide(this.#mode);
                this.getData()
            });
    }

    appendFileValidation(file, newName) {
        const MAX_SIZE_MB = 2;
        const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

        const isPdf =
            file.type === 'application/pdf' ||
            file.name.toLowerCase().endsWith('.pdf');

        if (!file) {
            toast.error('Please select a file');
            return false;
        }

        if (!isPdf) {
            toast.error('Only PDF files are allowed');
            return false;
        }

        if (file.size > MAX_SIZE_BYTES) {
            toast.error(`File size must not exceed ${MAX_SIZE_MB} MB`);
            return false;
        }

        if (!newName) {
            toast.error('Please enter a file name');
            return false;
        }

        return true;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new DocumentManagement();
});
