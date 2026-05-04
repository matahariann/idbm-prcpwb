import axios from 'axios';
import OtherFiles from './other-files';
import DetailTable from './detail-table';
import { _formToJson, _showInvalidError, toast } from '../../../../helpers';
import Validation from "./validation";
import { showLoadingSwal, closeSwal } from '../../../../swallLoading';

class VerifyNonPOForm {
    #grNoteId = null;
    #endPoint = 'FACTWM/ts/verify-non-po';
    #form = document.getElementById('non-po-form');
    #uploadRequirementModal = new bootstrap.Modal(document.getElementById('other-file-warning-modal'));;
    #inputFileElement = null;
    #ppn = window.APP_CONFIG.ppn;
    #rumusDpp = window.APP_CONFIG.rumus_dpp;
    #limitEskalated = window.APP_CONFIG.limit_eskalated;
    #pkpSupplier = window.APP_CONFIG.pkp_supplier;
    #verify_non_po_list_unit = window.APP_CONFIG.verify_non_po_list_unit;
    #IID = window.APP_CONFIG.id;
    #pph = window.APP_CONFIG.pph;
    #object = window.APP_CONFIG.object;
    #dpp_pph = window.APP_CONFIG.dpp_pph;
    #tarrif = window.APP_CONFIG.tarrif;
    #isPreloading = false;
    #requiredFileTax = true;
    #requiredFileInvoice = true;
    #verifiedInvoiceIdentity = null;
    #verifiedTaxIdentity = null;
    #invoiceRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
    #taxRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
    #lastInvoiceFile = null;
    #lastTaxFile = null;

    constructor() {
        this.otherFiles = new OtherFiles();
        this.detailTable = new DetailTable();
        this.validation = new Validation();
        console.log(this.#dpp_pph);
        // console.log(this.#IID);
    }

    init() {
        // if (this.#handleBrowserBackNavigation()) {
        //     return;
        // }

        this.otherFiles.init();
        this.detailTable.init();
        this.#events();
        this.#initFileRequirementState();
        this.#select2();
        this.#clearAddDetailRow();
        window.addEventListener('pageshow', () => this.#clearAddDetailRow());
        this.#restoreDraftFromServerIfReturning();
    }

    #handleBrowserBackNavigation() {
        const navigationEntry = performance.getEntriesByType('navigation')[0];
        const isBackForward = navigationEntry?.type === 'back_forward';
        const cameFromFinalPreview = (document.referrer || '').includes('/FACTWM/ts/verify-non-po/final-preview/');

        if (!isBackForward || cameFromFinalPreview) {
            return false;
        }

        this.#clearDraftCache();
        axios
            .post(`/${this.#endPoint}/clear-ocr-state`)
            .catch(error => {
                console.log(error);
            })
            .finally(() => {
                window.location.href = `/${this.#endPoint}`;
            });

        return true;
    }

    #events() {
        this.#onSubmit();
        this.#onChange();
        this.#onChangeUploadFile();
        this.#onClick();
        this.#setDisabledInput();
        this.#syncTaxCodeFieldState();
    }

    #clearAddDetailRow() {
        const $addRow = $('#purchase-detail-table tbody tr.detail-item').last();
        if (!$addRow.length) {
            return;
        }

        $addRow.find('.description').val('');
        $addRow.find('.qty').val('');
        $addRow.find('.unit').prop('selectedIndex', 0);
        $addRow.find('.price').val('');
        $addRow.find('.total').val('');
    }

    #initFileRequirementState() {
        const mode = ($(this.#form).data('mode') || '').toString().toLowerCase();
        if (mode !== 'update') {
            return;
        }

        const hasExistingInvoiceFile = ($('#invoice-file-name').val() || '').trim() !== '';
        const hasExistingTaxFile = ($('#tax-file-name').val() || '').trim() !== '';

        if (hasExistingInvoiceFile) {
            this.#requiredFileInvoice = false;
            this.#setVerifiedInvoiceIdentity();
        }

        if (hasExistingTaxFile) {
            this.#requiredFileTax = false;
            this.#setVerifiedTaxIdentity();
        }
    }

    #onSubmit() {
        const self = this;

        $('#non-po-form').on('submit', function (e) {
            e.preventDefault();

            if (!self.#validateInvoiceTaxDateMatch(true)) {
                return false;
            }

            const isAllValid = Object.values(self.validation.validationList).every(Boolean);
            if (!isAllValid) {
                toast.error('OCR Invoice or Tax Invoice Not Valid');
                return false;
            }

            const form = $(this);
            const mode = form.data('mode');
            const endpoint = form.data('endpoint');

            // Ambil detail dulu
            const details = self.#getDetails();
            if (!details) {
                return;
            }


            let checkButtonSubmit = self.otherFiles?.checkButtonSubmit();
            if (checkButtonSubmit) {
                toast.error("Please append a other file click Add");
                return;
            }

            if (self.#requiredFileInvoice) {
                toast.error("Please Upload Invoice file");
                return false;
            }

            if (self.#requiredFileTax) {
                toast.error("Please Upload Tax file");
                return false;
            }


            // Gunakan FormData (WAJIB untuk file)
            const formData = new FormData(self.#form);

            // ===============================
            // TAMBAH DATA TAMBAHAN
            // ===============================
            formData.append('details', JSON.stringify(details));

            // Bersihkan format angka Indonesia (9.166.667 → 9166667)
            const netAmount = $('#net_amount').val().replace(/\./g, '');
            const ppn = $('#ppn').val().replace(/\./g, '');
            const dppNilaiLain = $('#dpp_lain').val().replace(/\./g, '');
            const grandTotal = $('#grand_total').val().replace(/\./g, '');

            formData.append('ppn', ppn);
            formData.append('net_amount', netAmount);
            formData.append('dpp_lain', dppNilaiLain);
            formData.append('grand_total', grandTotal);
            formData.append('status', 'WAITING');

            if ($.fn.select2 && $('#object').hasClass('select2-hidden-accessible')) {
                const objectSelect = $('#object').select2('data')?.[0];
                formData.set('object', objectSelect?.text ?? '');
            } else {
                formData.set('object', ''); // fallback
            }

            // ===============================
            // TAMBAH FILE LAINNYA
            // ===============================
            self.otherFiles.files.forEach((item, index) => {
                formData.append(`otherFiles[${index}][file]`, item.file);
                formData.append(`otherFiles[${index}][name]`, item.name);
            });
            formData.append('deleted_other_file_ids', JSON.stringify(self.otherFiles.deletedExistingFileIds || []));

            showLoadingSwal();
            const request = mode === 'update'
                ? axios.post(endpoint, formData)
                : axios.post(endpoint, formData);

            request
                .then(response => {
                    const data = response.data.data;
                    self.#saveDraftSnapshotForBack(data.IID, details);
                    sessionStorage.setItem(self.#getRestoreDraftFlagKey(), '1');
                    sessionStorage.setItem(self.#getLastDraftIdStorageKey(), String(data.IID || ''));
                    // toast.success(response.data.message);
                    window.location.href = `/${self.#endPoint}/final-preview/${data.IID}`;
                }).catch(error => {
                    if (error.response && error.response.status === 422) {
                        _showInvalidError(error.response.data.errors);
                    } else {
                        toast.error(error.response.data.message);
                    }
                }).finally(() => {
                    closeSwal();
                })
        });
    }


    #resetInput() {
        this.#form.reset();
        $('#optional-witholding').addClass('d-none');
        this.#invoiceRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
        this.#taxRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
        this.#lastInvoiceFile = null;
        this.#lastTaxFile = null;
        const $rows = $('#purchase-detail-table tbody tr.detail-item');

        if ($rows.length > 1) {
            $rows.not(':last').remove(); // hapus baris tambahan
        }

        // kosongkan baris pertama (atau satu-satunya baris)
        const $firstRow = $('#purchase-detail-table tbody tr.detail-item').first();
        $firstRow.find('.description').val('');
        $firstRow.find('.qty').val('');
        $firstRow.find('.unit').val('');
        $firstRow.find('.price').val('');
        $firstRow.find('.total').val('');

        $('#pph').val(null).trigger('change');
        $('#net_amount').val(0);
        $('#dpp_lain').val(0);
        $('#ppn').val(0);
        $('#dpp-pph').val(0);
        $('#tarif').val(0);
        $('#nilai-pph').val(0);
        $('#grand_total').val(0);
        $('#other-file-list').empty();
        this.otherFiles?.clearFiles();
    }

    #onChange() {
        let self = this;

        $(document).on('input', '#invoice_number', function () {

            // Hanya izinkan huruf, angka, -, /, ., ,
            $(this).val(
                $(this).val().replace(/[^A-Za-z0-9\-\/\.,]/g, '')
            );
        });

        $(document).on('change', '#invoice_number', function () {
            self.#resetInvoiceUploadRequirement();
        });

        $(document).on('change', '#invoice_date', function () {
            self.#resetInvoiceUploadRequirement();
        });

        $(document).on('change', '#tax_number_supplier', function () {
            self.#resetTaxUploadRequirement();
        });

        $(document).on('change', '#tax_date', function () {
            self.#resetTaxUploadRequirement();
        });

        $('#tax_code').on('change', function () {
            self.#syncTaxCodeFieldState();
        });

        $('#dpp_pph').on('input', function (e) {
            let rawValue = this.value.replace(/\D/g, '');

            if (!rawValue) {
                this.value = '';
                return;
            }

            this.value = self.#currencyFormat(rawValue);
            self.#processTotalAmount($('#tarrif').val());
        });

        $(document).on('change', '#invoice_date, #tax_date', function () {
            self.#validateInvoiceTaxDateMatch(true);
        });
    }

    #resetInvoiceUploadRequirement() {
        if (this.#isInvoiceIdentitySameAsVerified()) {
            return;
        }

        const hasInvoiceFileName = ($('#invoice-file-name').val() || '').trim() !== '';
        const hasSelectedInvoiceFile = ($('#invoice_pdf').val() || '').trim() !== '';

        if (!hasInvoiceFileName && !hasSelectedInvoiceFile && this.#requiredFileInvoice) {
            return;
        }

        $('#invoice_pdf').val(null);
        $('#invoice-file-name').val('');
        $('#verified-invoice').addClass('d-none');
        this.validation.validationList.invoice = false;
        this.#requiredFileInvoice = true;
        this.#lastInvoiceFile = null;
        this.#invoiceRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
    }

    #resetTaxUploadRequirement() {
        const isTaxUploadDisabled = $('#tax_pdf').prop('disabled') || $('#btn-upload-tax').prop('disabled');
        if (isTaxUploadDisabled) {
            return;
        }

        if (this.#isTaxIdentitySameAsVerified()) {
            return;
        }

        const hasTaxFileName = ($('#tax-file-name').val() || '').trim() !== '';
        const hasSelectedTaxFile = ($('#tax_pdf').val() || '').trim() !== '';

        if (!hasTaxFileName && !hasSelectedTaxFile && this.#requiredFileTax) {
            return;
        }

        $('#tax_pdf').val(null);
        $('#tax-file-name').val('');
        $('#verified-tax').addClass('d-none');
        this.validation.validationList.tax = false;
        this.#requiredFileTax = true;
        this.#lastTaxFile = null;
        this.#taxRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
    }

    #setVerifiedInvoiceIdentity() {
        this.#verifiedInvoiceIdentity = {
            invoice_number: ($('#invoice_number').val() || '').trim(),
            invoice_date: ($('#invoice_date').val() || '').trim(),
        };
    }

    #setVerifiedTaxIdentity() {
        this.#verifiedTaxIdentity = {
            tax_number_supplier: ($('#tax_number_supplier').val() || '').trim(),
            tax_date: ($('#tax_date').val() || '').trim(),
        };
    }

    #isInvoiceIdentitySameAsVerified() {
        if (!this.#verifiedInvoiceIdentity) {
            return false;
        }

        const currentInvoiceNumber = ($('#invoice_number').val() || '').trim();
        const currentInvoiceDate = ($('#invoice_date').val() || '').trim();

        return currentInvoiceNumber === this.#verifiedInvoiceIdentity.invoice_number
            && currentInvoiceDate === this.#verifiedInvoiceIdentity.invoice_date;
    }

    #isTaxIdentitySameAsVerified() {
        if (!this.#verifiedTaxIdentity) {
            return false;
        }

        const currentTaxNumber = ($('#tax_number_supplier').val() || '').trim();
        const currentTaxDate = ($('#tax_date').val() || '').trim();

        return currentTaxNumber === this.#verifiedTaxIdentity.tax_number_supplier
            && currentTaxDate === this.#verifiedTaxIdentity.tax_date;
    }

    #onClick() {
        const self = this;

        $('#btn-upload-invoice').on('click', function () {
            // self.#setInvoiceIdentityLock(false);
            self.#uploadRequirementModal.show();
            self.#inputFileElement = $('#invoice_pdf');
        });

        $('#btn-upload-tax').on('click', function () {
            // self.#setTaxIdentityLock(false);
            self.#uploadRequirementModal.show();
            self.#inputFileElement = $('#tax_pdf');

        });

        $('#btn-add-other-file').on('click', function () {
            self.#uploadRequirementModal.show();
            self.#inputFileElement = $('#other-file-input');
        });

        $('#btn-accept-requirement').on('click', function () {
            self.#inputFileElement.click();
            self.#uploadRequirementModal.hide();
        });

        $('#reset-form').on('click', function () {
            self.#resetInput();
            self.#clearDraftCache();
            axios
                .post(`/${self.#endPoint}/clear-ocr-state`)
                .catch(error => {
                    console.log(error);
                })
                .finally(() => {
                    window.location.href = `/${self.#endPoint}`;
                });
        });

        $(document).on('click', '#escalated-button', function () {
            const form = document.querySelector('#non-po-form');

            if (!form.checkValidity()) {
                form.reportValidity(); // ini yang munculin pesan required
                return;
            }

            const details = self.#getDetails();
            if (!details) {
                return;
            }

            if (!self.#validateBeforeSubmit()) {
                return;
            }

            self.#submitEskalatedPO();
        })
    }

    #select2() {
        if (!this.#pph) {
            $('#optional-witholding').addClass('d-none');
        }
        $('#pph').select2({
            placeholder: 'Select PPh',
            allowClear: true,
        });

        this.#select2Events();
    }

    #select2Events() {
        const self = this;
        // Inisialisasi #object select2 saat page load jika ada value
        if (self.#pph) {
            $('#pph').val(self.#pph).trigger('change');
            // ✅ Langsung init #object select2
            this.#initObjectSelect2(self.#pph);
            const pph = (self.#pph || '').toLowerCase();
            if (pph === 'pph4-2') {
                $('#dpp_pph').val($('#net_amount').val()).attr('readonly', true).addClass('bg-gray-50');
                $('#prefix').html('-');
            } else if (pph === 'pph22') {
                $('#dpp_pph').val(self.#currencyFormat(self.#dpp_pph)).attr('readonly', false).removeClass('bg-gray-50');
                $('#prefix').html('+');
            } else {
                $('#dpp_pph').val(self.#currencyFormat(self.#dpp_pph)).attr('readonly', false).removeClass('bg-gray-50');
                $('#prefix').html('-');
            }
        }

        $('#pph').on('select2:select', function (e) {
            const selected = e.params.data;
            // ✅ Init #object select2 saat user manual select
            self.#initObjectSelect2(selected.id);

            self.#resetPphUI();
            if (selected.id !== 'none') {
                $('#optional-witholding').removeClass('d-none');
                // Setup DPP & prefix
                const pph = (selected.id || '').toLowerCase();
                if (pph === 'pph4-2') {
                    $('#dpp_pph').val($('#net_amount').val()).attr('readonly', true).addClass('bg-gray-50');
                    $('#prefix').html('-');
                } else if (pph === 'pph22') {
                    $('#dpp_pph').val(self.#currencyFormat(self.#dpp_pph)).attr('readonly', false).removeClass('bg-gray-50');
                    $('#prefix').html('+');
                } else {
                    $('#dpp_pph').val(self.#currencyFormat(self.#dpp_pph)).attr('readonly', false).removeClass('bg-gray-50');
                    $('#prefix').html('-');
                }

                // Set selected value jika sedang preloading
                if (self.#tarrif) {
                    $('#tarrif').val(self.#tarrif).trigger('input');
                }

                $('#rekap-jasa-pph').attr('required', true);
                $('#rekap-jasa-content').removeClass('d-none');
            } else {
                $('#optional-witholding').addClass('d-none');
                $('#rekap-jasa-pph').attr('required', false);
                $('#rekap-jasa-content').addClass('d-none');
                $('#tarrif').val('');
            }
        });

        // Bind event object
        $('#object')
            .off('select2:select select2:unselect')
            .on('select2:select', function (e) {
                const data = e.params.data;
                $('#tarrif').val(data.tariff).trigger('input');
            })
            .on('select2:unselect', function () {
                $('#tarrif').val('');
                $('#value').val('');
            });

        $('#tarrif').on('input', function () {
            self.#processTotalAmount(this.value);
        });
    }

    #resetPphUI() {
        // $('#pph').val(null).trigger('change');

        $('#object option[data-dynamic="true"]').remove();
        $('#object').val(null).trigger('change');

        $('#tarrif').val(null);
        $('#nilai').val(null);
        $('#dpp_pph').val(null);
        $('#tarif').val(null);
        $('#nilai-pph').val(null);
        $('#grand_total').val(null);

        $('#optional-witholding').addClass('d-none');
        // $('#rekap-jasa-pph').attr('required', false);
        // $('#rekap-jasa-content').addClass('d-none');
    }



    // ✅ Fungsi terpisah untuk init #object select2
    #initObjectSelect2(pphId) {
        const self = this;
        // ✅ Cek apakah sudah menggunakan select2, baru destroy
        if ($('#object').hasClass('select2-hidden-accessible')) {
            $('#object').select2('destroy');
        }
        $('#object').select2({
            placeholder: 'Select object',
            allowClear: true,
            ajax: {
                url: '/general/pph-list',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,
                        pph: pphId
                    };
                },
                processResults: function (response) {
                    const data = response.data?.data || [];
                    return {
                        results: data.map(item => ({
                            id: item.text,
                            text: item.text,
                            tariff: item.itarif,
                        }))
                    };
                },
                cache: true
            }
        });

        // Append value lama HANYA jika ada dan sedang preloading
        if (self.#object && self.#tarrif) {
            const option = new Option(self.#object, self.#tarrif, false, false);
            option.dataset.dynamic = 'true';
            $('#object').append(option);
        } else {
            $('#object').val(null).trigger('change');
        }
    }

    #processTotalAmount(tar) {
        const pph = $('#pph').val();
        const dppPph = parseFloat($('#dpp_pph').val().replace(/\./g, ''));
        const tarrif = parseFloat(tar);
        let value = 0;
        if (dppPph > 0 && tarrif > 0) {
            value = dppPph * (tarrif / 100);

            // jika BUKAN PPh4-2 → minus
            // if (pph !== 'PPh22') {
            //     value = value * -1;
            // }
        }

        $('#nilai').val(this.#currencyFormat(value));
        $('#dpp-pph').val(this.#currencyFormat(dppPph));
        $('#tarif').val(tarrif);
        $('#nilai-pph').val(this.#currencyFormat(value));

        this.#summarizeDetail();
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

        $('#purchase-detail-table tbody tr.detail-item .total').each(function () {
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

        const pph = ($('#pph').val() || '').trim().toLowerCase();
        let nilai = toNumber($('#nilai-pph').val());

        $('#net_amount').val(
            gt ? this.#currencyFormat(gt) : ''
        );

        let total = gt + ppn;

        if (pph === 'pph22') {
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




    #getDetails() {
        let self = this;

        let isValid = true;
        let items = [];
        let firstInvalid = null;

        $('.is-invalid').removeClass('is-invalid');
        $('.table-danger').removeClass('table-danger');

        const rows = $('#purchase-detail-table tbody tr.detail-item');

        if (rows.length < 1) {
            toast.error('Please add at least one item');
            return false;
        }

        rows.each(function (index) {
            let row = $(this);
            let isLastRow = index === rows.length - 1;

            let description = row.find('.description');
            let qty = row.find('.qty');
            let unit = row.find('.unit');
            let price = row.find('.price');
            let total = row.find('.total');

            // 🔹 BARIS TERAKHIR & KOSONG SEMUA → SKIP
            if (isLastRow && self.#isRowEmpty(row)) {
                return; // continue
            }

            // 🔹 VALIDASI (baris normal & last row yg disentuh)
            row.find('.description, .qty, .unit, .price').each(function () {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                    row.addClass('table-danger');

                    if (!firstInvalid) {
                        firstInvalid = $(this);
                    }
                }
            });

            // 🔹 PUSH ITEM (hanya yg dianggap valid/aktif)
            items.push({
                description: description.val(),
                qty: parseInt(qty.val().replace(/\./g, '')) || 0,
                unit: unit.val(),
                price: parseInt(price.val().replace(/\./g, '')) || 0,
                total: parseInt(total.val().replace(/\./g, '')) || 0
            });
        });

        // 🔹 minimal 1 item valid
        if (items.length < 1) {
            toast.error('Please add at least one item');
            return false;
        }

        if (!isValid) {
            toast.error('Please complete all item details');
            firstInvalid.focus();
            return false;
        }

        return items;
    }


    // #currencyFormat(value) {
    //     let formatted = new Intl.NumberFormat('en-US', {
    //         style: 'currency',
    //         currency: 'IDR'
    //     })
    //         .format(value)
    //         .replace(/[A-Z]{3}\s?/i, '');

    //     return formatted;
    // }

    #currencyFormat(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value).replace('Rp', '').trim();
    }

    #onChangeUploadFile() {
        const self = this;

        $('#invoice_pdf').on('click', function () {
            this.value = null;
        });

        $('#invoice_pdf').on('change', function () {
            const invoiceNumber = $('#invoice_number').val().trim();
            const invoiceDate = $('#invoice_date').val().trim();

            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();

            // remove class
            $('#invoice_number').removeClass('is-invalid');
            $('#invoice_date').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            if (invoiceNumber === '' || invoiceDate === '') {
                toast.error('Please input invoice number & date first');
                $(this).val(null);
                return;
            }

            if (!self.#validateInvoiceTaxDateMatch(true)) {
                $(this).val(null);
                return;
            }

            const file = this.files[0];

            if (!file) return;

            const fileName = file.name.replace(/\.[^/.]+$/, '');

            if (!self.otherFiles.appendFileValidation(file, fileName)) {
                return;
            }

            $('#invoice-file-name').val(fileName);
            self.#invoiceRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
            self.#lastInvoiceFile = file;
            self.#validateInvoiceFile(file, $(this));

            self.#requiredFileInvoice = false;
        });

        $('#tax_pdf').on('click', function () {
            this.value = null;
        });
        $('#tax_pdf').on('change', function () {
            const invoiceNumber = $('#tax_number_supplier').val().trim();
            const invoiceDate = $('#tax_date').val().trim();
            const npwp = $('#npwp_supplier').val().trim();
            const npwp_idbm_match = $('#npwp_idbm_match').val().trim();

            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
            // remove class
            $('#tax_number_supplier').removeClass('is-invalid');
            $('#tax_date').removeClass('is-invalid');
            $('#ppn').removeClass('is-invalid');
            $('#tax_number_supplier').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            if (invoiceNumber === '' || invoiceDate === '' || npwp === '') {
                toast.error('Please input invoice number, npwp & date first');
                $(this).val(null);
                return;
            }

            if (!self.#validateInvoiceTaxDateMatch(true)) {
                $(this).val(null);
                return;
            }

            const file = this.files[0];

            if (!file) return;

            const fileName = file.name.replace(/\.[^/.]+$/, '');

            if (!self.otherFiles.appendFileValidation(file, fileName)) {
                return;
            }

            $('#tax-file-name').val(fileName);
            self.#taxRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
            self.#lastTaxFile = file;
            self.#validateTaxFile(file, $(this));

            self.#requiredFileTax = false;
        });
    }

    #validateInvoiceFile(file, $element) {
        const invoiceNumber = $('#invoice_number').val().trim();
        const invoiceDate = $('#invoice_date').val().trim();
        const formData = new FormData();

        formData.append('invoice_file', file);
        formData.append('invoice_number', invoiceNumber);
        formData.append('id', this.#IID);
        formData.append('render_dpi', this.#invoiceRenderDpi);
        formData.append('params', [
            invoiceNumber,
            invoiceDate,
        ]);

        this.validation.validateInvoice(
            `/${this.#endPoint}/validate-invoice`,
            formData,
            $element,
            'invoice',
            (_, valid = []) => {
                const isVerified = this.#isVerifiedByPrefix(valid);
                if (isVerified) {
                    this.#setVerifiedInvoiceIdentity();
                }
            }
        );
    }

    #validateTaxFile(file, $element) {
        const taxNumber = $('#tax_number_supplier').val().trim();
        const taxDate = $('#tax_date').val().trim();
        const npwp = $('#npwp_supplier').val().trim();
        const npwpIdbmMatch = $('#npwp_idbm_match').val().trim();
        const formData = new FormData();

        formData.append('tax_file', file);
        formData.append('tax_number', taxNumber);
        formData.append('id', this.#IID);
        formData.append('render_dpi', this.#taxRenderDpi);
        formData.append('params', [
            taxNumber,
            taxDate,
            npwp,
            npwpIdbmMatch
        ]);

        this.validation.validateInvoice(
            `/${this.#endPoint}/validate-tax`,
            formData,
            $element,
            'tax',
            (_, valid = []) => {
                const isVerified = this.#isVerifiedByPrefix(valid);
                if (isVerified) {
                    this.#setVerifiedTaxIdentity();
                }
            }
        );
    }

    async #submitEskalatedPO() {
        const self = this;
        try {
            const result = await Swal.fire({
                title: 'Eskalated this PO?',
                text: 'Are you sure you want to Eskalated this PO?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d'
            });

            if (result.isConfirmed) {
                const form = $('#non-po-form');
                const mode = form.data('mode');
                const endpoint = form.data('endpoint');
                // console.log(endpoint, "end point");

                // Ambil detail dulu
                const details = self.#getDetails();
                if (!details) {
                    return;
                }

                let checkButtonSubmit = self.otherFiles?.checkButtonSubmit();
                if (checkButtonSubmit) {
                    toast.error("Please append a other file click Add");
                    return;
                }

                // Gunakan FormData (WAJIB untuk file)
                const formData = new FormData(self.#form);

                // ===============================
                // TAMBAH DATA TAMBAHAN
                // ===============================
                formData.append('details', JSON.stringify(details));
                formData.append('status', 'ESCALATED');

                // Bersihkan format angka Indonesia (9.166.667 → 9166667)
                const netAmount = $('#net_amount').val().replace(/\./g, '');
                const ppn = $('#ppn').val().replace(/\./g, '');
                const dppNilaiLain = $('#dpp_lain').val().replace(/\./g, '');
                const grandTotal = $('#grand_total').val().replace(/\./g, '');

                formData.append('ppn', ppn);
                formData.append('net_amount', netAmount);
                formData.append('dpp_lain', dppNilaiLain);
                formData.append('grand_total', grandTotal);

                if ($.fn.select2 && $('#object').hasClass('select2-hidden-accessible')) {
                    const objectSelect = $('#object').select2('data')?.[0];
                    formData.set('object', objectSelect?.text ?? '');
                } else {
                    formData.set('object', ''); // fallback
                }

                // ===============================
                // TAMBAH FILE LAINNYA
                // ===============================
                self.otherFiles.files.forEach((item, index) => {
                    formData.append(`otherFiles[${index}][file]`, item.file);
                    formData.append(`otherFiles[${index}][name]`, item.name);
                });
                formData.append('deleted_other_file_ids', JSON.stringify(self.otherFiles.deletedExistingFileIds || []));

                showLoadingSwal();
                const request = mode === 'update'
                    ? axios.post(endpoint, formData)
                    : axios.post(endpoint, formData);

                request
                    .then(response => {
                        // toast.success(response.data.message);
                        if (mode === 'create') {
                            self.#resetInput();
                        }
                        const data = response.data.data;
                        // toast.success(response.data.message);
                        window.location.href = `/${self.#endPoint}`;
                    }).catch(error => {
                        if (error.response && error.response.status === 422) {
                            _showInvalidError(error.response.data.errors);
                        } else {
                            toast.error(error.response.data.message);
                        }
                    }).finally(() => {
                        closeSwal();
                    })
            }
        } catch (error) {
            toast.error('Error');
        }
    }

    #isRowEmpty(row) {
        return row.find('.description, .qty, .unit, .price')
            .toArray()
            .every(el => !$(el).val());
    }

    #setDisabledInput() {
        const self = this;
        if (self.#pkpSupplier === false) {
            self.#requiredFileTax = false;
            $('#tax_date,#tax_number_supplier,#btn-upload-tax')
                .prop('required', false)
                .prop('disabled', true);
        }
    }

    #syncTaxCodeFieldState() {
        const taxCode = ($('#tax_code').val() || '').trim();
        const isTaxEnabledBySupplier = this.#pkpSupplier !== false;
        const shouldEnableTaxFields = isTaxEnabledBySupplier && taxCode === 'V11';

        $('#tax-code-fields').toggleClass('d-none', !shouldEnableTaxFields);
        $('#tax_number_supplier,#tax_date')
            .prop('required', shouldEnableTaxFields)
            .prop('disabled', !shouldEnableTaxFields)
            .removeClass('is-invalid');
        $('#btn-upload-tax').prop('disabled', !shouldEnableTaxFields);
        if (!shouldEnableTaxFields) {
            this.#requiredFileTax = false;
            $('#tax_pdf').val(null);
            $('#tax-file-name').val('');
            this.validation.setTaxValidationState(this.#isTaxCodeV0(), this.#isTaxCodeV0());
            this.#lastTaxFile = null;
            this.#taxRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
        } else {
            const hasTaxFileName = ($('#tax-file-name').val() || '').trim() !== '';
            this.#requiredFileTax = !hasTaxFileName;

            if (!hasTaxFileName) {
                this.validation.setTaxValidationState(false, false);
            }
        }

        this.#summarizeDetail();
    }

    #validateBeforeSubmit() {
        // const isAllValid = Object.values(this.validation.validationList).every(Boolean);
        // if (!isAllValid) {
        //     toast.error('OCR Invoice or Tax Invoice Not Valid');
        //     return false;
        // }

        if (this.#requiredFileInvoice) {
            toast.error("Please Upload Invoice file");
            return false;
        }

        if (this.#requiredFileTax) {
            toast.error("Please Upload Tax file");
            return false;
        }

        let checkButtonSubmit = this.otherFiles?.checkButtonSubmit();
        if (checkButtonSubmit) {
            toast.error("Please append a other file click Add");
            return false;
        }

        if (!this.#validateInvoiceTaxDateMatch(true)) {
            return false;
        }

        return true;
    }

    #clearValueDate() {
        $('#invoice_date').val('');
        $('#tax_date').val('');
    }

    #setInvoiceIdentityLock(isLocked) {
        $('#invoice_number,#invoice_date')
            .prop('readonly', isLocked)
            .toggleClass('readonly-locked', isLocked);

        // Keep file input enabled so FormData still carries the selected file.
        $('#btn-upload-invoice').prop('disabled', isLocked);
        $('#invoice_pdf').prop('disabled', false);
    }

    #setTaxIdentityLock(isLocked) {
        $('#tax_number_supplier,#tax_date')
            .prop('readonly', isLocked)
            .toggleClass('readonly-locked', isLocked);

        // Keep file input enabled so FormData still carries the selected file.
        $('#btn-upload-tax').prop('disabled', isLocked);
        $('#tax_pdf').prop('disabled', false);
    }

    #isVerifiedByPrefix(valid) {
        const errors = valid.filter(item => item.error);
        return errors.length === 0;
    }

    #validateInvoiceTaxDateMatch(showToast = false) {
        const $invoiceDate = $('#invoice_date');
        const $taxDate = $('#tax_date');

        if ($taxDate.prop('disabled')) {
            $invoiceDate.removeClass('is-invalid');
            $taxDate.removeClass('is-invalid');
            return true;
        }

        const invoiceDate = ($invoiceDate.val() || '').trim();
        const taxDate = ($taxDate.val() || '').trim();

        if (!invoiceDate || !taxDate) {
            $invoiceDate.removeClass('is-invalid');
            $taxDate.removeClass('is-invalid');
            return true;
        }

        const isMatch = invoiceDate === taxDate;
        const message = 'Invoice Date and Tax Invoice Date must be the same';

        $invoiceDate.toggleClass('is-invalid', !isMatch);
        $taxDate.toggleClass('is-invalid', !isMatch);

        if (!isMatch && showToast) {
            toast.error(message);
        }

        return isMatch;
    }

    #getRestoreDraftFlagKey() {
        return 'verify_non_po_restore_from_final_preview';
    }

    #getLastDraftIdStorageKey() {
        return 'verify_non_po_last_draft_id';
    }

    #getDraftSnapshotKey() {
        return 'verify_non_po_form_snapshot';
    }

    #clearDraftCache() {
        sessionStorage.removeItem(this.#getRestoreDraftFlagKey());
        sessionStorage.removeItem(this.#getLastDraftIdStorageKey());
        sessionStorage.removeItem(this.#getDraftSnapshotKey());

        const cachePrefixes = [
            'laravel_cache_verify_non_po_last_draft_',
            'verify_non_po_last_draft_',
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

    #saveDraftSnapshotForBack(draftId, details = []) {
        const payload = {
            id: draftId || this.#IID || null,
            invoice_number: $('#invoice_number').val() || '',
            invoice_date: $('#invoice_date').val() || '',
            invoice_file_name: $('#invoice-file-name').val() || '',
            invoice_badge: this.#getBadgeSnapshot('invoice'),
            tax_code: $('#tax_code').val() || 'V11',
            tax_number_supplier: $('#tax_number_supplier').val() || '',
            tax_date: $('#tax_date').val() || '',
            tax_file_name: $('#tax-file-name').val() || '',
            tax_badge: this.#getBadgeSnapshot('tax'),
            pph: $('#pph').val() || '',
            object: $('#object').val() || '',
            dpp_pph: ($('#dpp_pph').val() || '').replace(/\./g, ''),
            tarrif: $('#tarrif').val() || '',
            nilai: ($('#nilai').val() || '').replace(/\./g, ''),
            net_amount: ($('#net_amount').val() || '').replace(/\./g, ''),
            dpp_lain: ($('#dpp_lain').val() || '').replace(/\./g, ''),
            ppn: ($('#ppn').val() || '').replace(/\./g, ''),
            grand_total: ($('#grand_total').val() || '').replace(/\./g, ''),
            details: Array.isArray(details) ? details : [],
            other_files: Array.isArray(this.otherFiles.existingFiles) ? this.otherFiles.existingFiles : [],
        };

        sessionStorage.setItem(this.#getDraftSnapshotKey(), JSON.stringify(payload));
    }

    async #restoreDraftFromServerIfReturning() {
        const navigationEntry = performance.getEntriesByType('navigation')[0];
        const isBackForward = navigationEntry?.type === 'back_forward';
        const isReload = navigationEntry?.type === 'reload';
        const cameFromFinalPreview = (document.referrer || '').includes('/FACTWM/ts/verify-non-po/final-preview/');
        const hasRestoreFlag = sessionStorage.getItem(this.#getRestoreDraftFlagKey()) === '1';
        const draftIdFromSession = (sessionStorage.getItem(this.#getLastDraftIdStorageKey()) || '').trim();

        if (!isBackForward && !isReload && !cameFromFinalPreview && !hasRestoreFlag) {
            return;
        }

        const mode = ($(this.#form).data('mode') || '').toString().toLowerCase();
        if (mode === 'update') {
            return;
        }

        try {
            const snapshotRaw = sessionStorage.getItem(this.#getDraftSnapshotKey());
            if (snapshotRaw) {
                try {
                    const snapshot = JSON.parse(snapshotRaw);
                    if (!draftIdFromSession || Number(snapshot?.id || 0) === Number(draftIdFromSession)) {
                        this.#hydrateDraftOnCreate(snapshot);
                        sessionStorage.removeItem(this.#getRestoreDraftFlagKey());
                        sessionStorage.removeItem(this.#getLastDraftIdStorageKey());
                        return;
                    }
                } catch (e) {
                    // ignore invalid snapshot
                }
            }

            const response = await axios.get(`/${this.#endPoint}/draft-last`);
            const draft = response?.data?.data ?? null;

            if (!draft || !draft.id || (draftIdFromSession && Number(draft.id) !== Number(draftIdFromSession))) {
                sessionStorage.removeItem(this.#getRestoreDraftFlagKey());
                sessionStorage.removeItem(this.#getLastDraftIdStorageKey());
                return;
            }

            this.#hydrateDraftOnCreate(draft);

            sessionStorage.removeItem(this.#getRestoreDraftFlagKey());
            sessionStorage.removeItem(this.#getLastDraftIdStorageKey());
        } catch (error) {
            console.log(error);
        }
    }

    #hydrateDraftOnCreate(draft) {
        const normalizeDateForInput = (value) => {
            if (!value) return '';
            const raw = String(value).trim();
            const isoMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (isoMatch) {
                return `${isoMatch[1]}-${isoMatch[2]}-${isoMatch[3]}`;
            }
            const slashMatch = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
            if (slashMatch) {
                return `${slashMatch[3]}-${slashMatch[1]}-${slashMatch[2]}`;
            }
            return '';
        };

        this.#IID = draft.id || null;
        $('#invoice_number').val(draft.invoice_number || '');
        $('#invoice_date').val(normalizeDateForInput(draft.invoice_date));
        $('#tax_code').val(draft.tax_code || 'V11').trigger('change');
        $('#tax_number_supplier').val(draft.tax_number_supplier || '');
        $('#tax_date').val(normalizeDateForInput(draft.tax_date));

        $('#invoice-file-name').val(draft.invoice_file_name || '');
        $('#tax-file-name').val(draft.tax_file_name || '');

        if ((draft.invoice_file_name || '').trim() !== '') {
            this.#requiredFileInvoice = false;
            this.#setVerifiedInvoiceIdentity();
        }
        if ((draft.tax_file_name || '').trim() !== '') {
            this.#requiredFileTax = false;
            this.#setVerifiedTaxIdentity();
        }

        this.#applyBadgeSnapshot('invoice', draft.invoice_badge);
        this.#applyBadgeSnapshot('tax', draft.tax_badge);

        const setCurrency = (selector, value) => {
            const val = Number(value || 0);
            $(selector).val(val ? this.#currencyFormat(val) : '');
        };

        if (draft.pph) {
            $('#pph').val(draft.pph).trigger('change');
            if (draft.pph !== 'none') {
                $('#optional-witholding').removeClass('d-none');
                $('#rekap-jasa-content').removeClass('d-none');
                $('#rekap-jasa-pph').attr('required', true);
                // Ensure object select2 is initialized in create mode before setting object value.
                this.#initObjectSelect2(draft.pph);
            } else {
                $('#optional-witholding').addClass('d-none');
            }
        }

        if (draft.object) {
            const currentOptionExists = $('#object option').filter(function () {
                return $(this).val() === draft.object;
            }).length > 0;

            if (!currentOptionExists) {
                const option = new Option(draft.object, draft.object, true, true);
                option.dataset.dynamic = 'true';
                $('#object').append(option);
            }

            $('#object').val(draft.object).trigger('change');
        }

        const pph = (draft.pph || '').toLowerCase();
        if (pph === 'pph4-2') {
            $('#prefix').html('+');
        } else {
            $('#prefix').html('-');
        }

        this.#hydrateDetails(draft.details || []);

        $('#dpp_pph').val(draft.dpp_pph ? this.#currencyFormat(draft.dpp_pph) : '');
        $('#tarrif').val(draft.tarrif || '');
        $('#nilai').val(draft.nilai ? this.#currencyFormat(draft.nilai) : '');
        this.#processTotalAmount($('#tarrif').val());

        // Fallback to saved summary values when detail-based calculation still yields empty fields.
        if (!($('#net_amount').val() || '').trim()) {
            setCurrency('#net_amount', draft.net_amount);
        }
        if (!($('#dpp_lain').val() || '').trim()) {
            setCurrency('#dpp_lain', draft.dpp_lain);
        }
        if (!($('#ppn').val() || '').trim()) {
            setCurrency('#ppn', draft.ppn);
        }
        setCurrency('#dpp-pph', draft.dpp_pph);
        $('#tarif').val(draft.tarrif || 0);
        setCurrency('#nilai-pph', draft.nilai);
        if (!($('#grand_total').val() || '').trim()) {
            setCurrency('#grand_total', draft.grand_total);
        }

        this.otherFiles.setExistingFiles(draft.other_files || []);

        // Re-apply date at the end to avoid being overwritten by later UI updates.
        $('#invoice_date').val(normalizeDateForInput(draft.invoice_date));
        $('#tax_date').val(normalizeDateForInput(draft.tax_date));

        this.#switchCreateFormToUpdate(draft.id);
    }

    #getBadgeSnapshot(type) {
        const badgeSelector = type === 'invoice' ? '#verified-invoice' : '#verified-tax';
        const $badge = $(badgeSelector);
        const $status = $badge.find('span').first();

        return {
            visible: !$badge.hasClass('d-none'),
            verified: $status.hasClass('bg-label-success')
        };
    }

    #applyBadgeSnapshot(type, snapshot) {
        if (!snapshot || typeof snapshot !== 'object') {
            return;
        }

        const badgeSelector = type === 'invoice' ? '#verified-invoice' : '#verified-tax';
        const $badge = $(badgeSelector);
        const $status = $badge.find('span').first();
        const isVisible = snapshot.visible === true;
        const isVerified = snapshot.verified === true;

        $badge.toggleClass('d-none', !isVisible);
        $status
            .toggleClass('bg-label-success', isVerified)
            .toggleClass('bg-label-danger', !isVerified)
            .text(isVerified ? 'Verified' : 'Invalid');

        if (type === 'invoice') {
            this.validation.validationList.invoice = isVerified;
        } else {
            this.validation.validationList.tax = isVerified;
        }
    }

    #hydrateDetails(details = []) {
        const $tbody = $('#purchase-detail-table tbody');
        const $addRow = $tbody.find('tr.detail-item').last();
        $tbody.find('tr.detail-item').not($addRow).remove();

        if (!Array.isArray(details) || details.length === 0) {
            return;
        }

        const listUnits = (this.#verify_non_po_list_unit || '')
            .split(',')
            .map(item => item.trim())
            .filter(Boolean);

        details.forEach((item) => {
            const qty = Number(item.qty || 0);
            const price = Number(item.price || 0);
            const total = Number(item.total || (qty * price));
            const description = (item.description || '').toString();
            const currentUnit = (item.unit || '-').toString();

            const $row = $('<tr/>', { class: 'detail-item' });
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

            $unit.append(new Option('-', '-', false, currentUnit === '-'));
            listUnits.forEach(option => {
                $unit.append(new Option(option, option, false, option === currentUnit));
            });
            if (currentUnit !== '-' && !listUnits.includes(currentUnit)) {
                $unit.append(new Option(currentUnit, currentUnit, false, true));
            }

            const $price = $('<input/>', {
                type: 'text',
                class: 'form-control price',
                autocomplete: 'new-password'
            }).val(price ? this.#currencyFormat(price) : '');
            const $total = $('<input/>', {
                type: 'text',
                class: 'form-control total',
                readonly: true,
                autocomplete: 'new-password'
            }).val(total ? this.#currencyFormat(total) : '');
            const $removeBtn = $(`
                <button type="button" class="btn btn-danger remove-detail">
                    <i class="ti tabler-trash"></i>
                </button>
            `);

            $row.append($('<td/>').append($description));
            $row.append($('<td/>').append($qty));
            $row.append($('<td/>').append($unit));
            $row.append($('<td/>').append($price));
            $row.append($('<td/>').append($total));
            $row.append($('<td/>', { class: 'text-center' }).append($removeBtn));

            $addRow.before($row);
        });

        $addRow.find('.description').val('');
        $addRow.find('.qty').val('');
        $addRow.find('.unit').val('');
        $addRow.find('.price').val('');
        $addRow.find('.total').val('');
    }

    #switchCreateFormToUpdate(draftId) {
        if (!draftId) {
            return;
        }

        const $form = $(this.#form);
        $form.attr('data-mode', 'update');
        $form.attr('data-endpoint', `/${this.#endPoint}/${draftId}`);

        if ($form.find('input[name="_method"]').length === 0) {
            $form.append('<input type="hidden" name="_method" value="PUT">');
        } else {
            $form.find('input[name="_method"]').val('PUT');
        }
    }
}

new VerifyNonPOForm().init();
