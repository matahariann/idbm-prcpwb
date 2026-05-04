import axios from "axios";
import Swal from "sweetalert2";
import flatpickr from "flatpickr";
import { Indonesian } from "flatpickr/dist/l10n/id.js";
import "flatpickr/dist/flatpickr.min.css";

class scanVerifyPoFormScan {
    #scanVerifyFromEndpoint = "FACTWM/ts/scan-verify-non-po";
    #$billingStatementInput;
    #$uniqueCodeInput;
    #$supplierNameInput;
    #$noInvoiceInput;
    #$documentForm;
    #$historyTable;
    #$dateRangeInput;
    #baseTableUrl = null;
    #currentStep = 'billing'; // billing or unique
    #billingData = null;
    #uniqueData = null;
    #dataTable = null;
    #flatpickrInstance = null;

    constructor() {
        this.#$billingStatementInput = $('#billingStatement');
        this.#$uniqueCodeInput = $('#uniqueCode');
        this.#$supplierNameInput = $('#supplierName');
        this.#$noInvoiceInput = $('#noInvoice');
        this.#$documentForm = $('#documentForm');
        this.#$historyTable = $('#factwmf009-table');
        this.#$dateRangeInput = $('#dateRangeFilter');
    }

    init() {
        this.#$supplierNameInput.prop('disabled', false);
        this.#$supplierNameInput.css('backgroundColor', '#f0f0f0');
        this.#$noInvoiceInput.prop('disabled', false);
        this.#$noInvoiceInput.css('backgroundColor', '#f0f0f0');

        this.#setupAxiosDefaults();
        this.#setupScannerListener();
        this.#setupFormEvents();
        this.#disableUniqueCodeInput();
        this.#initializeFlatpickr();

        $(document).on('click', '#date-clear', e => {
            this.#$dateRangeInput.val("");
            if (this.#flatpickrInstance) {
                this.#flatpickrInstance.clear();
            }
            var table = this.#$historyTable.DataTable();
            table.ajax.url(this.#baseTableUrl).load();
        });
    }

    #setupAxiosDefaults() {
        // Set CSRF token untuk semua request axios
        const token = $('meta[name="csrf-token"]').attr('content');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }
    }

    #setupScannerListener() {
        // Listener untuk scanner pada billing statement
        this.#$billingStatementInput.on('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.#processBillingScan();
            }
        });

        // Listener untuk scanner pada unique code
        this.#$uniqueCodeInput.on('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.#processUniqueCodeScan();
            }
        });
    }

    #setupFormEvents() {
        this.#$documentForm.on('submit', (e) => {
            e.preventDefault();
            this.#submitForm();
        });

        this.#$documentForm.on('reset', (e) => {
            setTimeout(() => {
                this.#resetForm();
            }, 10);
        });
    }

    #disableUniqueCodeInput() {
        this.#$uniqueCodeInput.prop('disabled', true);
        this.#$uniqueCodeInput.css('backgroundColor', '#f0f0f0');
    }

    #enableUniqueCodeInput() {
        this.#$uniqueCodeInput.prop('disabled', false);
        this.#$uniqueCodeInput.css('backgroundColor', '');
        this.#$uniqueCodeInput.focus();
    }

    #initializeFlatpickr() {
        // Initialize Flatpickr for date range
        if (this.#$dateRangeInput.length) {
            const table = this.#$historyTable.DataTable();
            this.#baseTableUrl = table.ajax.url();

            this.#flatpickrInstance = flatpickr(this.#$dateRangeInput[0], {
                mode: "range",
                dateFormat: "Y-m-d",
                static: true,
                appendTo: document.body,
                allowEscapeKey: true,
                onChange: (selectedDates, dateStr, instance) => {
                    const datea = dateStr.split(" to ");

                    if (selectedDates.length === 2) {
                        this.#filterTableByDateRange(datea[0], datea[1]);
                    } else {
                        this.#filterTableByDateRange('', '');
                    }
                },
                onClose: (selectedDates, dateStr, instance) => {
                    // If user cleared the date, show all data
                    if (selectedDates.length === 0) {
                        // this.#loadHistoryTable();
                    }
                }
            });
        }
    }

    #filterTableByDateRange(startDate, endDate) {
        var table = $('#factwmf009-table').DataTable();
        var url = table.ajax.url();

        var separator = url.indexOf('?') > -1 ? '&' : '?';
        var newUrl = url + separator + 'start_date=' + startDate + '&end_date=' + endDate;

        table.ajax.url(newUrl).load();
    }

    async #processBillingScan() {
        const billingValue = this.#$billingStatementInput.val().trim();

        if (!billingValue) {
            this.#showAlert('error', 'Billing Statement tidak boleh kosong!');
            return;
        }

        // this.#showLoading('Memverifikasi Billing Statement...');

        // try {
        //     const response = await axios.post(`/${this.#scanVerifyFromEndpoint}/check-billing`, {
        //         billing_statement: billingValue
        //     });

        //     this.#hideLoading();

        //     if (response.data.success) {
        //         this.#billingData = response.data.data;
        //         this.#showAlert('success', 'Billing Statement ditemukan! Silakan scan Unique Code.');
        //         this.#$billingStatementInput.prop('disabled', true);
        this.#enableUniqueCodeInput();
        this.#currentStep = 'unique';
        //     } else {
        //         this.#showAlert('error', response.data.message || 'Billing Statement tidak ditemukan!');
        //         this.#$billingStatementInput.val('');
        //         this.#$billingStatementInput.focus();
        //     }
        // } catch (error) {
        //     this.#hideLoading();
        //     console.error('Error checking billing:', error);
        //     this.#showAlert('error', error.response?.data?.message || 'Terjadi kesalahan saat memverifikasi Billing Statement!');
        //     this.#$billingStatementInput.val('');
        //     this.#$billingStatementInput.focus();
        // }
    }

    async #processUniqueCodeScan() {
        const uniqueValue = this.#$uniqueCodeInput.val().trim();

        if (!uniqueValue) {
            this.#showAlert('error', 'Unique Code tidak boleh kosong!');
            return;
        }

        this.#showLoading('Memverifikasi Billing Statement dan Unique Code...');

        try {
            const response = await axios.post(`/${this.#scanVerifyFromEndpoint}/check-unique-code`, {
                billing_statement: this.#$billingStatementInput.val(),
                unique_code: uniqueValue
            });

            this.#hideLoading();

            if (response.data.success) {
                this.#uniqueData = response.data.data;
                this.#showAlert('success', 'Billing Statement dan Unique Code ditemukan!');
                // this.#$uniqueCodeInput.prop('disabled', true);

                // Populate form fields
                this.#$supplierNameInput.val(response.data.data.supplier_name || '');
                this.#$noInvoiceInput.val(response.data.data.no_invoice || '');

                // Load and display table
                // await this.#loadHistoryTable();
            } else {
                this.#showAlert('error', response.data.message || 'Billing Statement dan Unique Code tidak ditemukan!');
                this.#$uniqueCodeInput.val('');
                this.#$uniqueCodeInput.focus();
            }
        } catch (error) {
            this.#hideLoading();
            console.error('Error checking unique code:', error);
            this.#showAlert('error', error.response?.data?.message || 'Terjadi kesalahan saat memverifikasi Billing Statement dan Unique Code!');
            this.#$uniqueCodeInput.val('');
            this.#$uniqueCodeInput.focus();
        }
    }

    // async #loadHistoryTable() {
    //     this.#showLoading('Memuat data history...');

    //     try {
    //         const response = await axios.get(`/${this.#scanVerifyFromEndpoint}/history`, {
    //             params: {
    //                 billing_statement: this.#$billingStatementInput.val(),
    //                 unique_code: this.#$uniqueCodeInput.val()
    //             }
    //         });

    //         this.#hideLoading();

    //         if (response.data.success) {
    //             // Clear and reload DataTable with new data
    //             this.#dataTable.clear();
    //             this.#dataTable.rows.add(response.data.data);
    //             this.#dataTable.draw();

    //             // Reset date range filter
    //             if (this.#flatpickrInstance) {
    //                 this.#flatpickrInstance.clear();
    //             }
    //         }
    //     } catch (error) {
    //         this.#hideLoading();
    //         console.error('Error loading history:', error);
    //         this.#showAlert('error', 'Terjadi kesalahan saat memuat data history!');
    //     }
    // }

    async #submitForm() {
        // if (!this.#billingData || !this.#uniqueData) {
        //     this.#showAlert('error', 'Silakan scan Billing Statement dan Unique Code terlebih dahulu!');
        //     return;
        // }

        this.#showLoading('Menyimpan data...');

        try {
            const response = await axios.post(`/${this.#scanVerifyFromEndpoint}/submit`, {
                billing_statement: this.#$billingStatementInput.val(),
                unique_code: this.#$uniqueCodeInput.val(),
                supplier_name: this.#$supplierNameInput.val(),
                no_invoice: this.#$noInvoiceInput.val()
            });

            this.#hideLoading();

            if (response.data.success) {
                this.#showAlert('success', 'Data berhasil disimpan!');
                setTimeout(() => {
                    this.#resetForm();
                    // this.#loadHistoryTable();
                    this.#$historyTable.DataTable().ajax.reload();
                }, 1500);
            } else {
                this.#showAlert('error', response.data.message || 'Gagal menyimpan data!');
            }
        } catch (error) {
            this.#hideLoading();
            console.error('Error submitting form:', error);
            this.#showAlert('error', error.response?.data?.message || 'Terjadi kesalahan saat menyimpan data!');
        }
    }

    #resetForm() {
        this.#$billingStatementInput.val('');
        this.#$uniqueCodeInput.val('');
        this.#$supplierNameInput.val('');
        this.#$noInvoiceInput.val('');

        this.#$billingStatementInput.prop('disabled', false);
        this.#disableUniqueCodeInput();

        this.#billingData = null;
        this.#uniqueData = null;
        this.#currentStep = 'billing';

        this.#$billingStatementInput.focus();
    }

    #showLoading(message = 'Loading...') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    #hideLoading() {
        Swal.close();
    }

    #showAlert(type, message) {
        // type: 'success', 'error', 'warning', 'info'
        const iconType = type === 'error' ? 'error' : type;
        const title = type === 'success' ? 'Berhasil!' : type === 'error' ? 'Error!' : 'Perhatian!';

        Swal.fire({
            icon: iconType,
            title: title,
            text: message,
            timer: 3000,
            showConfirmButton: false,
            timerProgressBar: true
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new scanVerifyPoFormScan().init();
});
