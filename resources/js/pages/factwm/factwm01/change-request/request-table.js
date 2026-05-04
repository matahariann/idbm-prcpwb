import axios from 'axios';
import { toast } from '../../../../helpers';
import moment from 'moment/moment';

export default class RequestTable {
    #supplierEndpoint = 'FACTWM/bd/change-request-vendor';
    instance = null;

    initTable({ isVendor = true }) {
        const self = this;

        this.instance = $('#request-table').DataTable({
            processing: false,
            serverSide: true,
            dom:
                'r' +
                "<'table-responsive border-top'tr>" +
                "<'d-flex align-items-center justify-content-center justify-content-lg-between flex-wrap gap-2 text-center px-6 mt-6'ip>",
            ajax: {
                url: window.location + '/request-table'
            },
            buttons: [
                {
                    extend: 'excel',
                    className: 'd-none',
                    filename: function () {
                        return 'request_' + moment().format('YYYYMMDDHHmmss');
                    },
                    exportOptions: {
                        rows: function (idx, data, node) {
                            return $(node).find("input[name='selected[]']").prop('checked');
                        },
                        columns: [1, 2, 3, 4, 5, 6, 7, 8]
                    }
                }
            ],
            columns: this.#columns(isVendor),
            columnDefs: [
                {
                    className: 'text-center text-nowrap',
                    targets: '_all' // apply to all columns
                }
            ],

            initComplete: function () {
                if (!isVendor) {
                    var table = this.api();
                    self.#searcRow(table);

                    $('#select-all')
                        .off('click')
                        .on('click', function () {
                            var checked = this.checked;
                            $("input[name='selected[]']").prop('checked', checked).trigger('change');
                        });

                    $("input[name='selected[]']")
                        .off('change')
                        .on('change', function () {
                            var anyChecked = $("input[name='selected[]']:checked").length > 0;
                            $('#btn-delete-selected').toggleClass('d-none', !anyChecked);

                            var allChecked =
                                $("input[name='selected[]']").length === $("input[name='selected[]']:checked").length;
                            $('#select-all').prop('checked', allChecked);
                        });
                }
                self.#events();
            }
        });
    }

    #events() {
        const self = this;

        $(document).on('click', '.cancel-saved-request', function () {
            self.#cancelRequest($(this).data('id'));
        });
    }

    #searcRow(table) {
        var tfoot = table.table().footer();
        var headerCells = $(table.table().header()).find('th');

        $(tfoot)
            .find('tr th')
            .each(function (index) {
                var column = table.column(index);
                var title = $(headerCells[index]).text();

                // Skip first column (checkbox) and last column (actions)
                if (index === 0 || index === $(tfoot).find('tr th').length - 1) {
                    $(this).html('');
                } else {
                    // Check if the header text contains "Date" (case-insensitive)
                    if (/date/i.test(title)) {
                        var input = $('<input>', {
                            class: 'form-control daterange-input',
                            placeholder: 'Select date'
                        });

                        $(this).html(input);

                        // Initialize Daterangepicker
                        input.daterangepicker({
                            autoUpdateInput: false, // Prevent showing default dates
                            locale: {
                                autoApply: true,
                                cancelLabel: 'Clear',
                                format: 'YYYY-MM-DD' // Adjust to your desired format
                            }
                        });

                        // Event when a date range is applied
                        input.on('apply.daterangepicker', function (ev, picker) {
                            var startDate = picker.startDate.format('YYYY-MM-DD');
                            var endDate = picker.endDate.format('YYYY-MM-DD');
                            column.search(startDate + ' to ' + endDate).draw(); // Adjust search logic as needed
                            $(this).val(startDate + ' - ' + endDate); // Show selected range in input
                        });

                        // Event for clearing the date picker
                        input.on('cancel.daterangepicker', function (ev, picker) {
                            column.search('').draw();
                            $(this).val('');
                        });
                    } else {
                        var input = $('<input>', {
                            class: 'form-control',
                            placeholder: 'Search...'
                        });

                        $(this).html(input);

                        input.on('keyup', function () {
                            column.search(input.val()).draw();
                        });
                    }
                }
            });

        var footer = $('#request-table').find('tfoot tr');
        var header = $('#request-table').find('thead');

        // Move the tfoot row into thead (after the header row)
        footer.appendTo(header);
    }

    #columns(isVendor) {
        const columns = [
            {
                data: 'VUSERNAME',
                name: 'VUSERNAME',
                render: (data, __, row) => {
                    return data ? data : '-';
                }
            },
            { data: 'VNAME', name: 'VNAME' },
            { data: 'VDESCRIPTION', name: 'VDESCRIPTION' },
            { data: 'VMETHOD_ID', name: 'VMETHOD_ID' },
            { data: 'VVALUE', name: 'VVALUE' },
            { data: 'VTYPE', name: 'VTYPE' },
            {
                data: 'VSTATUS',
                name: 'VSTATUS',
                render: (data, __, row) => {
                    if (!isVendor) {
                        return row.BDOWNLOAD ? 'Download' : '';
                    }

                    return data;
                }
            },
            {
                data: null,
                name: null,
                orderable: false,
                searchable: false,
                render: (data, __, row) => {
                    if (data.BDOWNLOAD == false) {
                        return row.VSTATUS !== 'Cancel'
                            ? `<a href="javascript:void(0)" class="btn btn-danger cancel-saved-request" data-id="${row.IID}">
                                    Cancel
                                </a>`
                            : '-';
                    }
                    return '-';
                }
            }
        ];

        if (!isVendor) {
            columns.pop();

            columns.unshift(
                {
                    data: 'IID',
                    name: 'IID',
                    orderable: false,
                    searchable: false,
                    render: data => {
                        return `<input type="checkbox" class="form-check-input" name="selected[]" value="${data}">`;
                    }
                },
                {
                    data: 'VSUPPLIER_CODE',
                    name: 'VSUPPLIER_CODE'
                },
                {
                    data: 'VSUPPLIER_NAME',
                    name: 'VSUPPLIER_NAME'
                }
            );

            columns.push({
                data: 'DDOWNLOAD',
                name: 'DDOWNLOAD',
                render: (data, __, row) => {
                    return data ? moment(data).format('DD-MM-YYYY') : '';
                }
            });
        }

        return columns;
    }

    #cancelRequest(id) {
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
                    .delete(`${this.#supplierEndpoint}/${id}`)
                    .then(response => {
                        toast.success(response.data.message);
                        this.instance.ajax.reload();
                    })
                    .catch(error => {
                        console.log(error);
                        toast.error(error.response.data.message);
                    });
            }
        });
    }
}
