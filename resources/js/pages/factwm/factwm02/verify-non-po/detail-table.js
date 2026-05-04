export default class DetailTable {
    #table = $('#purchase-detail-table');
    #unitOptions = ['-', 'Pcs', 'Box', 'Kg'];
    #ppn = window.APP_CONFIG.ppn;
    #rumusDpp = window.APP_CONFIG.rumus_dpp;
    #verify_non_po_list_unit = window.APP_CONFIG.verify_non_po_list_unit;
    #pkpSupplier = window.APP_CONFIG.pkp_supplier;

    init() {
        this.#events();
    }

    #events() {
        this.#onClick();
        this.#onChange();
    }

    #onClick() {
        const self = this;

        $(document).on('click', '#purchase-detail-table .add-detail', function () {
            self.#addRow($(this));
            self.#summarizeDetail();
        });

        $(document).on('click', '#purchase-detail-table .remove-detail', function () {
            $(this).closest('tr').remove();
            self.#summarizeDetail();
        });
    }

    #onChange() {
        const self = this;

        $(document).on('input change', '#purchase-detail-table .qty', function () {
            const row = $(this).closest('tr');
            const qty = parseFloat($(this).val()) || 0;
            let rawPrice = row.find('.price').val().replace(/\D/g, '');
            let price = parseFloat(rawPrice) || 0;

            const total = qty * price;
            // set total (formatted)
            row.find('.total').val(
                total ? self.#currencyFormat(total) : ''
            );

            self.#summarizeDetail();
        });

        $(document).on('input change', '#purchase-detail-table .price', function () {
            const row = $(this).closest('tr');

            // ambil angka murni
            let rawPrice = $(this).val().replace(/\D/g, '');
            let price = parseFloat(rawPrice) || 0;

            // format ulang ke currency
            if (rawPrice) {
                $(this).val(self.#currencyFormat(rawPrice));
            } else {
                $(this).val('');
            }

            const qty = parseFloat(row.find('.qty').val()) || 0;

            const total = qty * price;

            // set total (formatted)
            row.find('.total').val(
                total ? self.#currencyFormat(total) : ''
            );

            self.#summarizeDetail();
        });

    }

    #isTaxCodeV0() {
        return (($('#tax_code').val() || '').trim().toUpperCase() === 'V0');
    }

    #summarizeDetail() {
        const self = this;

        const toNumber = (v) => {
            if (v === null || v === undefined) return 0;
            return parseInt(v.toString().replace(/\D/g, ''), 10) || 0;
        };

        const safeNumber = (v) => Number.isFinite(v) ? Math.round(v) : 0;

        let grandTotal = 0;

        this.#table.find('tbody tr.detail-item .total').each(function () {
            grandTotal += toNumber($(this).val());
        });

        let gt = safeNumber(grandTotal);

        let rumusDpp = parseFloat(self.#rumusDpp) || 0;
        let ppnRate = 0;
        if (self.#pkpSupplier && !this.#isTaxCodeV0()) {
            ppnRate = parseFloat(self.#ppn) || 0;
        }

        let dpp = safeNumber(gt * rumusDpp);
        let ppn = safeNumber(dpp * (ppnRate / 100));

        const pph = $('#pph').val();
        let nilai = toNumber($('#nilai-pph').val());

        $('#net_amount').val(
            gt ? this.#currencyFormat(gt) : ''
        );

        let total = gt + ppn;

        if (pph === 'PPh22') {
            total += nilai;
        } else {
            total -= nilai;
        }

        total = safeNumber(total);

        // console.log(total, "total");

        $('#dpp_lain').val(gt ? this.#currencyFormat(dpp) : '');
        $('#ppn').val(gt ? this.#currencyFormat(ppn) : '');
        $('#grand_total').val(this.#currencyFormat(total));
    }


    #addRow(element) {
        const row = element.closest('tr');

        const description = (row.find('.description').val() || '').toString();
        const qtyRaw = (row.find('.qty').val() || '').toString().trim();
        const qty = parseInt(qtyRaw, 10) || 0;

        // ambil raw price (hapus titik)
        const priceFormatted = (row.find('.price').val() || '').toString();
        const priceRaw = priceFormatted.replace(/\D/g, '');
        const price = parseInt(priceRaw, 10) || 0;

        const unit = (row.find('.unit').val() || '-').toString().trim();
        let listOptions = [];

        if (this.#verify_non_po_list_unit !== null) {
            listOptions = this.#verify_non_po_list_unit
                .split(',')
                .map(option => option.trim())
                .filter(Boolean);
        }

        const total = qty * price;

        const $newRow = $('<tr/>', { class: 'detail-item' });
        const $description = $('<input/>', {
            type: 'text',
            class: 'form-control description',
            autocomplete: 'new-password'
        }).val(description);

        const $qty = $('<input/>', {
            type: 'number',
            class: 'form-control qty',
            min: 0,
            step: 1,
            autocomplete: 'new-password'
        }).val(qty || '');

        const $unit = $('<select/>', {
            class: 'form-select unit',
            style: 'width: 100px'
        });
        $unit.append(new Option('-', '-', false, unit === '-'));
        listOptions.forEach(option => {
            $unit.append(new Option(option, option, false, option === unit));
        });

        if (unit !== '-' && listOptions.includes(unit) === false) {
            $unit.append(new Option(unit, unit, false, true));
        }

        const $price = $('<input/>', {
            type: 'text',
            class: 'form-control price',
            autocomplete: 'new-password'
        }).val(price ? this.#currencyFormat(price) : '');

        const $total = $('<input/>', {
            type: 'text',
            class: 'form-control total bg-light',
            readonly: true,
            autocomplete: 'new-password'
        }).val(total ? this.#currencyFormat(total) : '');

        const $removeButton = $(`
            <button type="button" class="btn btn-danger remove-detail">
                <i class="ti tabler-trash"></i>
            </button>
        `);

        $newRow.append($('<td/>').append($description));
        $newRow.append($('<td/>').append($qty));
        $newRow.append($('<td/>').append($unit));
        $newRow.append($('<td/>').append($price));
        $newRow.append($('<td/>').append($total));
        $newRow.append($('<td/>', { class: 'text-center' }).append($removeButton));

        // reset row input
        row.find('.description').val('');
        row.find('.qty').val('');
        row.find('.price').val('');
        row.find('.total').val('');
        row.find('select').prop('selectedIndex', 0);

        this.#table.find('tbody').prepend($newRow);

        this.#summarizeDetail();
    }


    #currencyFormat(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value).replace('Rp', '').trim();
    }
}
