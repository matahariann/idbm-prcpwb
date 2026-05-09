import axios from 'axios';
import VendorForm from './vendor-form';
import { toast } from '../../../../helpers';
import Swal from 'sweetalert2';

class Vendor {
    #vendorTable = $('#prcpwbf002-table');
    // #vendorEndpoint = 'PRCPWB/bd/master-vendor';
    #keyword = '';
    #selectedIds  = new Set();
    #excludedIds  = new Set();
    #isSelectAllRecords = false;
    #totalRecords = 0;

    constructor() {
        this.form = new VendorForm(() => {
            this.#vendorTable.DataTable().ajax.reload();
        });
    }

    init() {
        const self = this;

        // Pindahkan tfoot ke thead jika diperlukan untuk filter per kolom
        this.#vendorTable.on('init.dt', function () {
            var tfoot = self.#vendorTable.find('tfoot tr');
            var thead = self.#vendorTable.find('thead');
            if (tfoot.length) tfoot.appendTo(thead);
        });

        this.#vendorTable.on('preXhr.dt', function (e, settings, data) {
            if (self.#keyword) data['keyword'] = self.#keyword;
        });

        this.#vendorTable.on('draw.dt', function () {
            const dtApi = self.#vendorTable.DataTable();
            self.#totalRecords = dtApi.page.info().recordsTotal;
            self.#restoreCheckboxStates();
            self.#updateButtonStates();
        });

        this.#filterEvents();
        this.#events();
    }

    #restoreCheckboxStates() {
        $("input[name='selected[]']").each((_, el) => {
            const id = $(el).val();
            if (this.#isSelectAllRecords) {
                $(el).prop('checked', !this.#excludedIds.has(id));
            } else {
                $(el).prop('checked', this.#selectedIds.has(id));
            }
        });

        const totalOnPage   = $("input[name='selected[]']").length;
        const checkedOnPage = $("input[name='selected[]']:checked").length;
        $('#select-all').prop('checked', totalOnPage > 0 && totalOnPage === checkedOnPage);
    }

    #getSelectedCount() {
        if (this.#isSelectAllRecords) {
            return this.#totalRecords - this.#excludedIds.size;
        }
        return this.#selectedIds.size;
    }

    #updateButtonStates() {
        const count = this.#getSelectedCount();

        if (!this.#isSelectAllRecords && count === 1) {
            $('#btn-delete-selected').removeClass('disabled');
        } else {
            $('#btn-delete-selected').addClass('disabled');
        }

        if (count > 0) {
            $('#export-excel').removeClass('disabled');
        } else {
            $('#export-excel').addClass('disabled');
        }
    }

    #filterEvents() {
        $(document).on('change', '#entries', e => {
            this.#vendorTable.DataTable().page.len($(e.target).val()).draw();
        });

        let searchTimeout;
        $(document).on('keyup', '#search-input', e => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.#keyword = $(e.target).val();
                this.#vendorTable.DataTable().ajax.reload();
            }, 500);
        });
    }

    #events() {
        const self = this;

        // Checkbox individual
        $(document).on('change', "input[name='selected[]']", function () {
            const id      = $(this).val();
            const checked = $(this).is(':checked');

            if (self.#isSelectAllRecords) {
                if (!checked) {
                    self.#excludedIds.add(id);
                } else {
                    self.#excludedIds.delete(id);
                }
            } else {
                if (checked) {
                    self.#selectedIds.add(id);
                } else {
                    self.#selectedIds.delete(id);
                }
            }

            self.#restoreCheckboxStates();
            self.#updateButtonStates();
        });

        // Select-all header checkbox
        $(document).on('click', '#select-all', function () {
            if (this.checked) {
                self.#isSelectAllRecords = true;
                self.#selectedIds.clear();
                self.#excludedIds.clear();
            } else {
                self.#clearAllSelections();
            }

            self.#restoreCheckboxStates();
            self.#updateButtonStates();
        });

        // Sync
        $(document).on('click', '#btn-sync', async function (e) {
            e.preventDefault();
            self.#syncData();
        });

        // Delete/Edit
        $(document).on('click', '#btn-delete-selected', function () {
            if (self.#isSelectAllRecords || self.#selectedIds.size !== 1) return;
            const id = [...self.#selectedIds][0];
            if (id) self.form.openModal(id);
        });

        // Export Excel
        $(document).on('click', '#export-excel', function () {
            if (self.#getSelectedCount() === 0) {
                toast.error('Pilih minimal satu vendor untuk diekspor.');
                return;
            }
            self.#exportSelected();
        });
    }

    #buildExportUrl() {
        const params = new URLSearchParams();

        if (this.#isSelectAllRecords) {
            params.append('selectAll', '1');
            if (this.#keyword) params.append('keyword', this.#keyword);
            this.#excludedIds.forEach(id => params.append('excludedIds[]', id));
        } else {
            this.#selectedIds.forEach(id => params.append('ids[]', id));
        }

        return `/PRCPWB/bd/master-vendor/export?${params.toString()}`;
    }

    #exportSelected() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/PRCPWB/bd/master-vendor/export';
        form.style.display = 'none';

        // CSRF Token
        const csrf = document.createElement('input');
        csrf.type  = 'hidden';
        csrf.name  = '_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrf);

        if (this.#isSelectAllRecords) {
            this.#appendHidden(form, 'selectAll', '1');
            if (this.#keyword) this.#appendHidden(form, 'keyword', this.#keyword);
            this.#excludedIds.forEach(id => this.#appendHidden(form, 'excludedIds[]', id));
        } else {
            this.#selectedIds.forEach(id => this.#appendHidden(form, 'ids[]', id));
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    // Helper
    #appendHidden(form, name, value) {
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = name;
        input.value = value;
        form.appendChild(input);
    }

    #clearAllSelections() {
        this.#isSelectAllRecords = false;
        this.#selectedIds.clear();
        this.#excludedIds.clear();
    }

    async #syncData() {
        // ...
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new Vendor().init();
});