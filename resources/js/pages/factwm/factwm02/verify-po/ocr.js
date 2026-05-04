
import OCRTable from "./ocr-table";
import OtherFiles from "./ocr-other-files";
import axios from "axios";
import { _showInvalidError, toast } from "../../../../helpers";
import Validation from "./validation";
import { showLoadingSwal, closeSwal } from "../../../../swallLoading";

class OCR {
    #verifyPoEndPoint = 'FACTWM/ts/verify-po';
    #getIdbmNpwpMatchEndPoint = 'FACTWM/ts/verify-po/get-idbm-npwp-match';
    #ocrForm = document.getElementById('ocr-form');
    #uploadRequirementModal = new bootstrap.Modal(document.getElementById('other-file-warning-modal'));
    #inputFileElement = null;
    #limitEskalated = window.APP_CONFIG.limit_eskalated;
    #pkpSupplier = window.APP_CONFIG.pkp_supplier;
    #requiredFileRekapJasa = false;
    #requiredFileTax = true;
    #requiredFileInvoice = true;
    #inputPPH = false;
    #draftId = null;
    #verifiedInvoiceIdentity = null;
    #verifiedTaxIdentity = null;
    #verifiedRekapJasaValue = null;
    #isRestoringDraft = false;
    #invoiceRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
    #taxRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
    #rekapRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
    #lastInvoiceFile = null;
    #lastTaxFile = null;
    #lastRekapFile = null;
    #returnFromFinalPreviewKey = 'verify_po_return_from_final_preview';
    #skipLeaveCleanupKey = 'verify_po_skip_leave_cleanup';

    constructor() {
        this.otherFiles = new OtherFiles();
        this.validation = new Validation();
        this.table = new OCRTable(this.validation);
    }

    init() {
        sessionStorage.removeItem(this.#skipLeaveCleanupKey);

        // if (this.#handleBrowserBackNavigation()) {
        //     return;
        // }

        this.table.init();
        this.otherFiles.init();
        this.#select2();
        this.#events();
        this.#getNPWPIDBMMatch();
        this.#registerLeaveCleanup();
        const restored = this.#restoreDraftIfReturning();
        if (!restored) {
            this.#clearValueDate();
            this.#restoreDraftFromServerIfReturning();
        }
    }

    // #handleBrowserBackNavigation() {
    //     const navigationEntry = performance.getEntriesByType('navigation')[0];
    //     const isBackForward = navigationEntry?.type === 'back_forward';
    //     const cameFromFinalPreview = sessionStorage.getItem(this.#returnFromFinalPreviewKey) === '1'
    //         || (document.referrer || '').includes(`/${this.#verifyPoEndPoint}/final-preview/`);

    //     if (!isBackForward || cameFromFinalPreview) {
    //         if (cameFromFinalPreview) {
    //             sessionStorage.removeItem(this.#returnFromFinalPreviewKey);
    //         }
    //         return false;
    //     }

    //     this.#clearDraft();
    //     axios
    //         .post(`/${this.#verifyPoEndPoint}/clear-ocr-state`)
    //         .catch(error => {
    //             console.log(error);
    //         })
    //         .finally(() => {
    //             window.location.href = `/${this.#verifyPoEndPoint}/view`;
    //         });

    //     return true;
    // }

    #events() {
        const self = this;

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

        $(document).on('change', '#tax_invoice', function () {
            self.#resetTaxUploadRequirement();
        });

        $(document).on('change', '#tax_invoice_date', function () {
            self.#resetTaxUploadRequirement();
        });

        $(document).on('click', '#back-button', function () {
            self.#clearDraft();
            axios
                .post(`/${self.#verifyPoEndPoint}/clear-ocr-state`)
                .catch(error => {
                    console.log(error);
                })
                .finally(() => {
                    window.location.href = `/${self.#verifyPoEndPoint}/view`;
                });
        });

        $(document).on('click', '#escalated-button', function () {
            const form = this.closest('form');

            if (!form.checkValidity()) {
                form.reportValidity(); // ini yang munculin pesan required
                return;
            }

            if (!self.#validateBeforeSubmit()) {
                return;
            }
            self.#submitEskalatedPO();
        });

        $(document).on('change', '#invoice_date, #tax_invoice_date', function () {
            self.#validateInvoiceTaxDateMatch(true);
        });

        $('#dpp-pph').on('input', function (e) {
            let rawValue = this.value.replace(/[^0-9]/g, '');

            if (!rawValue) {
                this.value = '';
                return;
            }

            this.value = self.table.currencyFormat(Number(rawValue));
            self.#processTotalAmount($('#tarrif').val())
        });

        // upload invoice
        $('#btn-upload-invoice').on('click', function () {
            // self.#setInvoiceIdentityLock(false);
            self.#uploadRequirementModal.show();
            self.#inputFileElement = $('#invoice_file');
        });

        $('#btn-upload-tax').on('click', function () {
            self.#uploadRequirementModal.show();
            self.#inputFileElement = $('#tax_file');
        });

        $('#btn-upload-pph').on('click', function () {
            self.#uploadRequirementModal.show();
            self.#inputFileElement = $('#rekap_jasa_file');
        });

        $('#other-file-upload-button').on('click', function () {
            self.#uploadRequirementModal.show();
            self.#inputFileElement = $('#other-file');
        });

        $('#btn-accept-requirement').on('click', function () {
            self.#uploadRequirementModal.hide();
            self.#inputFileElement.click();
        });

        $('.close-modal').on('click', function () {
            self.#uploadRequirementModal.hide();
            self.#inputFileElement = null;
        });

        this.#onChange();
        this.#onSubmit();
    }

    #registerLeaveCleanup() {
        const self = this;

        window.addEventListener('pagehide', function () {
            if (sessionStorage.getItem(self.#skipLeaveCleanupKey) === '1') {
                sessionStorage.removeItem(self.#skipLeaveCleanupKey);
                return;
            }

            if (!window.location.pathname.includes(`/${self.#verifyPoEndPoint}/ocr`)) {
                return;
            }

            self.#clearDraft();
            self.#sendLeaveCleanupBeacon();
        });
    }

    #sendLeaveCleanupBeacon() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const url = `/${this.#verifyPoEndPoint}/clear-ocr-state`;

        if (!token || typeof navigator.sendBeacon !== 'function') {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
                keepalive: true,
            }).catch(() => { });
            return;
        }

        const payload = new URLSearchParams();
        payload.append('_token', token);

        navigator.sendBeacon(url, payload);
    }

    #onChange() {
        const self = this;

        $('#invoice_file').on('click', function () {
            this.value = null;
        });

        $('#invoice_file').on('change', function () {
            const invoiceNumber = $('#invoice_number').val().trim();
            const invoiceDate = $('#invoice_date').val().trim();
            const netAmount = $('#net-amount').val().trim();
            const total = $('#total').val().trim();
            const dpp = $('#dpp').val().trim();
            const ppn = $('#ppn').val().trim();
            const npwp_idbm = $('#npwp_idbm').val().trim();
            const value = $('#value').val().trim();

            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();

            // remove class
            $('#net-amount').removeClass('is-invalid');
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

            if (self.#inputPPH && value === '') {
                toast.error('Please input value first');
                $(this).val(null);
                return;
            }

            self.#invoiceRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
            self.#lastInvoiceFile = file;
            self.#validateInvoiceFile(file, $(this));

            self.#requiredFileInvoice = false
        });

        $('#rekap_jasa_file').on('click', function () {
            this.value = null;
        });
        $('#rekap_jasa_file').on('change', function () {
            // const invoiceNumber = $('#invoice_number').val().trim();
            // const invoiceDate = $('#invoice_date').val().trim();
            // const netAmount = $('#net-amount').val().trim();
            // const ppn = $('#ppn').val().trim();
            const value = $('#value').val().trim();

            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();

            // remove class
            // $('#net-amount').removeClass('is-invalid');
            // $('#invoice_number').removeClass('is-invalid');
            // $('#invoice_date').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            if (value === '') {
                toast.error('Please input value first');
                $(this).val(null);
            }

            const file = this.files[0];

            if (!file) return;

            // Set text input value to file name (without extension)
            const fileName = file.name.replace(/\.[^/.]+$/, '');

            if (!self.otherFiles.appendFileValidation(file, fileName)) {
                return;
            }

            $('#pph-file-name').val(fileName);
            self.#rekapRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
            self.#lastRekapFile = file;
            self.#validateRekapJasaFile(file, $(this));

            self.validation.requiredFileRekapJasa = false
        });

        $('#tax_file').on('click', function () {
            this.value = null;
        });
        $('#tax_file').on('change', function () {
            const invoiceNumber = $('#tax_invoice').val().trim();
            const invoiceDate = $('#tax_invoice_date').val().trim();
            const netAmount = $('#net-amount').val().trim();
            const dpp = $('#dpp').val().trim();
            const ppn = $('#ppn').val().trim();
            const npwp = $('#npwp_supplier').val().trim();
            const npwp_idbm = $('#npwp_idbm').val().trim();

            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
            // remove class
            $('#tax_invoice').removeClass('is-invalid');
            $('#tax_invoice_date').removeClass('is-invalid');
            $('#ppn').removeClass('is-invalid');
            $('#npwp_supplier').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            if (invoiceNumber === '' || invoiceDate === '' || npwp === '') {
                toast.error('Please input tax invoice number, npwp & tax invoice date first');
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

            self.#requiredFileTax = false
        });
    }

    #onSubmit() {
        const self = this;

        this.#ocrForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!self.#validateInvoiceTaxDateMatch(true)) {
                return false;
            }

            if (self.#requiredFileInvoice) {
                toast.error("Please Upload Invoice file");
                return false;
            }

            if (self.#requiredFileTax) {
                toast.error("Please Upload Tax file");
                return false;
            }

            let inputPPHFile = $('#pph-file-name').val();

            if (self.validation.requiredFileRekapJasa) {
                const message = (inputPPHFile || '').trim()
                    ? "Please Reupload Rekap Jasa PPH"
                    : "Please Upload Rekap Jasa PPH";
                toast.error(message);
                return false;
            }

            let checkButtonSubmit = self.otherFiles?.checkButtonSubmit();
            if (checkButtonSubmit) {
                toast.error("Please append a other file click Add");
                return;
            }

            if (self.table.grNumber.length < 1) {
                toast.error('No GR number');
                return;
            }

            const unverifyOCR = $('#unverified_ocr').val();
            const isAllValid = Object.values(self.validation.validationList).every(Boolean);
            console.log(isAllValid, "valid");
            if (!isAllValid) {
                toast.error('OCR Invoice or Rekap Jasa PPh or Tax Invoice Not Valid');
                return;
            }

            const formData = new FormData(self.#ocrForm);
            formData.append('gr_number', self.table.grNumber);
            formData.append('status', 'WAITING');
            if (self.#draftId) {
                formData.append('draft_id', self.#draftId);
            }

            //send the text instead of the value of the pph object
            if ($.fn.select2 && $('#object').hasClass('select2-hidden-accessible')) {
                const objectSelect = $('#object').select2('data')?.[0];
                formData.set('object', objectSelect?.text ?? '');
            } else {
                formData.set('object', ''); // fallback
            }


            self.otherFiles.files.forEach((item, index) => {
                formData.append(`otherFiles[${index}][file]`, item.file);
                formData.append(`otherFiles[${index}][name]`, item.name);
            });
            formData.append('deleted_existing_other_file_ids', JSON.stringify(self.otherFiles.deletedExistingFileIds || []));

            showLoadingSwal();
            axios
                .post(`/${self.#verifyPoEndPoint}`, formData)
                .then(response => {
                    const data = response.data.data;
                    self.#saveDraftForBack(data.IID, true);
                    sessionStorage.setItem(self.#skipLeaveCleanupKey, '1');
                    sessionStorage.setItem(self.#returnFromFinalPreviewKey, '1');
                    // toast.success(response.data.message);
                    window.location.href = `/${self.#verifyPoEndPoint}/final-preview/${data.IID}`;
                })
                .catch(error => {
                    if (error.response && error.response.status === 422) {
                        _showInvalidError(error.response.data.errors);
                    } else {
                        console.log(error)
                        toast.error(error.response.data.message);
                    }
                }).finally(() => {
                    closeSwal();
                })
        });
    }

    #validateInvoiceFile(file, $element) {
        const invoiceNumber = $('#invoice_number').val().trim();
        const invoiceDate = $('#invoice_date').val().trim();
        const netAmount = $('#net-amount').val().trim();
        const total = $('#total').val().trim();
        const dpp = $('#dpp').val().trim();
        const ppn = $('#ppn').val().trim();
        const npwpIdbm = $('#npwp_idbm').val().trim();
        const value = $('#value').val().trim();
        const formData = new FormData();
        const params = [
            invoiceNumber,
            invoiceDate,
            netAmount,
            ppn,
            npwpIdbm,
            this.#pkpSupplier,
            dpp,
            total
        ];

        if (this.#inputPPH === true) {
            params.push(value);
        }

        formData.append('invoice_file', file);
        formData.append('invoice_number', invoiceNumber);
        formData.append('render_dpi', this.#invoiceRenderDpi);
        formData.append('params', params);

        this.validation.validateInvoice(
            `/${this.#verifyPoEndPoint}/validate-invoice`,
            formData,
            $element,
            'invoice',
            (_, valid = []) => {
                const isVerified = this.#isVerifiedByPrefix(valid, 'invoice');
                if (isVerified) {
                    this.#setVerifiedInvoiceIdentity();
                }
            }
        );
    }

    #validateTaxFile(file, $element) {
        const taxInvoice = $('#tax_invoice').val().trim();
        const taxInvoiceDate = $('#tax_invoice_date').val().trim();
        const netAmount = $('#net-amount').val().trim();
        const dpp = $('#dpp').val().trim();
        const ppn = $('#ppn').val().trim();
        const npwp = $('#npwp_supplier').val().trim();
        const npwpIdbm = $('#npwp_idbm').val().trim();
        const formData = new FormData();

        formData.append('tax_file', file);
        formData.append('tax_invoice', taxInvoice);
        formData.append('render_dpi', this.#taxRenderDpi);
        formData.append('params', [
            taxInvoice,
            taxInvoiceDate,
            netAmount,
            ppn,
            npwp,
            npwpIdbm,
            this.#pkpSupplier,
            dpp
        ]);

        this.validation.validateInvoice(
            `/${this.#verifyPoEndPoint}/validate-tax`,
            formData,
            $element,
            'tax',
            (_, valid = []) => {
                const isVerified = this.#isVerifiedByPrefix(valid, 'tax');
                if (isVerified) {
                    this.#setVerifiedTaxIdentity();
                }
            }
        );
    }

    #validateRekapJasaFile(file, $element) {
        const value = $('#value').val().trim();
        const formData = new FormData();

        formData.append('rekap_jasa_file', file);
        formData.append('render_dpi', this.#rekapRenderDpi);
        formData.append('params', [value]);

        this.validation.validateInvoice(
            `/${this.#verifyPoEndPoint}/validate-rekap-jasa`,
            formData,
            $element,
            'rekap_jasa',
            (_, valid = []) => {
                const isVerified = this.#isVerifiedByPrefix(valid, 'rekap_jasa');
                if (isVerified) {
                    this.#setVerifiedRekapJasaValue();
                }
            }
        );
    }

    #select2() {
        $('#pph').select2({
            placeholder: 'Select PPh',
            allowClear: true,
            dropdownParent: $('#tax-content'),
            // ajax: {
            //     url: '/general/pph-list', // your API route
            //     dataType: 'json',
            //     delay: 250, // delays requests for better performance
            //     data: function (params) {
            //         return {
            //             search: params.term // search term
            //         };
            //     },
            //     processResults: function (response) {
            //         const data = response.data.data || [];

            //         const results = [];

            //         Object.keys(data).forEach(function (key) {
            //             results.push({
            //                 id: key,
            //                 text: key,
            //                 object: data[key].object
            //             });
            //         });

            //         // 👇 add empty option at the top
            //         results.unshift({
            //             id: 'none',
            //             text: 'Tidak ada PPh',
            //             object: []
            //         });

            //         return {
            //             results: results
            //         };
            //     },
            //     cache: true
            // }
        });

        this.#select2Events();
    }

    #select2Events() {
        const self = this;
        $('#pph').on('select2:select', function (e) {
            const selected = e.params.data;

            $('#object').val(null).trigger('change');
            $('#object option[data-dynamic="true"]').remove();
            $('#tarrif').val('');
            $('#value').val('');
            $('#total').val(self.table.currencyFormat(self.table.totalAmount + self.table.ppn))
            $('#verified-rekap-jasa').addClass('d-none');
            $('#nilai-status').addClass('d-none');
            if (selected.id !== 'none') {
                $('#optional-witholding').removeClass('d-none');
                // trigger input pph
                self.#inputPPH = true;
                const pph = (selected.id || '').toLowerCase();
                if (pph === 'pph4-2') {
                    $('#dpp-pph').val($('#net-amount').val());
                    $('#dpp-pph').attr('readonly', true);
                    $('#dpp-pph').addClass('bg-gray-50');
                    // $('#prefix').removeClass('d-none');
                    $('#prefix').html('-');
                } else if (pph === 'pph22') {
                    // $('#prefix').removeClass('d-none');
                    $('#dpp-pph').val(0);
                    // $('#value').val('');
                    $('#prefix').html('+');
                } else {
                    $('#dpp-pph').val(0);
                    $('#dpp-pph').attr('readonly', false);
                    $('#dpp-pph').removeClass('bg-gray-50');
                    // $('#value').val('');
                    // $('#prefix').removeClass('d-none');
                    $('#prefix').html('-');
                }

                /** DESTROY JIKA SUDAH ADA **/
                if ($('#object').hasClass("select2-hidden-accessible")) {
                    $('#object').select2('destroy');
                }

                /** INIT SELECT2 AJAX **/
                $('#object').select2({
                    placeholder: 'Select object',
                    allowClear: true,
                    // minimumInputLength: 1,
                    ajax: {
                        url: '/general/pph-list',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                search: params.term,
                                pph: selected.id // opsional kalau mau filter by PPH
                            };
                        },
                        processResults: function (response) {
                            const data = response.data?.data || [];

                            return {
                                results: data.map(item => ({
                                    id: item.text,
                                    text: item.text,
                                    tariff: item.itarif,
                                    vpph_pasal: item.vpph_pasal,
                                    vcode_map: item.vcode_map
                                }))
                            };

                        },
                        cache: true
                    }
                });

                // $('#object').on('select2:select', function (e) {
                //     const data = e.params.data;
                //     $("#tarrif").val(data.tarrif);
                // }).on('select2:unselect', function () {
                //     $("#tarrif").val('');
                // });

                $('.required-rekap-jasa-pph').removeClass('d-none');
                self.#requiredFileRekapJasa = true;
                $('#rekap-jasa-content').removeClass('d-none');
                self.validation.validationList.pph = false;
                self.#resetRekapJasaUploadRequirement();

            } else {
                // trigger input pph
                self.#inputPPH = false;
                $('#optional-witholding').addClass('d-none');
                $('#prefix').removeClass('d-none');
                $('.required-rekap-jasa-pph').addClass('d-none');
                self.#requiredFileRekapJasa = false;
                $('#rekap-jasa-content').addClass('d-none');
                self.validation.validationList.pph = true;
                self.validation.requiredFileRekapJasa = false;
                $('#rekap_jasa_file').val(null);
                $('#pph-file-name').val('');
                $('#verified-rekap-jasa').addClass('d-none');
                $('#nilai-status').addClass('d-none');
                self.#verifiedRekapJasaValue = null;
                $(document).trigger('ocr-validation-state-change');
            }
        }).on('select2:unselect', function () {
            $('#object option[data-dynamic="true"]').remove();
            $('#tarrif').val('');
        });

        $('#object')
            .off('select2:select select2:unselect')
            .on('select2:select', function (e) {
                const data = e.params.data;

                $('#tarrif').val(data.tariff).trigger('input');
                // self.#processTotalAmount(data); // ✅
            })
            .on('select2:unselect', function () {
                $('#tarrif').val('');
                $('#value').val('');
                self.#resetRekapJasaUploadRequirement();
            });

        $('#tarrif').on('input', function () {
            self.#processTotalAmount(this.value);
        });
    }

    #processTotalAmount(tar) {
        const pph = ($('#pph').val() || '').trim().toLowerCase();
        const dppPph = parseFloat($('#dpp-pph').val().replace(/\./g, ''));
        const tarrif = parseFloat(tar);
        const value = dppPph * (tarrif / 100);
        const previousValue = ($('#value').val() || '').trim();
        const nextValue = this.table.currencyFormat(value);

        $('#value').val(nextValue);
        if (!this.#isRestoringDraft && previousValue !== nextValue) {
            this.#resetRekapJasaUploadRequirement();
        }

        let total = this.table.totalAmount + this.table.ppn;

        if (pph === 'pph22') {
            total = total + value;
        } else {
            total = total - value;
        }
        $('#total').val(this.table.currencyFormat(total));
    }

    #getNPWPIDBMMatch() {
        let self = this
        showLoadingSwal();
        axios
            .get(`/${self.#getIdbmNpwpMatchEndPoint}`)
            .then(response => {
                const data = response.data.data;
                if (data) {
                    $('#npwp_idbm').val(data.config_npwp_idbm_match);
                    $('#npwp_supplier').val(data.npwp);
                    // check supplier pkp or non pkp
                    if (data.pkp_supplier === false) {
                        self.#requiredFileTax = false;
                        self.#setDisabledInput();
                    }
                }
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    _showInvalidError(error.response.data.errors);
                } else {
                    console.log(error)
                    toast.error(error.response.data.message);
                }
            }).finally(() => {
                closeSwal();
            })
    }

    async #submitEskalatedPO() {
        const self = this;
        try {
            const result = await Swal.fire({
                title: 'Escalated this PO?',
                text: 'Are you sure you want to Escalated this PO?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d'
            });

            if (result.isConfirmed) {
                const formData = new FormData(self.#ocrForm);
                formData.append('gr_number', self.table.grNumber);
                if (self.#draftId) {
                    formData.append('draft_id', self.#draftId);
                }


                //send the text instead of the value of the pph object
                if ($.fn.select2 && $('#object').hasClass('select2-hidden-accessible')) {
                    const objectSelect = $('#object').select2('data')?.[0];
                    formData.set('object', objectSelect?.text ?? '');
                } else {
                    formData.set('object', ''); // fallback
                }


                self.otherFiles.files.forEach((item, index) => {
                    formData.append(`otherFiles[${index}][file]`, item.file);
                    formData.append(`otherFiles[${index}][name]`, item.name);
                });
                formData.append('deleted_existing_other_file_ids', JSON.stringify(self.otherFiles.deletedExistingFileIds || []));
                formData.append('status', 'ESCALATED');
                showLoadingSwal();
                axios
                    .post(`/${self.#verifyPoEndPoint}`, formData)
                    .then(response => {
                        const data = response.data.data;
                        // toast.success(response.data.message);
                        window.location.href = `/${self.#verifyPoEndPoint}/view`;
                    })
                    .catch(error => {
                        if (error.response && error.response.status === 422) {
                            _showInvalidError(error.response.data.errors);
                        } else {
                            console.log(error)
                            toast.error(error.response.data.message);
                        }
                    }).finally(() => {
                        closeSwal();
                        $('#next-button').prop('disabled', true);
                    })
            }
        } catch (error) {
            toast.error('Error');
        }
    }

    #setDisabledInput() {
        $('#tax_invoice,#tax_invoice_date,#btn-upload-tax')
            .prop('required', false)
            .prop('disabled', true);
    }


    #validateBeforeSubmit() {
        const selectedPph = ($('#pph').val() || '').trim().toLowerCase();

        // const isAllValid = Object.values(this.validation.validationList).every(Boolean);
        // if (!isAllValid) {
        //     toast.error('OCR Invoice or Rekap Jasa PPh or Tax Invoice Not Valid');
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

        if (selectedPph !== 'none' && this.validation.requiredFileRekapJasa) {
            const inputPPHFile = $('#pph-file-name').val();
            const message = (inputPPHFile || '').trim()
                ? "Please Reupload Rekap Jasa PPH"
                : "Please Upload Rekap Jasa PPH";
            toast.error(message);
            return false;
        }

        let checkButtonSubmit = this.otherFiles?.checkButtonSubmit();
        if (checkButtonSubmit) {
            toast.error("Please append a other file click Add");
            return false;
        }

        if (!this.table.grNumber || this.table.grNumber.length < 1) {
            toast.error('No GR number');
            return false;
        }

        if (!this.#validateInvoiceTaxDateMatch(true)) {
            return false;
        }

        return true;
    }

    #clearValueDate() {
        $('#invoice_date').val('');
        $('#tax_invoice_date').val('');
    }

    #getDraftStorageKey() {
        return 'verify_po_ocr_form_draft';
    }

    #saveDraftForBack(draftId = null, allowRestore = false) {
        const pphValue = $('#pph').val();
        const objectValue = $('#object').val();
        const objectText = $('#object option:selected').text() || '';
        const isWithoutPph = (pphValue || '').trim().toLowerCase() === 'none';

        const payload = {
            draft_id: draftId || this.#draftId || '',
            invoice_number: $('#invoice_number').val() || '',
            invoice_date: $('#invoice_date').val() || '',
            tax_invoice: $('#tax_invoice').val() || '',
            tax_invoice_date: $('#tax_invoice_date').val() || '',
            invoice_file_name: $('#invoice-file-name').val() || '',
            tax_file_name: $('#tax-file-name').val() || '',
            rekap_jasa_file_name: isWithoutPph ? '' : ($('#pph-file-name').val() || ''),
            pph: pphValue || '',
            object: isWithoutPph ? '' : (objectValue || ''),
            object_text: isWithoutPph ? '' : (objectText || ''),
            dpp_pph: isWithoutPph ? '' : ($('#dpp-pph').val() || ''),
            tarrif: isWithoutPph ? '' : ($('#tarrif').val() || ''),
            value: isWithoutPph ? '' : ($('#value').val() || ''),
            allow_restore: allowRestore === true,
            verification_ui: this.#getVerificationUiSnapshot(),
        };

        sessionStorage.setItem(this.#getDraftStorageKey(), JSON.stringify(payload));
    }

    #restoreDraftIfReturning() {
        const raw = sessionStorage.getItem(this.#getDraftStorageKey());
        if (!raw) {
            return false;
        }

        let payload = null;
        try {
            payload = JSON.parse(raw);
        } catch (e) {
            return false;
        }

        if (payload.allow_restore !== true) {
            return false;
        }

        this.#draftId = payload.draft_id || null;
        this.#isRestoringDraft = true;

        $('#invoice_number').val(payload.invoice_number || '');
        $('#invoice_date').val(payload.invoice_date || '');
        $('#tax_invoice').val(payload.tax_invoice || '');
        $('#tax_invoice_date').val(payload.tax_invoice_date || '');
        $('#invoice-file-name').val(payload.invoice_file_name || '');
        $('#tax-file-name').val(payload.tax_file_name || '');
        $('#pph-file-name').val(payload.rekap_jasa_file_name || '');
        $('#dpp-pph').val(payload.dpp_pph || '');
        $('#tarrif').val(payload.tarrif || '').trigger('input');
        $('#value').val(payload.value || '');

        if ((payload.invoice_file_name || '').trim() !== '') {
            this.#requiredFileInvoice = false;
            this.#setVerifiedInvoiceIdentity();
        }
        if ((payload.tax_file_name || '').trim() !== '') {
            this.#requiredFileTax = false;
            this.#setVerifiedTaxIdentity();
        }
        if ((payload.rekap_jasa_file_name || '').trim() !== '') {
            this.validation.requiredFileRekapJasa = false;
            this.#setVerifiedRekapJasaValue();
        }

        if (payload.pph) {
            $('#pph').val(payload.pph).trigger('change');
            if (payload.pph !== 'none') {
                $('#optional-witholding').removeClass('d-none');
                this.#inputPPH = true;
            } else {
                $('#optional-witholding').addClass('d-none');
                this.#inputPPH = false;
            }
        }

        if (payload.object) {
            const currentOptionExists = $('#object option').filter(function () {
                return $(this).val() === payload.object;
            }).length > 0;

            if (!currentOptionExists) {
                const option = new Option(payload.object_text || payload.object, payload.object, true, true);
                option.dataset.dynamic = 'true';
                $('#object').append(option);
            }

            $('#object').val(payload.object).trigger('change');
        }

        const pph = (payload.pph || '').toLowerCase();
        if (pph === 'pph4-2') {
            $('#prefix').html('+');
        } else {
            $('#prefix').html('-');
        }


        this.#applyVerificationUiSnapshot(payload.verification_ui);
        this.#isRestoringDraft = false;

        return true;
    }

    async #restoreDraftFromServerIfReturning() {
        try {
            const response = await axios.get(`/${this.#verifyPoEndPoint}/draft-last`);
            const draft = response?.data?.data ?? null;

            if (!draft || !draft.id) {
                return;
            }

            this.#draftId = draft.id;
            this.#isRestoringDraft = true;

            $('#invoice_number').val(draft.invoice_number || '');
            $('#invoice_date').val(draft.invoice_date || '');
            $('#tax_invoice').val(draft.tax_invoice || '');
            $('#tax_invoice_date').val(draft.tax_invoice_date || '');
            $('#dpp-pph').val(draft.dpp_pph || '');
            $('#tarrif').val(draft.tarrif || '').trigger('input');
            $('#value').val(draft.value || '');

            $('#invoice-file-name').val(draft.invoice_file_name || '');
            $('#tax-file-name').val(draft.tax_file_name || '');
            $('#pph-file-name').val(draft.rekap_jasa_file_name || '');

            if (draft.invoice_file_name) {
                this.#requiredFileInvoice = false;
                this.#setVerifiedInvoiceIdentity();
            }
            if (draft.tax_file_name) {
                this.#requiredFileTax = false;
                this.#setVerifiedTaxIdentity();
            }
            if (draft.rekap_jasa_file_name) {
                this.validation.requiredFileRekapJasa = false;
                this.#setVerifiedRekapJasaValue();
            }

            if (draft.pph) {
                $('#pph').val(draft.pph).trigger('change');
                if (draft.pph !== 'none') {
                    $('#optional-witholding').removeClass('d-none');
                    this.#inputPPH = true;
                } else {
                    $('#optional-witholding').addClass('d-none');
                    this.#inputPPH = false;
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
            if (draft.pph === 'pph4-2') {
                $('#prefix').html('+');
            } else {
                $('#prefix').html('-');
            }


            this.otherFiles.setExistingFiles(draft.other_files || []);
            this.#saveDraftForBack(draft.id, false);
            this.#isRestoringDraft = false;
        } catch (error) {
            this.#isRestoringDraft = false;
            console.log(error);
        }
    }

    #clearDraft() {
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
        this.#draftId = null;
    }

    #resetInvoiceUploadRequirement() {
        if (this.#isInvoiceIdentitySameAsVerified()) {
            return;
        }

        const hasInvoiceFileName = ($('#invoice-file-name').val() || '').trim() !== '';
        const hasSelectedInvoiceFile = ($('#invoice_file').val() || '').trim() !== '';

        if (!hasInvoiceFileName && !hasSelectedInvoiceFile && this.#requiredFileInvoice) {
            return;
        }

        $('#invoice_file').val(null);
        $('#invoice-file-name').val('');
        $('#verified-invoice').addClass('d-none');
        $('#net-amount-status').addClass('d-none');
        // $('#dpp-nilai-lain-status').addClass('d-none');
        $('#ppn-status').addClass('d-none');
        $('.required-rekap-jasa-pph').addClass('d-none');
        $('.list-error-ocr, .list-warning-ocr').addClass('d-none').empty();
        this.validation.validationList.invoice = false;
        this.validation.validationList.netAmount = false;
        this.validation.validationList.dppNilaiLain = false;
        this.validation.validationList.ppn = false;
        this.validation.validationList.pph = false;
        this.validation.requiredFileRekapJasa = false;
        this.#requiredFileInvoice = true;
        this.#lastInvoiceFile = null;
        this.#invoiceRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
        $(document).trigger('ocr-validation-state-change');
    }

    #resetTaxUploadRequirement() {
        const isTaxUploadDisabled = $('#tax_file').prop('disabled') || $('#btn-upload-tax').prop('disabled');
        if (isTaxUploadDisabled) {
            return;
        }

        if (this.#isTaxIdentitySameAsVerified()) {
            return;
        }

        const hasTaxFileName = ($('#tax-file-name').val() || '').trim() !== '';
        const hasSelectedTaxFile = ($('#tax_file').val() || '').trim() !== '';

        if (!hasTaxFileName && !hasSelectedTaxFile && this.#requiredFileTax) {
            return;
        }

        $('#tax_file').val(null);
        $('#tax-file-name').val('');
        $('#verified-tax').addClass('d-none');
        $('#ppn-status').addClass('d-none');
        $('#dpp-nilai-lain-status').addClass('d-none');
        $('#npwp-idbm-status').addClass('d-none');
        $('.list-error-ocr, .list-warning-ocr').addClass('d-none').empty();
        this.validation.validationList.tax = false;
        this.validation.validationList.ppn = false;
        this.validation.validationList.dppNilaiLain = false;
        this.validation.validationList.npwpIDBM = false;
        this.#requiredFileTax = true;
        this.#lastTaxFile = null;
        this.#taxRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
        $(document).trigger('ocr-validation-state-change');
    }

    #resetRekapJasaUploadRequirement() {
        if (this.#isRestoringDraft) {
            return;
        }

        if (this.#isRekapJasaValueSameAsVerified()) {
            return;
        }

        const hasRekapFileName = ($('#pph-file-name').val() || '').trim() !== '';
        const hasSelectedRekapFile = ($('#rekap_jasa_file').val() || '').trim() !== '';

        if (!hasRekapFileName && !hasSelectedRekapFile && this.validation.requiredFileRekapJasa) {
            return;
        }

        $('#rekap_jasa_file').val(null);
        $('#pph-file-name').val('');
        $('#verified-rekap-jasa').addClass('d-none');
        $('#nilai-status').addClass('d-none');
        $('.list-error-ocr, .list-warning-ocr').addClass('d-none').empty();
        this.validation.validationList.pph = false;
        this.validation.requiredFileRekapJasa = true;
        this.#lastRekapFile = null;
        this.#rekapRenderDpi = Number(window.APP_CONFIG.ocr_render_dpi_start ?? 140);
        $(document).trigger('ocr-validation-state-change');
    }

    #setVerifiedInvoiceIdentity() {
        this.#verifiedInvoiceIdentity = {
            invoice_number: ($('#invoice_number').val() || '').trim(),
            invoice_date: ($('#invoice_date').val() || '').trim(),
        };
    }

    #setVerifiedTaxIdentity() {
        this.#verifiedTaxIdentity = {
            tax_invoice: ($('#tax_invoice').val() || '').trim(),
            tax_invoice_date: ($('#tax_invoice_date').val() || '').trim(),
        };
    }

    #setVerifiedRekapJasaValue() {
        this.#verifiedRekapJasaValue = ($('#value').val() || '').trim();
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

        const currentTaxNumber = ($('#tax_invoice').val() || '').trim();
        const currentTaxDate = ($('#tax_invoice_date').val() || '').trim();

        return currentTaxNumber === this.#verifiedTaxIdentity.tax_invoice
            && currentTaxDate === this.#verifiedTaxIdentity.tax_invoice_date;
    }

    #isRekapJasaValueSameAsVerified() {
        if (this.#verifiedRekapJasaValue === null) {
            return false;
        }

        const currentValue = ($('#value').val() || '').trim();
        return currentValue === this.#verifiedRekapJasaValue;
    }

    #getBadgeSnapshot(type) {
        const map = {
            invoice: '#verified-invoice',
            tax: '#verified-tax',
            rekap_jasa: '#verified-rekap-jasa',
        };
        const $badge = $(map[type] || '');
        if (!$badge.length) {
            return null;
        }

        const $status = $badge.find('span').first();
        return {
            visible: !$badge.hasClass('d-none'),
            verified: $status.hasClass('bg-label-success'),
        };
    }

    #applyBadgeSnapshot(type, snapshot) {
        if (!snapshot || typeof snapshot !== 'object') {
            return;
        }

        const map = {
            invoice: '#verified-invoice',
            tax: '#verified-tax',
            rekap_jasa: '#verified-rekap-jasa',
        };
        const $badge = $(map[type] || '');
        if (!$badge.length) {
            return;
        }

        const $status = $badge.find('span').first();
        const isVisible = snapshot.visible === true;
        const isVerified = snapshot.verified === true;

        $badge.toggleClass('d-none', !isVisible);
        $status
            .toggleClass('bg-label-success', isVerified)
            .toggleClass('bg-label-danger', !isVerified)
            .text(isVerified ? 'Verified' : 'Invalid');
    }

    #getVerificationUiSnapshot() {
        const captureStatus = (selector) => {
            const $el = $(selector);
            if (!$el.length) {
                return null;
            }

            const $icon = $el.find('i').first();
            return {
                visible: !$el.hasClass('d-none'),
                success: $el.hasClass('text-bg-success'),
                danger: $el.hasClass('text-bg-danger'),
                icon_check: $icon.hasClass('tabler-check'),
                icon_x: $icon.hasClass('tabler-x'),
            };
        };

        const $errorContainer = $('.list-error-ocr').first();
        const $warningContainer = $('.list-warning-ocr').first();

        return {
            badge_invoice: this.#getBadgeSnapshot('invoice'),
            badge_tax: this.#getBadgeSnapshot('tax'),
            badge_rekap_jasa: this.#getBadgeSnapshot('rekap_jasa'),
            status_net_amount: captureStatus('#net-amount-status'),
            status_dpp_nilai_lain: captureStatus('#dpp-nilai-lain-status'),
            status_ppn: captureStatus('#ppn-status'),
            status_npwp: captureStatus('#npwp-idbm-status'),
            status_nilai: captureStatus('#nilai-status'),
            validation_list: { ...(this.validation?.validationList || {}) },
            error_list_html: $errorContainer.length ? ($errorContainer.html() || '') : '',
            error_list_visible: $errorContainer.length ? !$errorContainer.hasClass('d-none') : false,
            warning_list_html: $warningContainer.length ? ($warningContainer.html() || '') : '',
            warning_list_visible: $warningContainer.length ? !$warningContainer.hasClass('d-none') : false,
            unverified_ocr: $('#unverified_ocr').val() || '',
            escalated_visible: !$('#escalated-button').hasClass('d-none'),
            next_disabled: $('#next-button').prop('disabled') === true,
        };
    }

    #applyVerificationUiSnapshot(snapshot) {
        if (!snapshot || typeof snapshot !== 'object') {
            return;
        }

        const applyStatus = (selector, state) => {
            if (!state || typeof state !== 'object') {
                return;
            }

            const $el = $(selector);
            if (!$el.length) {
                return;
            }

            const $icon = $el.find('i').first();
            $el.toggleClass('d-none', !state.visible);
            $el.toggleClass('text-bg-success', !!state.success);
            $el.toggleClass('text-bg-danger', !!state.danger);
            $icon.toggleClass('tabler-check', !!state.icon_check);
            $icon.toggleClass('tabler-x', !!state.icon_x);
        };

        this.#applyBadgeSnapshot('invoice', snapshot.badge_invoice);
        this.#applyBadgeSnapshot('tax', snapshot.badge_tax);
        this.#applyBadgeSnapshot('rekap_jasa', snapshot.badge_rekap_jasa);

        applyStatus('#net-amount-status', snapshot.status_net_amount);
        applyStatus('#dpp-nilai-lain-status', snapshot.status_dpp_nilai_lain);
        applyStatus('#ppn-status', snapshot.status_ppn);
        applyStatus('#npwp-idbm-status', snapshot.status_npwp);
        applyStatus('#nilai-status', snapshot.status_nilai);

        if (snapshot.validation_list && typeof snapshot.validation_list === 'object') {
            this.validation.validationList = {
                ...(this.validation.validationList || {}),
                ...snapshot.validation_list,
            };
        }

        const $errorContainer = $('.list-error-ocr').first();
        if ($errorContainer.length) {
            $errorContainer.html(snapshot.error_list_html || '');
            $errorContainer.toggleClass('d-none', !snapshot.error_list_visible);
        }

        const $warningContainer = $('.list-warning-ocr').first();
        if ($warningContainer.length) {
            $warningContainer.html(snapshot.warning_list_html || '');
            $warningContainer.toggleClass('d-none', !snapshot.warning_list_visible);
        }

        if (typeof snapshot.unverified_ocr !== 'undefined') {
            $('#unverified_ocr').val(snapshot.unverified_ocr);
        }
        if (typeof snapshot.escalated_visible === 'boolean') {
            $('#escalated-button').toggleClass('d-none', !snapshot.escalated_visible);
        }
        if (typeof snapshot.next_disabled === 'boolean') {
            $('#next-button').prop('disabled', snapshot.next_disabled);
        }
    }

    #setInvoiceIdentityLock(isLocked) {
        $('#invoice_number,#invoice_date')
            .prop('readonly', isLocked)
            .toggleClass('readonly-locked', isLocked);

        // Keep file input enabled so FormData still carries the selected file.
        $('#btn-upload-invoice').prop('disabled', isLocked);
        $('#invoice_file').prop('disabled', false);
    }

    #setTaxIdentityLock(isLocked) {
        $('#tax_invoice,#tax_invoice_date')
            .prop('readonly', isLocked)
            .toggleClass('readonly-locked', isLocked);

        // Keep file input enabled so FormData still carries the selected file.
        $('#btn-upload-tax').prop('disabled', isLocked);
        $('#tax_file').prop('disabled', false);
    }

    #setRekapIdentityLock(isLocked) {
        $('#pph-file-name')
            .toggleClass('readonly-locked', isLocked);

        // Keep file input enabled so FormData still carries the selected file.
        $('#btn-upload-pph').prop('disabled', isLocked);
        $('#rekap_jasa_file').prop('disabled', false);
    }

    #isVerifiedByPrefix(valid, validationPrefix) {
        let countError = [];
        if (validationPrefix === 'invoice') {
            countError = valid.filter(item => item.key !== 'nilai' && item.error);
        } else {
            countError = valid.filter(item => item.error);
        }

        return countError.length === 0;
    }

    #validateInvoiceTaxDateMatch(showToast = false) {
        const $invoiceDate = $('#invoice_date');
        const $taxInvoiceDate = $('#tax_invoice_date');

        if ($taxInvoiceDate.prop('disabled')) {
            $invoiceDate[0].setCustomValidity('');
            $taxInvoiceDate[0].setCustomValidity('');
            $invoiceDate.removeClass('is-invalid');
            $taxInvoiceDate.removeClass('is-invalid');
            return true;
        }

        const invoiceDate = ($invoiceDate.val() || '').trim();
        const taxInvoiceDate = ($taxInvoiceDate.val() || '').trim();

        if (!invoiceDate || !taxInvoiceDate) {
            $invoiceDate[0].setCustomValidity('');
            $taxInvoiceDate[0].setCustomValidity('');
            $invoiceDate.removeClass('is-invalid');
            $taxInvoiceDate.removeClass('is-invalid');
            return true;
        }

        const isMatch = invoiceDate === taxInvoiceDate;
        const message = 'Invoice Date and Tax Invoice Date must be the same';

        // Keep browser native validation clean; show message via toast.
        $invoiceDate[0].setCustomValidity('');
        $taxInvoiceDate[0].setCustomValidity('');
        $invoiceDate.toggleClass('is-invalid', !isMatch);
        $taxInvoiceDate.toggleClass('is-invalid', !isMatch);

        if (!isMatch && showToast) {
            toast.error(message);
        }

        return isMatch;
    }
}

new OCR().init();
