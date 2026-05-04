import axios from 'axios';
import InformationForm from './information-form';
import { toast } from '../../../../helpers';
import Swal from 'sweetalert2';

class Application {
    #informationTable = $('#hituamf013-table');
    constructor() {
        this.form = new InformationForm(() => {
            this.#informationTable.DataTable().ajax.reload();
        });
    }

    init() {
        const self = this;
        // DataTable init
        this.#informationTable.on('init.dt', function () {
            var tfoot = self.#informationTable.find('tfoot tr');
            var thead = self.#informationTable.find('thead');

            // Move the tfoot row into thead (after the header row)
            tfoot.appendTo(thead);
        });

        this.#filterEvents();
        this.#events();
    }

    #events() {
        const self = this;
        $(document).on('click', '#btn-create-information', function (e) {
            e.preventDefault();
            self.form.openModal();
        });

        $(document).on('click', '.edit-information', function () {
            window.location.href = '/HITUAM/bd/master-information/update/' + $(this).data('id');
        });

        $(document).on('click', '.delete-information', function () {
            self.#deleteData($(this).data('id'));
        });
        $(document).on('click', '#btn-create', function (e) {
            window.location.href = '/HITUAM/bd/master-information/create';
        });
    }

    #filterEvents() {
        $(document).on('change', '#entries', e => {
            const perPage = $(e.target).val();
            const table = this.#informationTable.DataTable();

            table.page.len(perPage).draw();
        });

        let searchTimeout;
        $(document).on('keyup', '#search-input', e => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const keyword = $(e.target).val();
                this.#updateQuery({ keyword });
            }, 500);
        });
    }

    #updateQuery(params) {
        const table = this.#informationTable.DataTable();
        const currentUrl = new URL(window.location.href);
        const searchParams = new URLSearchParams(currentUrl.search);

        // Update parameters
        for (const key in params) {
            // delete key parameters if its empty for clearance
            if (params[key] === '' || params[key] === null || params[key] === undefined) {
                searchParams.delete(key);
            } else {
                searchParams.set(key, params[key]);
            }
        }

        searchParams.set('page', 1);

        // Update the AJAX URL and reload
        const newUrl = `${currentUrl.pathname}?${searchParams.toString()}`;
        table.ajax.url(newUrl).load();
    }

    #deleteData(appId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You sure want to delete this data? You won't be able to revert this!",
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
                    .delete(`HITUAM/bd/master-information/${appId}`)
                    .then(response => {
                        toast.success(response.data.message);
                        this.#informationTable.DataTable().ajax.reload();
                    })
                    .catch(error => {
                        toast.error(error.response.data.message);
                    });
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new Application().init();
});
