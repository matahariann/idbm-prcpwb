import axios from 'axios';
import ChangeRequestForm from './change-request-form';
import RequestTable from './request-table';
import { toast } from '../../../../helpers';
import Swal from 'sweetalert2';

class ChangeRequest {
    #supplierTable = $('#factwmf003-table');
    #supplierEndpoint = 'FACTWM/bd/change-request-vendor';
    #editingRow = null;
    #onSuccessCallback = null;

    constructor() {
        this.form = new ChangeRequestForm(() => {
            this.#supplierTable.DataTable().ajax.reload();
        });

        this.requestTable = new RequestTable();
    }

    init() {
        const self = this;

        this.#supplierTable.on('init.dt', function () {
            var tfoot = self.#supplierTable.find('tfoot tr');
            var thead = self.#supplierTable.find('thead');

            // Move the tfoot row into thead (after the header row)
            tfoot.appendTo(thead);
        });

        this.#events();
        this.#filterEvents();
        this.requestTable.initTable({ isVendor: true });
    }

    #filterEvents() {
        this.#bindEvents('#search-input', this.#supplierTable);
    }

    #bindEvents(selector, table) {
        if (!table.length) return;

        let searchTimeout;
        $(document).on('keyup', selector, e => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const keyword = $(e.target).val();
                this.#updateQuery(table, { keyword });
            }, 500);
        });
    }

    #updateQuery(table, params) {
        const dataTable = table.DataTable();
        const currentUrl = new URL(dataTable.ajax.url(), window.location.origin);
        const searchParams = new URLSearchParams(currentUrl.search);

        for (const key in params) {
            if (params[key] === '' || params[key] === null || params[key] === undefined) {
                searchParams.delete(key);
            } else {
                searchParams.set(key, params[key]);
            }
        }

        searchParams.set('page', 1);

        const newUrl = `${currentUrl.pathname}?${searchParams.toString()}`;
        dataTable.ajax.url(newUrl).load();
    }

    #events() {
        const self = this;

        $(document).on('click', '.add-new-member', function () {
            self.form.openModal();
        });

        $(document).on('click', '#submit-request', function () {
            axios
                .post(`${self.#supplierEndpoint}/submit-request`)
                .then(response => {
                    toast.success(response.data.message);
                    self.requestTable.instance.ajax.reload();
                })
                .catch(error => {
                    console.log(error);
                    self.error(error.response.data.message);
                });
        });

        $(document).on('click', '.edit-request', function () {
            if (self.#editingRow) return; // prevent multiple editing

            let row = $(this).closest('tr');
            let dataId = $(this).data('id');
            self.#editingRow = row;

            // Define which columns you want editable (by index)
            let editableColumns = [1, 2, 3, 4, 5];
            let originalValues = {};

            // Store original action buttons HTML
            row.data('original-actions', row.find(`#action-buttons-${dataId}`).html());

            editableColumns.forEach(function (index) {
                let cell = row.find('td').eq(index);
                let text = cell.text().trim();

                // store original
                originalValues[index] = text;

                // Convert to input
                cell.html(
                    '<input type="text" class="form-control autosize-input" style="width: 10rem" maxlength="100" value="' +
                        text +
                        '">'
                );
            });

            row.data('original-values', originalValues);

            // Replace edit icon with save icon, keep delete icon
            row.find(`#action-buttons-${dataId}`).html(`
                <a href="javascript:void(0)" class="save-request" data-id="${dataId}">
                    <i class="icon-base ti tabler-check"></i>
                </a>
                <a href="javascript:void(0)" class="cancel-request" data-id="${dataId}">
                    <i class="icon-base ti tabler-x"></i>
                </a>
        `);
        });

        $(document).on('click', '.cancel-request', function () {
            let row = self.#editingRow;
            let dataId = $(this).data('id');

            // restore original values
            let saved = row.data('original-values');
            Object.keys(saved).forEach(function (index) {
                row.find('td').eq(index).text(saved[index]);
            });

            // restore original buttons
            row.find(`#action-buttons-${dataId}`).html(row.data('original-actions'));

            self.#editingRow = null;
        });

        $(document).on('click', '.save-request', function () {
            let row = self.#editingRow;
            let dataId = $(this).data('id');

            let updatedValues = {};
            let editableColumns = [1, 2, 3, 4, 5];
            let columnKeyMap = {
                1: 'Username',
                2: 'Name',
                3: 'Position',
                4: 'Type',
                5: 'Value'
            };

            editableColumns.forEach(function (index) {
                let newValue = row.find('td').eq(index).find('input').val();
                let keyName = columnKeyMap[index];
                if (!newValue.trim()) {
                    toast.error(`${keyName} cannot be empty.`);
                    throw 'Validation error: empty field';
                }
                updatedValues[keyName] = newValue;
            });

            updatedValues['Id'] = dataId;
            updatedValues['request_type'] = 'update';
            console.log(updatedValues);

            axios
                .post(self.#supplierEndpoint, updatedValues, {})
                .then(response => {
                    self.#supplierTable.DataTable().ajax.reload();
                    toast.success(response.data.message);

                    if (self.#onSuccessCallback) {
                        self.#onSuccessCallback();
                    }

                    self.#editingRow = null;
                    self.requestTable.instance.ajax.reload();
                })
                .catch(error => {
                    if (error.response && error.response.status === 422) {
                        const errors = error.response.data.errors;
                        Object.keys(errors).forEach(function (key) {
                            errors[key].forEach(function (errorMsg) {
                                toast.error(errorMsg);
                            });
                        });
                    } else {
                        toast.error(error.response.data.message);
                    }
                });
        });

        $(document).on('click', '.delete-request', function () {
            self.#deleteData($(this).data('id'));
        });
    }

    #deleteData(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'You sure want to delete this data?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            customClass: {
                confirmButton: 'btn btn-outline-danger',
                cancelButton: 'btn btn-primary'
            }
        }).then(result => {
            if (result.isConfirmed) {
                axios
                    .post(`${this.#supplierEndpoint}`, { id: id, request_type: 'delete' })
                    .then(response => {
                        toast.success(response.data.message);
                        this.#supplierTable.DataTable().ajax.reload();
                        this.requestTable.instance.ajax.reload();
                    })
                    .catch(error => {
                        console.log(error);
                        toast.error(error.response.data.message);
                    });
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new ChangeRequest().init();
});
