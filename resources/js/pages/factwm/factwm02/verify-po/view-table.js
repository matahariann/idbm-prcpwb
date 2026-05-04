import axios from "axios";
import moment from "moment/moment";
import { showLoadingSwal, closeSwal } from "../../../../swallLoading";
import { _showInvalidError, toast } from "../../../../helpers";

export default class ViewTable {
    instance = null;
    #verifyPoEndPoint = 'FACTWM/ts/verify-po';
    #ppn = window.APP_CONFIG.ppn;
    #rumusDpp = window.APP_CONFIG.rumus_dpp;
    #selectedGrNumbers = new Set();
    #receiptAmounts = new Map();
    #selectableGrNumbers = new Set();
    #selectableRequestKey = null;
    #selectableRowsCache = [];

    init() {
        const self = this;

        this.instance = $('#view-table').DataTable({
            processing: false,
            serverSide: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            order: [
                [4, 'asc']
            ],
            dom:
                'r' +
                "<'table-responsive border-top'tr>" +
                "<'d-flex align-items-center justify-content-center justify-content-lg-between flex-wrap gap-2 text-center px-6 mt-6'ip>",
            ajax: {
                url: `/${self.#verifyPoEndPoint}/view-table`,
            },
            buttons: [
                {
                    extend: 'excel',
                    className: 'd-none',
                    filename: function () {
                        return 'view_' + moment().format('YYYYMMDDHHmmss');
                    },
                    exportOptions: {
                        rows: function (idx, data, node) {
                            return $(node).find("input[name='selected[]']").prop('checked');
                        },
                        columns: [1, 2, 3, 4, 5, 6, 7, 8]
                    }
                }
            ],
            columns: this.#columns(),
            columnDefs: [
                {
                    className: 'text-center text-nowrap',
                    targets: '_all' // apply to all columns
                }
            ],

            initComplete: function () {
                var table = this.api();
                self.#searcRow(table);

                $('.select-all')
                    .off('click')
                    .on('click', function () {
                        self.#toggleSelectAll(this.checked);
                    });

                $('#view-table tbody')
                    .off('change', "input[name='selected[]']")
                    .on('change', "input[name='selected[]']", function () {
                        const $checkbox = $(this);
                        const tr = $checkbox.closest('tr');
                        const row = self.instance.row(tr);
                        const data = row.data();
                        const isChecked = $checkbox.is(':checked');

                        if (data && !$checkbox.data('_syncing')) {
                            self.#syncSelectionState(data, isChecked);
                        }

                        self.#applySelectionToVisibleRows();
                        self.#refreshSelectionUI();
                    });
                self.instance.on('draw', function () {
                    self.#applySelectionToVisibleRows();
                    self.#refreshSelectionUI();
                    self.#syncSelectableUniverse();
                });
                self.#events();
                self.#syncSelectableUniverse();
            }
        });

        self.#entriesEvent();
        self.#clickEvents();
    }

    getSelectedGrNumbers() {
        return Array.from(this.#selectedGrNumbers);
    }

    #entriesEvent() {
        const self = this;

        $('#entries').val(String(this.instance.page.len()));

        $(document).off('change', '#entries').on('change', '#entries', function () {
            const perPage = parseInt($(this).val(), 10);
            self.instance.page.len(perPage).draw();
        });
    }

    #summarizeAmount() {
        let summary = 0;

        this.#receiptAmounts.forEach((amount, vgrNumber) => {
            if (!this.#selectedGrNumbers.has(vgrNumber)) return;
            summary += parseFloat(amount) || 0;
        });

        let dpp = (this.#rumusDpp) * summary;
        let ppn = summary * (this.#rumusDpp) * (this.#ppn / 100);
        let gross = summary + ppn;

        dpp = this.#currencyFormat(dpp);
        ppn = this.#currencyFormat(ppn);
        gross = this.#currencyFormat(gross);
        summary = this.#currencyFormat(summary);

        $('#dpp').val(dpp)
        $('#ppn').val(ppn)
        $('#gross-summary').val(gross)
        $('#summary').val(summary)
        $('#selected-length').html(this.#selectedGrNumbers.size);
    }

    #syncSelectionState(data, isChecked) {
        const targets = new Set();

        if (data?.VREF_TYPE === 'RECEIPT' && data?.VGR_NUMBER) {
            const total = Array.isArray(data?.details)
                ? data.details.reduce((sum, item) => sum + (parseFloat(item.VAMOUNT) || 0), 0)
                : 0;

            this.#receiptAmounts.set(data.VGR_NUMBER, total);
        }

        if (data?.VGR_NUMBER) {
            targets.add(data.VGR_NUMBER);
        }

        if (Array.isArray(data?.PAIR_VGR_NUMBERS)) {
            data.PAIR_VGR_NUMBERS.forEach((vgrNumber) => {
                if (vgrNumber) {
                    targets.add(vgrNumber);
                }
            });
        } else if (data?.PAIR_VGR_NUMBER) {
            targets.add(data.PAIR_VGR_NUMBER);
        }

        targets.forEach((vgrNumber) => {
            if (!vgrNumber) return;

            if (isChecked) {
                this.#selectedGrNumbers.add(vgrNumber);
            } else {
                this.#selectedGrNumbers.delete(vgrNumber);
            }
        });
    }

    #applySelectionToVisibleRows() {
        const self = this;

        self.instance.rows({ page: 'current' }).every(function () {
            const data = this.data();
            const $checkbox = $(this.node()).find("input[name='selected[]']");

            if (!data || !$checkbox.length) return;

            const shouldCheck = self.#selectedGrNumbers.has(data.VGR_NUMBER);
            $checkbox.data('_syncing', true).prop('checked', shouldCheck).data('_syncing', false);
        });
    }

    #refreshSelectionUI() {
        $('#btn-delete-selected').toggleClass('d-none', this.#selectedGrNumbers.size === 0);
        $('#match').prop('disabled', this.#selectedGrNumbers.size === 0);

        const allSelectableChecked = this.#selectableGrNumbers.size > 0
            && [...this.#selectableGrNumbers].every((vgrNumber) => this.#selectedGrNumbers.has(vgrNumber));

        $('.select-all').prop('checked', allSelectableChecked);

        this.#summarizeAmount();
    }

    #toggleSelectAll(isChecked) {
        if (!isChecked) {
            this.#selectedGrNumbers.clear();
            this.#receiptAmounts.clear();
            this.#applySelectionToVisibleRows();
            this.#refreshSelectionUI();
            return;
        }

        this.#selectAllAcrossPages();
    }

    async #selectAllAcrossPages() {
        try {
            showLoadingSwal();

            const rows = await this.#fetchSelectableRows();

            this.#selectedGrNumbers.clear();
            this.#receiptAmounts.clear();
            this.#selectableGrNumbers.clear();

            rows.forEach((row) => {
                if (!row?.VGR_NUMBER) {
                    return;
                }

                this.#selectedGrNumbers.add(row.VGR_NUMBER);
                this.#selectableGrNumbers.add(row.VGR_NUMBER);
                this.#receiptAmounts.set(row.VGR_NUMBER, parseFloat(row.AMOUNT) || 0);

                if (Array.isArray(row.PAIR_VGR_NUMBERS)) {
                    row.PAIR_VGR_NUMBERS.forEach((pairVgrNumber) => {
                        if (pairVgrNumber) {
                            this.#selectedGrNumbers.add(pairVgrNumber);
                            this.#selectableGrNumbers.add(pairVgrNumber);
                        }
                    });
                }
            });

            this.#applySelectionToVisibleRows();
            this.#refreshSelectionUI();
        } catch (error) {
            $('.select-all').prop('checked', false);
            _showInvalidError(error);
        } finally {
            closeSwal();
        }
    }

    async #syncSelectableUniverse() {
        try {
            const rows = await this.#fetchSelectableRows();
            const selectableGrNumbers = new Set();

            rows.forEach((row) => {
                if (!row?.VGR_NUMBER) {
                    return;
                }

                selectableGrNumbers.add(row.VGR_NUMBER);
                this.#receiptAmounts.set(row.VGR_NUMBER, parseFloat(row.AMOUNT) || 0);

                if (Array.isArray(row.PAIR_VGR_NUMBERS)) {
                    row.PAIR_VGR_NUMBERS.forEach((pairVgrNumber) => {
                        if (pairVgrNumber) {
                            selectableGrNumbers.add(pairVgrNumber);
                        }
                    });
                }
            });

            this.#selectableGrNumbers = selectableGrNumbers;
            this.#refreshSelectionUI();
        } catch (error) {
            // keep current UI state if sync fails
        }
    }

    async #fetchSelectableRows() {
        const params = this.instance.ajax.params() ?? {};
        const requestKey = JSON.stringify(params);

        if (this.#selectableRequestKey === requestKey && this.#selectableRowsCache.length > 0) {
            return this.#selectableRowsCache;
        }

        const response = await axios.get(`/${this.#verifyPoEndPoint}/selectable-grns`, { params });
        const rows = response?.data?.data ?? [];

        this.#selectableRequestKey = requestKey;
        this.#selectableRowsCache = rows;

        return rows;
    }

    #events() {
        const self = this;

        $(document).on('click', '.btn-expand', async function () {
            let tr = $(this).closest('tr');
            let table = $('#view-table').DataTable();
            let row = table.row(tr);
            let data = row.data();

            let icon = $(this).find('i');
            icon.attr('class', 'menu-icon icon-base ti tabler-square-minus');

            if (row.child.isShown()) {
                row.child.hide();
                icon.attr('class', 'menu-icon icon-base ti tabler-square-plus');
                return;
            }

            row.child(self.#child(data.details)).show();

        });
    }

    #child(details = []) {
        let rows = details
            .map(
                (detail, index) => `
                <tr>
                    <td>${detail.VGR_NUMBER ?? '-'}</td>
                    <td>${detail.VMATERIAL_CODE ?? '-'}</td>
                    <td>${detail.VDESCRIPTION ?? '-'}</td>
                    <td>${detail.IQTY ?? '-'}</td>
                    <td>${detail.UOM ?? '-'}</td>
                    <td>${this.#currencyFormat(detail.VPRICE) ?? '-'}</td>
                    <td>${this.#currencyFormat(detail.VAMOUNT) ?? '-'}</td>
                </tr>
            `
            )
            .join('');

        return `
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>GRN Number</th>
                        <th>Material Number</th>
                        <th>Description</th>
                        <th>QTY</th>
                        <th>UOM</th>
                        <th>Price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        `;
    }

    #searcRow(table) {
        var tfoot = table.table().footer();
        var headerCells = $(table.table().header()).find('th');

        $(tfoot)
            .find('tr th')
            .each(function (index) {
                var column = table.column(index);
                var title = $(headerCells[index]).text();
                var skippedColumns = [0, 1, 2];

                // Skip first column (checkbox) and last column (actions)
                if (skippedColumns.includes(index) || index === $(tfoot).find('tr th').length - 1) {
                    $(this).html('');
                } else if (index == 2) {
                    var select = $('<select>', {
                        class: 'form-control form-control-sm',
                    });

                    select.append($('<option>', { value: '', text: 'All' }));
                    select.append($('<option>', { value: 'PENDING', text: 'OPEN' }));
                    select.append($('<option>', { value: 'PAID', text: 'PAID' }));
                    select.append($('<option>', { value: 'PRELIMENARY', text: 'PRELIMENARY' }));
                    select.append($('<option>', { value: 'ESKALATED', text: 'ESKALATED' }));
                    select.append($('<option>', { value: 'CANCEL', text: 'CANCEL' }));
                    select.append($('<option>', { value: 'WAITING', text: 'WAITING' }));

                    $(this).html(select);

                    select.on('change', function () {
                        column.search(select.val()).draw();
                    });
                } else {
                    // Check if the header text contains "Date" (case-insensitive)
                    if (/date/i.test(title)) {
                        var input = $('<input>', {
                            class: 'form-control form-control-sm daterange-input',
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
                            class: 'form-control form-control-sm',
                            placeholder: 'Search...'
                        });

                        $(this).html(input);

                        input.on('keyup', function () {
                            column.search(input.val()).draw();
                        });
                    }
                }
            });

        var footer = $('#view-table').find('tfoot tr');
        var header = $('#view-table').find('thead');

        // Move the tfoot row into thead (after the header row)
        footer.appendTo(header);
    }

    #columns() {
        const self = this;

        const columns = [
            {
                data: 'IID',
                name: 'IID',
                orderable: false,
                searchable: false,
                render: (data, type, row) => {

                    const isDisabled = row.VSTATUS_SUBMITTED !== 'PENDING' || row.VREF_TYPE !== 'RECEIPT';

                    return `
                        <div class="d-flex gap-2 align-items-center">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                name="selected[]"
                                value="${data}"
                                ${isDisabled ? 'disabled' : ''}
                            >

                            <button
                                class="btn btn-sm btn-expand"
                                data-id="${data}"
                                ${isDisabled ? 'disabled' : ''}
                            >
                                <i class="menu-icon icon-base ti tabler-square-plus"></i>
                            </button>
                        </div>`;
                }
            },
            // {
            //     data: null,
            //     name: null,
            //     orderable: false,
            //     searchable: false,
            //     render: (data, __, row) => {

            //         let formatted = `<a href="" target="_blank" class="btn btn-sm btn-outline-primary" title="View PDF">
            //                 <i class="ti tabler-file-type-pdf"></i>
            //             </a>`;

            //         return formatted;
            //     }
            // },
            {
                data: null,
                name: null,
                orderable: false,
                searchable: false,
                width: '20px',
                render: (data, __, row, meta) => {
                    return meta.row + 1;
                }
            },
            // 🔥 PREVIEW PDF (BARU)
            {
                data: null,
                name: 'preview_pdf',
                orderable: false,
                searchable: false,
                render: (data, __, row) => {
                    let button = '';
                    if (row.VSTATUS_SUBMITTED == 'PRELIMENARY') {
                        button += `
                            <a
                                href="/${self.#verifyPoEndPoint}/preview-pdf-grn/${row.IID}"
                                target="_blank"
                                class="btn btn-sm btn-outline-primary"
                                title="Preview PDF"
                            >
                                <i class="ti tabler-file-type-pdf"></i>
                            </a>
                        `
                    }
                    return button;
                }
            },
            {
                data: 'VSTATUS_SUBMITTED', name: 'VSTATUS_SUBMITTED',
                render: (data, __, row) => {
                    return self.#statusStyling(data, row);
                }
            },
            {
                data: 'VREF_TYPE', name: 'VREF_TYPE',
                render: (data, __, row) => {
                    const refStyle = {
                        'RECEIPT': 'badge bg-label-success',
                        'RETURN': 'badge bg-label-danger',
                    }[data] || 'badge bg-label-secondary';
                    return `<span class="${refStyle}">${data}</span>`;
                }
            },
            {
                data: 'VRETURN_REF', name: 'VRETURN_REF'
            },
            { data: 'VGR_NUMBER', name: 'VGR_NUMBER' },
            {
                data: 'DGR', name: 'DGR',
                render: (data, __, row) => {
                    return moment(data).format('DD-MM-Y')
                }
            },
            { data: 'VPO_NUMBER', name: 'VPO_NUMBER' },
            { data: 'VDELIVERY_NUMBER', name: 'VDELIVERY_NUMBER' },
            {
                data: null,
                name: null,
                orderable: false,
                searchable: false,
                render: (data, __, row) => {
                    let total = row.details.reduce((sum, item) => sum + (parseFloat(item.VAMOUNT) || 0), 0);

                    let formatted = this.#currencyFormat(total);

                    return formatted;
                }
            },
        ];

        return columns;
    }

    #currencyFormat(value) {
        let formatted = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'IDR'
        })
            .format(value)
            .replace(/[A-Z]{3}\s?/i, '');

        return formatted;
    }

    #statusStyling(status, row) {
        const id = row?.IID;
        const payload = JSON.stringify(row?.payload ?? []);
        let styled = '';

        switch (status) {
            case "PAID":
                styled = `<span class="badge bg-label-success">${status}</span>`
                break;
            case "PRELIMENARY":
                styled = `<span class="badge bg-blue">${status}</span>`
                break;
            case "ESKALATED":
                styled = `<span class="badge bg-label-secondary">${status}</span>`
                break;
            case "WAITING":
                styled = `<span class="badge bg-label-info">${status}</span>`
                break;
            case "FAILED":
                styled = row?.VREF_TYPE === 'RECEIPT'
                    ? `
                        <div class="d-inline-flex align-items-center gap-2">
                            <span class="badge bg-label-danger px-3 py-2">
                                ${status}
                            </span>

                            <span class="resend-si d-inline-flex align-items-center justify-content-center bg-secondary text-white rounded"
                                style="cursor:pointer; width:30px; height:30px;"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Resend SI"
                                data-id="${id}"
                                data-payload='${payload}'>
                                <i class="ti tabler-reload" style="font-size:16px;"></i>
                            </span>
                        </div>
                    `
                    : `<span class="badge bg-label-danger">${status}</span>`
                break;
            case "REJECTED":
                styled = `<span class="badge bg-label-dark">${status}</span>`
                break;
            default:
                styled = `<span class="badge bg-label-warning">${status}</span>`
                break;
        }

        return styled;
    }

    #clickEvents() {
        const self = this;

        $(document).on('click', '.resend-si', function (e) {
            let id = $(e.currentTarget).attr('data-id');
            let payload = $(e.currentTarget).attr('data-payload');
            const formData = new FormData();
            // formData.append('notes', notes);
            formData.append('payload', payload);
            showLoadingSwal();
            axios
                .post(`/${self.#verifyPoEndPoint}/reset-si/${id}`, formData)
                .then(response => {
                    const data = response.data.data;
                    toast.success(response.data.message);
                    $('#view-table').DataTable().ajax.reload();
                })
                .catch(error => {
                    toast.error(error.response.data.message);
                }).finally(() => {
                    closeSwal();
                })
        });
    }
}
