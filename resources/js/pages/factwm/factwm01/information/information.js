import axios from 'axios';
import InformationForm from './information-form';
import { toast } from '../../../../helpers';
import Swal from 'sweetalert2';

class Application {
    #informationTable = $('#factwmf005-table');
    #informationEndpoint = 'FACTWM/bd/master-information';
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
            $('#factwmf005-table thead tr:eq(1) th:eq(1)').html('');
        });

        this.#filterEvents();
        this.#events();
    }

    #events() {
        const self = this;
        $(document).on('click', '.edit-information', function () {
            const dataId = $(this).data('id');
            window.location.href = `/${self.#informationEndpoint}/update/${dataId}`;
        });

        $(document).on('click', '.delete-information', function () {
            self.#deleteData($(this).data('id'));
        });
        $(document).on('click', '#btn-create', function (e) {
            window.location.href = `/${self.#informationEndpoint}/create`;
        });
        $(document).on('click', '#btn-delete-selected-service', event => {
            event.preventDefault();
            this.#deleteSelected();
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
        const self = this
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
                    .delete(`${self.#informationEndpoint}/${appId}`)
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

    async #deleteSelected() {
        const selectedIds = [];
        $('input[name="selected-service[]"]:checked').each(function () {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            Swal.fire('No Selection', 'Please select at least one news to delete.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${selectedIds.length} informations item(s). This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete them!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await axios.post(`/${this.#informationEndpoint}/bulk-delete`, { ids: selectedIds });
                    if (response.status === 200) {
                        Swal.fire(
                            'Deleted!',
                            `${selectedIds.length} informations item(s) have been deleted.`,
                            'success'
                        );
                        this.#informationTable.DataTable().ajax.reload();
                        $('#btn-delete-selected-service').addClass('disabled');
                        $('#select-all-service').prop('checked', false);
                    }
                } catch (error) {
                    Swal.fire(
                        'Error!',
                        'There was an error deleting the selected news items.',
                        'error'
                    );
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new Application().init();
});
