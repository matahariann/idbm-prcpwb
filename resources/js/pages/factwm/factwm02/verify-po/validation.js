import axios from "axios";
import { _showInvalidError, toast } from "../../../../helpers";
import { showLoadingSwal, closeSwal } from "../../../../swallLoading";

export default class Validation {
    #limitEskalated = window.APP_CONFIG.limit_eskalated;
    #pkpSupplier = window.APP_CONFIG.pkp_supplier;
    requiredFileRekapJasa = true;
    validationList = {
        invoice: false,
        tax: false,
        netAmount: false,
        dppNilaiLain: false,
        ppn: false,
        pph: true,
        npwpIDBM: false,
    }

    validateInvoice(url, formData, element, validationPrefix, onSuccess = null) {
        const self = this;
        // clear error ocr
        $('.list-error-ocr, .list-warning-ocr').addClass('d-none').empty();
        // hide checklist pada ppn dan ammount
        if (validationPrefix == 'invoice') {
            $('#verified-invoice').addClass('d-none');
            $('#verified-rekap-jasa').addClass('d-none');
            $('#net-amount-status').addClass('d-none');
            // $('#dpp-nilai-lain-status').addClass('d-none');
            $('#ppn-status').addClass('d-none');
            $('.required-rekap-jasa-pph').addClass('d-none');
            $('#nilai-status').addClass('d-none');
            // $('#rekap-jasa-content').addClass('d-none');
            self.requiredFileRekapJasa = false;
            self.validationList['pph'] = false;
        } else if (validationPrefix == 'rekap_jasa') {
            $('#verified-rekap-jasa').addClass('d-none');
            $('#nilai-status').addClass('d-none');
            self.requiredFileRekapJasa = false;
            self.validationList['pph'] = false;
        } else {
            $('#verified-tax').addClass('d-none');
            $('#ppn-status').addClass('d-none');
            $('#dpp-nilai-lain-status').addClass('d-none');
            $('#npwp-idbm-status').addClass('d-none');
        }
        showLoadingSwal();
        // default: tidak required
        // $('#rekap-jasa-pph').prop('required', false);
        return axios.post(url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then(response => {
                const valid = response.data.data.valid;
                $('#unverified_ocr').val(response.data.data.unverifyOCR);

                const unverifyOCR = $('#unverified_ocr').val();
                if (unverifyOCR >= self.#limitEskalated) {
                    $("#escalated-button").removeClass('d-none');
                    // $('#next-button').prop('disabled', true);
                    // self.#submitEskalatedPO();
                }

                // generate error list ocr
                this.#generateErrorOCR(valid, validationPrefix);

                if (valid.length > 0) {
                    valid.forEach(item => {
                        if (item.key === 'ppn' || item.key === 'net-amount' || item.key === 'nilai' || item.key === 'npwp_idbm' || item.key === 'dpp-nilai-lain') {
                            this.#toggleStatusIcon(item);
                        }

                        // validasi pph jika gagal di ocr invoice maka upload rekap jasa pph jadi true
                        if (validationPrefix == 'invoice' && item.key === 'nilai' && !item.checked) {
                            $('.required-rekap-jasa-pph').removeClass('d-none');
                            self.requiredFileRekapJasa = true;
                            // $('#rekap-jasa-content').removeClass('d-none');
                            self.validationList['pph'] = false;
                        }


                        // required rekap jasa pph
                        // if ((item.key === 'ppn' && validationPrefix == 'invoice') && item.checked === false) {
                        //     $('#rekap-jasa-pph').prop('required', true);
                        // } else {
                        //     $('#rekap-jasa-pph').prop('required', false);
                        // }
                    });
                }

                // check pkp supplier
                if (self.#pkpSupplier === false) {
                    self.validationList['ppn'] = true;
                    self.validationList['pph'] = true;
                    self.validationList['npwpIDBM'] = true;
                    self.validationList['tax'] = true;
                }
                // if (validationPrefix == 'invoice') {
                //     self.validationList['invoice'] = response.data.data.valid;
                //     self.validationList['netAmount'] = response.data.data.valid;
                //     self.validationList['ppn'] = response.data.data.valid;
                // } else {
                //     self.validationList['tax'] = response.data.data.valid;
                //     self.validationList['npwpIDBM'] = response.data.data.valid;
                // }
                // self.updateFileValidationStatus(validationPrefix);

                if (typeof onSuccess === 'function') {
                    onSuccess(response, valid, validationPrefix);
                }

                this.#notifyValidationStateChanged();
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    element.val(null);
                    _showInvalidError(error.response.data.errors);
                } else {
                    console.log(error)
                    element.val(null);
                    toast.error(error.response.data.message);
                }
                throw error;
            })
            .finally(() => {
                closeSwal();
            })
    }

    updateFileValidationStatus(validationPrefix) {
        if (validationPrefix == 'invoice') {
            //Invoice
            const netAmount = $('#net-amount-status');
            const dppNilaiLain = $('#dpp-nilai-lain-status');
            const netAmountIcon = netAmount.find('i');
            const verifiedInvoice = $('#verified-invoice');
            const verifiedInvoiceStatus = verifiedInvoice.find('span')
            const ppn = $('#ppn-status');
            const ppnIcon = ppn.find('i');

            $('#rekap-jasa-pph').attr('required', !this.validationList.invoice);

            netAmount
                .toggleClass('text-bg-danger', !this.validationList.invoice)
                .toggleClass('text-bg-success', this.validationList.invoice)
                .removeClass('d-none');

            netAmountIcon
                .toggleClass('tabler-x', !this.validationList.netAmount)
                .toggleClass('tabler-check', this.validationList.netAmount)

            verifiedInvoice
                .toggleClass('d-none', false)

            verifiedInvoiceStatus
                .toggleClass('bg-label-success', this.validationList.invoice)
                .toggleClass('bg-label-danger', !this.validationList.invoice)

            ppn
                .toggleClass('text-bg-danger', !this.validationList.ppn)
                .toggleClass('text-bg-success', this.validationList.ppn).removeClass('d-none');

            dppNilaiLain
                .toggleClass('text-bg-danger', !this.validationList.dppNilaiLain)
                .toggleClass('text-bg-success', this.validationList.dppNilaiLain).removeClass('d-none');

            verifiedInvoiceStatus.text(this.validationList.invoice ? 'Verified' : 'Invalid');
        } else if (validationPrefix == 'rekap_jasa') {
            const verifiedInvoice = $('#verified-invoice');
            const verifiedInvoiceStatus = verifiedInvoice.find('span')
            const ppn = $('#ppn-status');
            const dppNilaiLain = $('#dpp-nilai-lain-status');
            const ppnIcon = ppn.find('i');

            verifiedInvoice
                .toggleClass('d-none', false)

            verifiedInvoiceStatus
                .toggleClass('bg-label-success', this.validationList.invoice)
                .toggleClass('bg-label-danger', !this.validationList.invoice)

            ppn
                .toggleClass('text-bg-danger', !this.validationList.ppn)
                .toggleClass('text-bg-success', this.validationList.ppn).removeClass('d-none');

            verifiedInvoiceStatus.text(this.validationList.invoice ? 'Verified' : 'Invalid');
        } else {
            //Tax
            // const ppn = $('#ppn-status');
            // const ppnIcon = ppn.find('i');
            // const npwpIDBM = $('#npwp-idbm-status');
            // const npwpIDBMIcon = npwpIDBM.find('i');
            const verifiedTax = $('#verified-tax');
            const verifiedTaxStatus = verifiedTax.find('span')
            const ppn = $('#ppn-status');
            const dppNilaiLain = $('#dpp-nilai-lain-status');

            ppn
                .toggleClass('text-bg-danger', !this.validationList.ppn)
                .toggleClass('text-bg-success', this.validationList.ppn).removeClass('d-none');

            dppNilaiLain
                .toggleClass('text-bg-danger', !this.validationList.dpp)
                .toggleClass('text-bg-success', this.validationList.dpp).removeClass('d-none');

            // ppnIcon
            //     .toggleClass('tabler-x', !this.validationList.ppn)
            //     .toggleClass('tabler-check', this.validationList.ppn)

            // npwpIDBM
            //     .toggleClass('text-bg-danger', !this.validationList.npwpIDBM)
            //     .toggleClass('text-bg-success', this.validationList.npwpIDBM).removeClass('d-none');

            // npwpIDBMIcon
            //     .toggleClass('tabler-x', !this.validationList.npwpIDBM)
            //     .toggleClass('tabler-check', this.validationList.npwpIDBM)

            verifiedTax
                .toggleClass('d-none', false)

            verifiedTaxStatus
                .toggleClass('bg-label-success', this.validationList.tax)
                .toggleClass('bg-label-danger', !this.validationList.tax)

            verifiedTaxStatus.text(this.validationList.tax ? 'Verified' : 'Invalid');
        }

    }

    resetValidation() {
        this.validationList = {
            invoice: false,
            tax: false,
            netAmount: false,
            dppNilaiLain: false,
            ppn: false,
            pph: false,
            npwpIDBM: false,
        };

        $('#verified-invoice').addClass('d-none');
        $('#verified-tax').addClass('d-none');
        $('#verified-rekap-jasa').addClass('d-none');
        $('#net-amount-status').addClass('d-none').removeClass('text-bg-danger text-bg-success');
        $('#dpp-nilai-lain-status').addClass('d-none').removeClass('text-bg-danger text-bg-success');
        $('#ppn-status').addClass('d-none').removeClass('text-bg-danger text-bg-success');
        $('#npwp-idbm-status').addClass('d-none').removeClass('text-bg-danger text-bg-success');
        $('#nilai-status').addClass('d-none').removeClass('text-bg-danger text-bg-success');
        $('#net-amount-status i, #dpp-nilai-lain-status i, #ppn-status i, #npwp-idbm-status i, #nilai-status i')
            .removeClass('tabler-x tabler-check');
        $('.list-error-ocr, .list-warning-ocr').addClass('d-none').empty();
        $('.invalid-feedback').remove();
        $('#invoice_file, #tax_file, #rekap_jasa_file, #invoice_number, #invoice_date, #tax_invoice, #tax_invoice_date')
            .removeClass('is-invalid');
        this.#notifyValidationStateChanged();
    }

    #notifyValidationStateChanged() {
        $(document).trigger('ocr-validation-state-change');
    }

    #toggleStatusIcon(item) {
        if (!item.id) return;

        const $itemId = $('#' + item.id);
        const $icon = $('#' + item.id).find('i');

        $itemId.toggleClass('text-bg-danger', !item.checked)
            .toggleClass('text-bg-success', item.checked)
            .removeClass('d-none');

        $icon
            .toggleClass('tabler-x', !item.checked)
            .toggleClass('tabler-check', item.checked);
    }

    #generateErrorOCR(data, validationPrefix) {
        // generate error
        const $errorContainer = $('.list-error-ocr');
        const $warningContainer = $('.list-warning-ocr');
        $errorContainer.empty();
        $warningContainer.empty();
        const self = this

        const materaiWarning = validationPrefix == 'invoice'
            ? data.find(item => item.key === 'materai' && item.error)
            : null;

        const pphWarning = validationPrefix == 'invoice'
            ? data.find(item => item.key === 'nilai' && item.error)
            : null;

        let errors;
        if (validationPrefix == 'invoice') {
            errors = data.filter(item => item.key !== 'materai' && item.key !== 'nilai' && item.error);
        } else {
            errors = data.filter(item => item.error);
        }

        if (errors.length) {
            const $ul = $('<ul></ul>');

            errors.forEach(item => {
                $ul.append(`<li>${item.error}</li>`);
            });

            $errorContainer.append($ul).removeClass('d-none');
        } else {
            $errorContainer.addClass('d-none');
        }

        const warningMessages = [];
        if (materaiWarning) {
            warningMessages.push('Warning: OCR cannot read the stamp duty.');
        }
        if (pphWarning) {
            warningMessages.push('Warning: OCR nilai PPh not found. Please upload Rekap Jasa PPh document.');
        }

        if (warningMessages.length) {
            const $ulWarning = $('<ul></ul>');
            warningMessages.forEach(msg => $ulWarning.append(`<li>${msg}</li>`));
            $warningContainer.append($ulWarning).removeClass('d-none');
        } else {
            $warningContainer.addClass('d-none');
        }

        let countError;
        if (validationPrefix == 'invoice') {
            countError = data.filter(item => item.key !== 'materai' && item.key !== 'nilai' && item.error);
        } else {
            countError = data.filter(item => item.error);
        }

        let checked = countError.length == 0 ? true : false;

        if (validationPrefix == 'invoice') {
            const verifiedInvoice = $('#verified-invoice');
            const verifiedInvoiceStatus = verifiedInvoice.find('span')
            verifiedInvoice
                .toggleClass('d-none', false)

            verifiedInvoiceStatus
                .toggleClass('bg-label-success', checked)
                .toggleClass('bg-label-danger', !checked)
            verifiedInvoiceStatus.text(errors.length == 0 ? 'Verified' : 'Invalid');
            self.validationList['invoice'] = checked;
            self.validationList['netAmount'] = checked;
            self.validationList['ppn'] = checked;
            self.validationList['dppNilaiLain'] = checked;
            self.validationList['pph'] = checked;
            // } else {
        } else if (validationPrefix == 'rekap_jasa') {
            const verifiedRekapJasa = $('#verified-rekap-jasa');
            const verifiedRekapJasaStatus = verifiedRekapJasa.find('span')
            verifiedRekapJasa
                .toggleClass('d-none', false)

            verifiedRekapJasaStatus
                .toggleClass('bg-label-success', checked)
                .toggleClass('bg-label-danger', !checked)
            verifiedRekapJasaStatus.text(errors.length == 0 ? 'Verified' : 'Invalid');
            self.validationList['pph'] = checked;
            // } else {
        } else {
            const verifiedTax = $('#verified-tax');
            const verifiedTaxStatus = verifiedTax.find('span')
            verifiedTax
                .toggleClass('d-none', false)

            verifiedTaxStatus
                .toggleClass('bg-label-success', checked)
                .toggleClass('bg-label-danger', !checked)
            verifiedTaxStatus.text(errors.length == 0 ? 'Verified' : 'Invalid');
            self.validationList['tax'] = checked;
            self.validationList['npwpIDBM'] = checked;
            self.validationList['ppn'] = checked;
            self.validationList['dppNilaiLain'] = checked;
        }
    }
}
