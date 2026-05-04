import axios from "axios";
import moment from "moment";
import { toast } from "../../../../helpers";

export default class OCRTable {
    instance = null;
    totalAmount = 0;
    ppn = 0;
    grNumber = [];
    #verifyPoEndPoint = 'FACTWM/ts/verify-po';
    #ocrForm = document.getElementById('ocr-form');
    #ppn = window.APP_CONFIG.ppn;
    #rumusDpp = window.APP_CONFIG.rumus_dpp;
    #pkpSupplier = window.APP_CONFIG.pkp_supplier;

    constructor(validation) {
        this.validation = validation;
    }

    init() {
        const self = this;

        this.instance = $('#ocr-table').DataTable({
            processing: false,
            serverSide: true,
            dom:
                'r' +
                "<'table-responsive border-top'tr>" +
                "<'d-flex align-items-center justify-content-center justify-content-lg-between flex-wrap gap-2 text-center px-6 mt-6'ip>",
            ajax: {
                url: `/${self.#verifyPoEndPoint}/ocr-table`,
            },
            buttons: [
                {
                    extend: 'excel',
                    className: 'd-none',
                    filename: function () {
                        return 'ocr_' + moment().format('YYYYMMDDHHmmss');
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
                self.#events();
                self.#syncRemoveButtonsState();
            },
            drawCallback: function () {
                var table = this.api();
                var data = table.rows().data();
                const response = table.ajax.json() || {};
                self.grNumber = [];
                self.totalAmount = 0;

                if (Array.isArray(response.all_gr_number_iids)) {
                    self.grNumber = response.all_gr_number_iids;
                } else {
                    data.each(function (value) {
                        self.grNumber.push(value.IID);
                    });
                }

                if (typeof response.receipt_total_amount !== 'undefined') {
                    self.totalAmount = parseFloat(response.receipt_total_amount) || 0;
                } else {
                    data.each(function (value) {
                        if (value.VREF_TYPE !== 'RECEIPT') return;

                        self.totalAmount += value.details.reduce((sum, item) => sum + (parseFloat(item.VAMOUNT) || 0), 0);
                    });
                }

                let dpp = (self.#rumusDpp) * self.totalAmount;
                let ppn = 0;
                if (self.#pkpSupplier === true) {
                    ppn = dpp * (parseInt(self.#ppn) / 100);
                } else {
                    ppn = 0;
                }
                let gross = self.totalAmount + ppn;

                self.ppn = ppn;

                $('#net-amount').val(self.currencyFormat(self.totalAmount))
                $('#dpp').val(self.currencyFormat(dpp))
                $('#ppn').val(self.currencyFormat(ppn))
                $('#total').val(self.currencyFormat(gross))
                self.#syncRemoveButtonsState();
            }
        });
    }

    #events() {
        const self = this;

        $(document)
            .off('ocr-validation-state-change.verify-po-remove')
            .on('ocr-validation-state-change.verify-po-remove', function () {
                self.#syncRemoveButtonsState();
            });

        $(document).on('click', '.remove-gr-number', function () {
            if ($(this).hasClass('disabled')) {
                return;
            }

            const tr = $(this).closest('tr');
            const table = self.instance;
            const row = table.row(tr);
            const data = row.data();

            let grs = [$(this).data('gr').toString()];

            // Case 1: This row is a RETURN → find the row whose VGR_NUMBER === this row's VRETURN_REF
            if (data.VREF_TYPE === 'RETURN' && data.VRETURN_REF) {
                grs.push(data.VRETURN_REF.toString());
            }

            // Case 2: Another row has VRETURN_REF === this row's VGR_NUMBER → find that row
            const linkedByReturnRef = self.instance
                .rows()
                .data()
                .toArray()
                .filter(r => r.VRETURN_REF === data.VGR_NUMBER)
                .map(r => r.VGR_NUMBER.toString());

            grs = grs.concat(linkedByReturnRef);

            self.#removeGrNumber(grs);
        });

    }

    #syncRemoveButtonsState() {
        const isDisabled = Object.values(this.validation.validationList || {}).every(Boolean);

        $('.remove-gr-number')
            .toggleClass('disabled', isDisabled)
            .css({
                cursor: isDisabled ? 'not-allowed' : 'pointer',
                opacity: isDisabled ? 0.5 : 1,
                'pointer-events': isDisabled ? 'none' : 'auto',
            });
    }

    async #removeGrNumber(data) {
        const self = this;
        let message = 'Are you sure you want to delete this GRN?';
        if (data.length > 1) {
            message = 'This GRN is linked to another GRN through return reference. Are you sure you want to delete all linked GRNs?';
        }

        try {
            const result = await Swal.fire({
                title: 'Delete GRN?',
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d'
            });

            if (result.isConfirmed) {
                // Show loading
                // Send approve request
                const url = `/${this.#verifyPoEndPoint}/remove-gr`;

                const response = await axios.post(url, { gr_number: data })
                if (response.data.success) {
                    toast.success(`GRN ${data} successfully removed`);
                    // self.#ocrForm.reset();
                    self.validation.resetValidation();
                    self.instance.ajax.reload();
                    if (response.data.data === 0) {
                        window.location.href = `/${self.#verifyPoEndPoint}/view`;
                        sessionStorage.removeItem('verify_po_ocr_form_draft');

                        const cachePrefixes = [
                            'laravel_cache_verify_po_last_draft_',
                            'verify_po_last_draft_',
                        ];

                        const removeByPrefix = (storage) => {
                            const keys = [];
                            for (let i = 0; i < storage.length; i += 1) {
                                const key = storage.key(i);
                                if (cachePrefixes.some(prefix => key?.startsWith(prefix))) {
                                    keys.push(key);
                                }
                            }
                            keys.forEach(key => storage.removeItem(key));
                        };

                        removeByPrefix(localStorage);
                        removeByPrefix(sessionStorage);
                    }
                }
            }
        } catch (error) {
            toast.error('Error');
        }
    }

    #columns() {
        const self = this;

        const columns = [
            {
                data: null,
                name: null,
                orderable: false,
                searchable: false,
                render: (data, __, row, meta) => {
                    return meta.row + 1;
                }
            },
            {
                data: 'VREF_TYPE',
                name: 'VREF_TYPE',
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
            { data: 'VDELIVERY_NUMBER', name: 'VDELIVERY_NUMBER' },
            { data: 'VPO_NUMBER', name: 'VPO_NUMBER' },
            {
                data: null, name: null,
                render: (data, __, row) => {
                    let total = row.details.reduce((sum, item) => sum + (parseFloat(item.VAMOUNT) || 0), 0);

                    let formatted = this.currencyFormat(total);

                    return formatted;
                }
            },
            {
                data: null, name: null,
                render: (data, __, row) => {
                    if (row.VREF_TYPE === 'RETURN') {
                        return '';
                    }

                    const isDisabled = Object.values(this.validation.validationList || {}).every(Boolean);

                    return `<i class="menu-icon ti tabler-trash bg-danger remove-gr-number ${isDisabled ? 'disabled' : ''}" style="cursor:${isDisabled ? 'not-allowed' : 'pointer'}; opacity:${isDisabled ? '0.5' : '1'}; pointer-events:${isDisabled ? 'none' : 'auto'};" data-gr="${row.VGR_NUMBER}"></i>`
                }
            }
        ];

        return columns;
    }

    currencyFormat(value) {
        let formatted = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        })
            .format(value)
            .replace(/Rp\s?|[^\d.,]/g, '')
            .trim();

        return formatted;
    }
}
