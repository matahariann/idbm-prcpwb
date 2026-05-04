import axios from 'axios';
import SupplierForm from './supplier-form';
import { toast } from '../../../../helpers';
import Swal from 'sweetalert2';

class Supplier {
    #supplierTable = $('#factwmf002-table');
    #supplierEndpoint = 'FACTWM/bd/master-vendor';

    constructor() {
        this.form = new SupplierForm(() => {
            this.#supplierTable.DataTable().ajax.reload();
        });
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
        $(document).on('click', '#btn-sync', async function (e) {
            e.preventDefault();

            self.#syncData();
        });

        $(document).on('click', '.btn-view-methods', async function () {
            let tr = $(this).closest('tr');
            let table = $('#factwmf002-table').DataTable();
            let row = table.row(tr);

            let supplierId = $(this).data('id');
            let icon = $(this).find('i');
            icon.attr('class', 'menu-icon icon-base ti tabler-square-minus');

            if (row.child.isShown()) {
                row.child.hide();
                icon.attr('class', 'menu-icon icon-base ti tabler-square-plus');
                return;
            }

            try {
                const response = await axios.get(`${self.#supplierEndpoint}/${supplierId}`);

                row.child(self.#methodsTable(response.data.data.methods)).show();
            } catch (error) {
                console.error(error);
                toast.error('Failed to load methods');
            }
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
                toast.error('Please select at least one supplier to export.');
                return;
            }

            const url = new URL(window.location.origin + '/FACTWM/bd/master-vendor/export');
            selectedIds.forEach((id) => url.searchParams.append('ids[]', id));

            window.location.href = url.toString();
        });

        $(document).on('change', '#entries', e => {
            const perPage = $(e.target).val();
            this.#supplierTable.DataTable().page.len(perPage).draw();
        });
        // $(document).on('click', '.delete-application', function () {
        //     self.#deleteData($(this).data('id'));
        // });
    }

    #methodsTable(methods = []) {
        let rows = methods
            .map(
                (method, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${method.VUSERNAME ?? '-'}</td>
                    <td>${method.VNAME ?? '-'}</td>
                    <td>${method.VDESCRIPTION ?? '-'}</td>
                    <td>${method.VMETHOD_ID ?? '-'}</td>
                    <td>${method.VVALUE ?? '-'}</td>
                </tr>
            `
            )
            .join('');

        return `
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Type</th>
                        <th>Communication Method</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        `;
    }

    async #syncData() {
        try {
            const response = await axios.post(this.#supplierEndpoint);

            if (response.status >= 200 && response.status < 300) {
                this.#supplierTable.DataTable().ajax.reload();
                toast.success('Supplier synced successfully!');
            } else {
                toast.error('Unexpected response from server.');
            }
        } catch (error) {
            // Server responded with an error
            if (error.response) {
                toast.error(
                    `Error ${error.response.status}: ${error.response.data?.message ?? 'Failed to sync supplier'}`
                );
            } else {
                // No response (network error, timeout, CORS, etc.)
                toast.error('Network error. Please try again.');
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new Supplier().init();
});
