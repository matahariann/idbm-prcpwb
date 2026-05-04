import axios from 'axios';
import { _formToJson, _showInvalidError, toast } from '../../../../helpers.js';
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import Swal from 'sweetalert2';

class ReportOverview {
    constructor() {
        this.table = null;
        this.initSelect2();
        this.initDataTable();
        this.initFlatpickr();
        this.initFilterDataTable();
        this.initActionHandlers();
    }

    initFlatpickr() {
        // Initialize all date range pickers
        document.querySelectorAll('#filter_date').forEach((element) => {
            const instance = flatpickr(element, {
                mode: "range",
                dateFormat: "Y-m-d",
                allowInput: true,
                onClose: (selectedDates, dateStr) => {
                    if ($.fn.DataTable.isDataTable('#factwmf012-table')) {
                        const dt = $('#factwmf012-table').DataTable();
                        if (dt.settings()[0].ajax) {
                            dt.ajax.reload();
                        }
                    }
                }
            });
        });

        document.querySelectorAll('#filter_delivery_date').forEach((element) => {
            const instance = flatpickr(element, {
                mode: "range",
                dateFormat: "Y-m-d",
                allowInput: true,
                onClose: (selectedDates, dateStr) => {
                    if ($.fn.DataTable.isDataTable('#factwmf012-table')) {
                        const dt = $('#factwmf012-table').DataTable();
                        if (dt.settings()[0].ajax) {
                            dt.ajax.reload();
                        }
                    }
                }
            });
        });
    }

    initSelect2() {
        // $('#status').select2({
        //     placeholder: 'Select Status',
        //     allowClear: true,
        //     // dropdownParent: $('#upload-modal'),
        // });

        // $('#type').select2({
        //     placeholder: 'Select Type',
        //     allowClear: true,
        //     // dropdownParent: $('#upload-modal'),
        // });
    }

    initDataTable() {
        const tableElement = document.getElementById('factwmf012-table');
        if (!tableElement) {
            console.error('DataTable element not found');
            return;
        }

        // DataTable will be initialized by Laravel DataTables package
        // We just need to get the reference
        this.table = $(tableElement).DataTable();
    }

    initFilterDataTable() {
        // Show Entries
        const showEntries = document.getElementById('entries');
        if (showEntries) {
            showEntries.addEventListener('change', (e) => {
                const length = parseInt(e.target.value);
                this.table.page.len(length).draw();
            });
        }

        // Status Filter
        const selectType = document.getElementById('type');
        if (selectType) {
            selectType.addEventListener('change', (e) => {
                const type = e.target.value;
                if (type) {
                    this.table.column(4).search("^" + type + "$", true, false).draw(); // Column 4 is transaction type
                } else {
                    this.table.column(4).search(type).draw(); // Column 4 is transaction type
                }
            });
        }

        // Currency Filter
        const selectStatus = document.getElementById('status');
        if (selectStatus) {
            selectStatus.addEventListener('change', (e) => {
                const status = e.target.value;
                this.table.column(2).search(status).draw(); // Column 2 is status invoice
            });
        }
    }

    initActionHandlers() {
        // Edit GRN Button
        $(document).on('click', '.reject-invoice', (e) => {
            const id = $(e.currentTarget).data('id');
            this.rejectInvoice(id);
        });

        // Approve GRN Button
        $(document).on('click', '.approve-invoice', (e) => {
            const id = $(e.currentTarget).data('id');
            this.approveInvoice(id);
        });

        $(document).on('click', '#btn-export', event => {
            event.preventDefault();
            window.location.href = `overview/export`;
        });
    }

    /**
     * Approve Invoice
     * @param {number} id - Invoice ID
     */
    async approveInvoice(id) {
        try {
            const result = await Swal.fire({
                title: 'Approve Invoice?',
                text: 'Are you sure you want to approve this Invoice?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, approve it!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d'
            });

            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                Swal.close();
                this.table.ajax.reload(null, false);

                // Send approve request
                // const response = await axios.post(
                //     `${this.apiBaseUrl}/${id}/approve`,
                //     {},
                //     {
                //         headers: {
                //             'X-CSRF-TOKEN': this.csrfToken
                //         }
                //     }
                // );

                // if (response.data.success) {
                //     Swal.fire({
                //         icon: 'success',
                //         title: 'Success',
                //         text: 'GRN approved successfully',
                //         timer: 2000,
                //         showConfirmButton: false
                //     });

                //     // Reload table
                //     this.table.ajax.reload(null, false);
                // } else {
                //     throw new Error(response.data.message || 'Failed to approve GRN');
                // }
            }
        } catch (error) {
            // console.error('Error approving GRN:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to approve Invoice'
            });
        }
    }

    /**
     * Reject Invoice
     * @param {number} id - Invoice ID
     */
    async rejectInvoice(id) {
        try {
            const result = await Swal.fire({
                title: 'Reject Invoice?',
                text: 'Are you sure you want to reject this Invoice?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, reject it!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d'
            });

            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                Swal.close();
                this.table.ajax.reload(null, false);

                // Send approve request
                // const response = await axios.post(
                //     `${this.apiBaseUrl}/${id}/approve`,
                //     {},
                //     {
                //         headers: {
                //             'X-CSRF-TOKEN': this.csrfToken
                //         }
                //     }
                // );

                // if (response.data.success) {
                //     Swal.fire({
                //         icon: 'success',
                //         title: 'Success',
                //         text: 'GRN approved successfully',
                //         timer: 2000,
                //         showConfirmButton: false
                //     });

                //     // Reload table
                //     this.table.ajax.reload(null, false);
                // } else {
                //     throw new Error(response.data.message || 'Failed to approve GRN');
                // }
            }
        } catch (error) {
            // console.error('Error approving GRN:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Failed to reject Invoice'
            });
        }
    }
}


document.addEventListener('DOMContentLoaded', function () {
    new ReportOverview();
});
