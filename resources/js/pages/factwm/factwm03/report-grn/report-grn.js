/**
 * Report GRN - FACTWMF010
 * Manages GRN (Goods Receipt Note) DataTable with expandable rows
 */

import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import axios from 'axios';
import Swal from 'sweetalert2';

class ReportGrn {
    constructor() {
        this.table = null;
        this.dateRangePicker = null;
        this.deliveryDatePicker = null;
        this.apiBaseUrl = 'FACTWM/ts/good-receipt-notes';
        this.csrfToken = $('meta[name="csrf-token"]').attr('content');
        this.tableElement = $('#factwmf010-table');
    }

    /**
     * Initialize the module
     */
    init() {
        this.initDataTable();
        this.initDatePickers();
        this.initFilterHandlers();
        this.initActionHandlers();
    }

    /**
     * Initialize DataTable
     */
    initDataTable() {
        this.table = $.fn.DataTable.isDataTable(this.tableElement)
            ? this.tableElement.DataTable()
            : null;
    }


    /**
     * Initialize Date Pickers using Flatpickr
     */
    initDatePickers() {
        // Date Range Picker
        const $dateRangeInput = $('#dateRangePicker');
        if ($dateRangeInput.length > 0) {
            this.dateRangePicker = flatpickr($dateRangeInput[0], {
                mode: 'range',
                dateFormat: 'd/m/Y',
                allowInput: false,
                onClose: (selectedDates, dateStr, instance) => {
                    if (selectedDates.length === 2) {
                        console.log('Date range selected:', dateStr);
                    }
                }
            });
        }

        // Delivery Date Picker
        const $deliveryDateInput = $('#deliveryDate');
        if ($deliveryDateInput.length > 0) {
            this.deliveryDatePicker = flatpickr($deliveryDateInput[0], {
                dateFormat: 'd/m/Y',
                allowInput: false,
                onClose: (selectedDates, dateStr, instance) => {
                    console.log('Delivery date selected:', dateStr);
                }
            });
        }
    }

    /**
     * Initialize Filter Handlers
     */
    initFilterHandlers() {
        // Show Data Button
        $('#showDataBtn').on('click', () => this.applyFilters());

        // Show Entries
        $('#showEntries').on('change', (e) => {
            const length = parseInt($(e.target).val());
            this.table.page.len(length).draw();
        });

        // Status Filter
        $('#selectStatus').on('change', (e) => {
            const status = $(e.target).val();
            this.table.column(2).search(status).draw(); // Column 2 is status_grn
        });

        // Currency Filter
        $('#selectCurrency').on('change', (e) => {
            const currency = $(e.target).val();
            this.table.column(7).search(currency).draw(); // Column 7 is currency
        });

        // Global Search
        $('#searchInput').on('keyup', (e) => {
            this.table.search($(e.target).val()).draw();
        });

        // Export Button
        $('#exportBtn').on('click', () => this.exportData());
    }

    /**
     * Initialize Action Handlers
     */
    initActionHandlers() {
        // Edit GRN Button
        $(document).on('click', '.approve-dispute', (e) => {
            const id = $(e.currentTarget).data('id');
            this.editGrn(id);
        });

        // Approve GRN Button
        $(document).on('click', '.reject-dispute', (e) => {
            const id = $(e.currentTarget).data('id');
            this.approveGrn(id);
        });
    }

    /**
     * Apply all filters and reload table
     */
    applyFilters() {
        const dateRange = $('#dateRangePicker').val() || '';
        const deliveryDate = $('#deliveryDate').val() || '';
        const currency = $('#selectCurrency').val() || '';
        const status = $('#selectStatus').val() || '';

        console.log('Applying filters:', {
            dateRange,
            deliveryDate,
            currency,
            status
        });

        // Apply filters to DataTable columns
        if (status) {
            this.table.column(2).search(status);
        }
        if (currency) {
            this.table.column(7).search(currency);
        }

        // Reload table
        this.table.ajax.reload();
    }

    /**
     * Export data to Excel/CSV
     */
    exportData() {
        const params = this.table?.ajax?.params?.() ?? null;
        const searchParams = new URLSearchParams();

        if (params?.search?.value) {
            searchParams.append('search[value]', params.search.value);
        }

        if (Array.isArray(params?.columns)) {
            params.columns.forEach((column, index) => {
                if (column?.data) {
                    searchParams.append(`columns[${index}][data]`, column.data);
                }

                if (column?.name) {
                    searchParams.append(`columns[${index}][name]`, column.name);
                }

                if (column?.search?.value) {
                    searchParams.append(`columns[${index}][search][value]`, column.search.value);
                }
            });
        }

        const exportUrl = '/FACTWM/rt/gr-notes/export';
        const queryString = searchParams.toString();

        window.location.href = queryString ? `${exportUrl}?${queryString}` : exportUrl;

        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Export',
            text: 'Data exported successfully!',
            timer: 2000,
            showConfirmButton: false
        });

        // TODO: Implement actual export functionality
        // You can use libraries like xlsx, papa parse, or send to backend
    }

    /**
     * Edit GRN
     * @param {number} id - GRN ID
     */
    async editGrn(id) {
        axios.get(this.apiBaseUrl + '/' + id).then(response => {
            const gr = response.data.data;
            console.log(gr, 'test');

            const isApproved = gr.VSTATUS === 'APPROVED';
            const action = isApproved ? 'unapprove' : 'approve';
            const title = isApproved ? 'Unapprove GR?' : 'Approve GR?';
            const text = isApproved
                ? `You sure want to unapprove ${gr.VGR_NUMBER}?`
                : `You sure want to approve ${gr.VGR_NUMBER}?`;
            const confirmText = isApproved ? 'Yes, unapprove it!' : 'Yes, approve it!';
            const icon = isApproved ? 'warning' : 'success';

            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonText: confirmText,
                customClass: {
                    confirmButton: 'btn btn-outline-danger',
                    cancelButton: 'btn btn-primary'
                }
            }).then(result => {
                if (result.isConfirmed) {
                    const endpoint = `${this.apiBaseUrl}/approve-dispute/${id}`;

                    axios
                        .post(endpoint)
                        .then(async response => {
                            this.tableElement.DataTable().ajax.reload();
                            toast.success(response.data.message);
                        })
                        .catch(error => {
                            toast.error(error.response.data.message);
                        });
                }
            });
        });
    }

    /**
     * Approve GRN
     * @param {number} id - GRN ID
     */
    async approveGrn(id) {
        axios.get(this.apiBaseUrl + '/' + id).then(response => {
            const gr = response.data.data;
            console.log(gr, 'test');

            const isApproved = gr.VSTATUS === 'DISPUTED';
            const action = isApproved ? 'reject dispute' : 'approve dispute';
            const title = isApproved ? 'Reject Dispute?' : 'Approve Dispute?';
            const text = isApproved
                ? `You sure want to reject dispute for GR NUMBER ${gr.VGR_NUMBER}?`
                : `You sure want to approve dispute for GR NUMBER ${gr.VGR_NUMBER}?`;
            const confirmText = isApproved ? 'Yes, reject it!' : 'Yes, approve it!';
            const icon = isApproved ? 'warning' : 'success';

            Swal.fire({
                title: title,
                html: `
                <p>${text}</p>
                <div class="mt-3">
                    <label for="reject-description" class="form-label text-start d-block">
                        <strong>Reason for Rejection:</strong>
                    </label>
                    <textarea
                        id="reject-description"
                        class="form-control"
                        rows="4"
                        placeholder="Enter reason for rejecting this dispute..."
                        style="resize: vertical;"
                        required
                    ></textarea>
                    <small class="text-danger d-block mt-1">* Required - Please provide a clear reason for rejection</small>
                </div>
            `,
                icon: icon,
                showCancelButton: true,
                confirmButtonText: confirmText,
                customClass: {
                    confirmButton: 'btn btn-outline-danger',
                    cancelButton: 'btn btn-primary'
                },
                preConfirm: () => {
                    const description = document.getElementById('reject-description').value;
                    if (!description || description.trim() === '') {
                        Swal.showValidationMessage('Please enter a reason for rejection');
                        return false;
                    }
                    return { description: description.trim() };
                }
            }).then(result => {
                if (result.isConfirmed) {
                    const endpoint = `${this.apiBaseUrl}/reject-dispute/${id}`;
                    const description = result.value.description;

                    axios
                        .post(endpoint, {
                            description: description
                        })
                        .then(async response => {
                            this.tableElement.DataTable().ajax.reload();
                            toast.success(response.data.message);
                        })
                        .catch(error => {
                            toast.error(error.response.data.message);
                        });
                }
            });
        });
    }

    /**
     * Show GRN Detail Modal
     * @param {Object} data - GRN data
     */
    // showGrnDetail(data) {
    //     const html = `
    //         <div class="row g-3">
    //             <div class="col-md-6">
    //                 <strong>GRN No:</strong> ${data.grn_no}
    //             </div>
    //             <div class="col-md-6">
    //                 <strong>Status:</strong>
    //                 <span class="badge bg-${this.getStatusColor(data.status_grn)}">${data.status_grn}</span>
    //             </div>
    //             <div class="col-md-6">
    //                 <strong>GRN Date:</strong> ${data.grn_date || '-'}
    //             </div>
    //             <div class="col-md-6">
    //                 <strong>Delivery No:</strong> ${data.delivery_no}
    //             </div>
    //             <div class="col-md-6">
    //                 <strong>PO Number:</strong> ${data.po_no}
    //             </div>
    //             <div class="col-md-6">
    //                 <strong>Currency:</strong> ${data.currency}
    //             </div>
    //             <div class="col-md-6">
    //                 <strong>Vendor Code:</strong> ${data.vendor_code}
    //             </div>
    //             <div class="col-md-6">
    //                 <strong>Vendor Name:</strong> ${data.vendor_name}
    //             </div>
    //             <div class="col-md-6">
    //                 <strong>Amount Before PPh:</strong> ${this.formatNumber(data.ammount_before_pph || 0)}
    //             </div>
    //             <div class="col-md-6">
    //                 <strong>Aging GRN:</strong> ${data.aging_grn || '-'} days
    //             </div>
    //         </div>

    //         <hr class="my-4">

    //         <h6 class="mb-3">Items Detail</h6>
    //         <div class="table-responsive">
    //             <table class="table table-sm table-bordered">
    //                 <thead class="table-secondary">
    //                     <tr>
    //                         <th>Part No</th>
    //                         <th>Description</th>
    //                         <th class="text-end">Qty</th>
    //                         <th class="text-end">Price</th>
    //                         <th>Currency</th>
    //                         <th class="text-end">Subtotal</th>
    //                         <th class="text-end">DPP Nilai Lain</th>
    //                         <th class="text-end">PPN</th>
    //                     </tr>
    //                 </thead>
    //                 <tbody>
    //                     ${this.renderItemsRows(data.items)}
    //                 </tbody>
    //             </table>
    //         </div>
    //     `;

    //     const $modalContent = $('#grnDetailContent');
    //     if ($modalContent.length > 0) {
    //         $modalContent.html(html);

    //         // Show modal using Bootstrap
    //         const $modal = $('#grnDetailModal');
    //         if ($modal.length > 0) {
    //             const modal = new bootstrap.Modal($modal[0]);
    //             modal.show();
    //         }
    //     }
    // }

    /**
     * Render items rows for table
     * @param {Array} items - Array of items
     * @returns {string} HTML string
     */
    renderItemsRows(items) {
        if (!items || items.length === 0) {
            return `
                <tr>
                    <td colspan="8" class="text-center text-muted">No items available</td>
                </tr>
            `;
        }

        return items.map(item => `
            <tr>
                <td>${item.part_number || '-'}</td>
                <td>${item.description || '-'}</td>
                <td class="text-end">${item.qty || 0}</td>
                <td class="text-end">${this.formatNumber(item.price || 0)}</td>
                <td>${item.currency || '-'}</td>
                <td class="text-end">${this.formatNumber(item.sub_total || 0)}</td>
                <td class="text-end">${this.formatNumber(item.dpp_nilai_lain || 0)}</td>
                <td class="text-end">${this.formatNumber(item.ppn || 0)}</td>
            </tr>
        `).join('');
    }

    /**
     * Get status badge color
     * @param {string} status - Status string
     * @returns {string} Bootstrap color class
     */
    getStatusColor(status) {
        const colors = {
            'new': 'info',
            'approved': 'success',
            'closed': 'danger',
            'dispute': 'warning'
        };
        return colors[status.toLowerCase()] || 'secondary';
    }

    /**
     * Format number with thousand separators
     * @param {number} number - Number to format
     * @returns {string} Formatted number
     */
    formatNumber(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }

    /**
     * Clear all filters
     */
    clearFilters() {
        // Clear date pickers
        if (this.dateRangePicker) {
            this.dateRangePicker.clear();
        }
        if (this.deliveryDatePicker) {
            this.deliveryDatePicker.clear();
        }

        // Clear select dropdowns
        $('#selectCurrency').val('');
        $('#selectStatus').val('');
        $('#searchInput').val('');

        // Clear DataTable search
        this.table.search('').columns().search('').draw();
    }

    /**
     * Destroy the module and clean up
     */
    destroy() {
        if (this.dateRangePicker) {
            this.dateRangePicker.destroy();
        }
        if (this.deliveryDatePicker) {
            this.deliveryDatePicker.destroy();
        }
        $(document).off('click', '.edit-grn');
        $(document).off('click', '.check-grn');
    }
}

// Initialize on document ready
// $(document).ready(function () {
//     const reportGrn = new ReportGrn();
//     reportGrn.init();
// });

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    new ReportGrn().init();
});
