import axios from "axios";
import { _showInvalidError, toast } from "../../../../helpers";
import { showLoadingSwal, closeSwal } from "../../../../swallLoading";

export default class Validation {
    #limitEskalated = window.APP_CONFIG.limit_eskalated;

    validationList = {
        invoice: false,
        tax: false,
        // netAmount: false,
        // ppn: false,
        // npwpIDBM: false,
    }

    validateInvoice(url, formData, element, validationPrefix, onSuccess = null) {
        const self = this;
        // clear error ocr
        $('.list-error-ocr').addClass('d-none').empty();
        // hide checklist pada ppn dan ammount
        if (validationPrefix == 'invoice') {
            $('#verified-invoice').addClass('d-none');
        } else {
            $('#verified-tax').addClass('d-none');
        }
        // default: tidak required
        $('#rekap-jasa-pph').prop('required', false);
        showLoadingSwal();
        return axios.post(url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then(response => {
                const valid = response.data.data.valid;

                $('#unverified_ocr').val(response.data.data.unverifyOCR);

                // generate error list ocr
                this.#generateErrorOCR(valid, validationPrefix);

                const unverifyOCR = $('#unverified_ocr').val();
                if (unverifyOCR >= self.#limitEskalated) {
                    $("#escalated-button").removeClass('d-none');
                    // $('#next-button').prop('disabled', true);
                    // self.#submitEskalatedPO();
                }

                // if (valid.length > 0) {
                //     valid.forEach(item => {
                //         if (item.key === 'ppn' || item.key === 'net-amount') {
                //             this.#toggleStatusIcon(item);
                //         }

                //         // required rekap jasa pph
                //         if ((item.key === 'ppn' && validationPrefix == 'invoice') && item.checked === false) {
                //             $('#rekap-jasa-pph').prop('required', true);
                //         } else {
                //             $('#rekap-jasa-pph').prop('required', false);
                //         }
                //     });
                // }

                if (typeof onSuccess === 'function') {
                    onSuccess(response, valid, validationPrefix);
                }
            })
            .catch(error => {
                // console.log(error, "err")
                if (error.response && error.response.status === 422) {
                    toast.error(error.response.data.message);
                    element.val(null);
                    _showInvalidError(error.response.data.errors);
                } else {
                    // console.log(error)
                    element.val(null);
                    toast.error(error.response.data.message);
                }
                throw error;
            }).finally(() => {
                closeSwal();
            });
    }

    updateFileValidationStatus(validationPrefix) {
        if (validationPrefix == 'invoice') {
            //Invoice
            const netAmount = $('#net-amount-status');
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

            verifiedInvoiceStatus.text(this.validationList.invoice ? 'Verified' : 'Invalid');
        } else if (validationPrefix == 'rekap_jasa') {
            const verifiedInvoice = $('#verified-invoice');
            const verifiedInvoiceStatus = verifiedInvoice.find('span')
            const ppn = $('#ppn-status');
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
            const npwpIDBM = $('#npwp-idbm-status');
            const npwpIDBMIcon = npwpIDBM.find('i');
            const verifiedTax = $('#verified-tax');
            const verifiedTaxStatus = verifiedTax.find('span')

            // ppn
            //     .toggleClass('text-bg-danger', !this.validationList.ppn)
            //     .toggleClass('text-bg-success', this.validationList.ppn).removeClass('d-none');

            ppnIcon
                .toggleClass('tabler-x', !this.validationList.ppn)
                .toggleClass('tabler-check', this.validationList.ppn)


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
            // npwpIDBM: false,
        };

        this.updateFileValidationStatus();
    }

    setTaxValidationState(isValid, showBadge = true) {
        this.validationList.tax = isValid;

        const verifiedTax = $('#verified-tax');
        const verifiedTaxStatus = verifiedTax.find('span');

        verifiedTax.toggleClass('d-none', !showBadge);

        if (showBadge) {
            verifiedTaxStatus
                .toggleClass('bg-label-success', isValid)
                .toggleClass('bg-label-danger', !isValid)
                .text(isValid ? 'Verified' : 'Invalid');
        }
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
        $errorContainer.empty();
        const self = this

        const errors = data.filter(item => item.error);

        if (errors.length) {
            const $ul = $('<ul></ul>');

            errors.forEach(item => {
                $ul.append(`<li>${item.error}</li>`);
            });

            $errorContainer.append($ul).removeClass('d-none');
        } else {
            $errorContainer.addClass('d-none');
        }

        let checked = errors.length == 0 ? true : false;

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
            // self.validationList['netAmount'] = checked;
            self.validationList['ppn'] = checked;
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
            // self.validationList['tax'] = checked;
            // self.validationList['npwpIDBM'] = checked;
        }
    }
}
