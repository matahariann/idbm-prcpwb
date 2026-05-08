import axios from 'axios';
import { toast } from '../../../../helpers';
import Swal from 'sweetalert2';

class Vendor {
    #vendorTable = $('#prcpwbf002-table');
    #vendorEndpoint = 'FACTWM/bd/master-vendor';
    #keyword = '';

    init() {
        const self = this;
        // DataTable init
        this.#vendorTable.on('init.dt', function () {
            var tfoot = self.#vendorTable.find('tfoot tr');
            var thead = self.#vendorTable.find('thead');
            if (tfoot.length) {
                tfoot.appendTo(thead);
            }
        });

        this.#vendorTable.on('preXhr.dt', function(e, settings, data) {
            if (self.#keyword) {
                data['keyword'] = self.#keyword;
            }
        });

        this.#filterEvents();
    }

    #filterEvents() {
        $(document).on('change', '#entries', e => {
            const perPage = $(e.target).val();
            const table = this.#vendorTable.DataTable();
            table.page.len(perPage).draw();
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
        $(document).on('click', '#btn-sync', async function (e) {
            e.preventDefault();

            self.#syncData();
        });

        $(document).on('click', '#btn-delete-selected', function () {
            const id = $("input[name='selected[]']:checked").val();
            self.form.openModal(id);
        });

        $(document).on('click', '#btn-eksport', function () {
            const selectedIds = $("input[name='selected[]']:checked")
                .map(function () {
                    return $(this).val();
                })
                .get();

            if (selectedIds.length === 0) {
                toast.error('Please select at least one vendor to export.');
                return;
            }

            const url = new URL(window.location.origin + '/FACTWM/bd/master-vendor/export');
            selectedIds.forEach((id) => url.searchParams.append('ids[]', id));

            window.location.href = url.toString();
        });

        $(document).on('change', '#entries', e => {
            const perPage = $(e.target).val();
            this.#vendorTable.DataTable().page.len(perPage).draw();
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new Vendor().init();
});