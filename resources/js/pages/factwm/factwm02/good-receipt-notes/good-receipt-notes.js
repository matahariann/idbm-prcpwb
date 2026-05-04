import axios from 'axios';
import GoodReceiptForm from './good-receipt-notes-form.js';
import { toast } from '../../../../helpers';
import Swal from 'sweetalert2';

class GoodReceiptNotes {
    #goodReceiptNotesTable = $('#factwmf006-table');
    #goodReceiptNotesEndpoint = 'FACTWM/ts/good-receipt-notes';

    constructor() {
        this.form = new GoodReceiptForm(() => {
            this.#goodReceiptNotesTable.DataTable().ajax.reload();
        });
    }

    init() {
        const self = this;

        this.#goodReceiptNotesTable.on('init.dt', function () {
            var tfoot = self.#goodReceiptNotesTable.find('tfoot tr');
            var thead = self.#goodReceiptNotesTable.find('thead');

            tfoot.appendTo(thead);

            // Clear kolom checkbox dan action di search row
            $('#factwmf006-table thead tr:eq(1) th:eq(0)').html('');
            $('#factwmf006-table thead tr:eq(1) th:eq(1)').html('');

            // Pastikan checkbox select-all muncul setelah init
            setTimeout(function () {
                if ($('#select-all').length === 0) {
                    var checkboxHtml = '<div class="d-flex justify-content-center align-items-center h-100"><input type="checkbox" id="select-all" class="form-check-input"></div>';
                    $('#factwmf006-table thead tr:eq(1) th:first').html(checkboxHtml);

                    // Bind event untuk select-all
                    $('#select-all').off('click').on('click', function () {
                        var checked = this.checked;
                        $('.row-checkbox:not(:disabled)').prop('checked', checked).trigger('change');

                        // Trigger summary card update setelah select all
                        setTimeout(() => {
                            self.#toggleSummaryCard();
                        }, 100);
                    });
                }
            }, 200);
        });

        this.#events();
        this.#initializeSummaryCard();
    }

    #events() {
        const self = this;

        $(document).on('click', '.btn-view-methods', async function () {
            let tr = $(this).closest('tr');
            let table = $('#factwmf006-table').DataTable();
            let row = table.row(tr);

            let grId = $(this).data('id');
            let icon = $(this).find('i');
            icon.attr('class', 'menu-icon icon-base ti tabler-square-minus');

            if (row.child.isShown()) {
                row.child.hide();
                icon.attr('class', 'menu-icon icon-base ti tabler-square-plus');
                return;
            }

            try {
                const response = await axios.get(`${self.#goodReceiptNotesEndpoint}/${grId}`);
                const details = response.data.data.details || [];

                row.child(self.#detailsTable(details)).show();
            } catch (error) {
                console.error(error);
                toast.error('Failed to load details');
                icon.attr('class', 'menu-icon icon-base ti tabler-square-plus');
            }
        });

        $(document).on('click', '#btn-approve-selected', function () {
            self.#approvedSelected();
        });

        $(document).on('click', '.dispute-menu', function () {
            self.form.openModal($(this).data('id'));
        });

        $(document).on('click', '.approve-menu', function () {
            self.#approved($(this).data('id'));
        });

        $(document).on('click', '.approve-dispute', function () {
            self.#approvedDispute($(this).data('id'));
        });

        $(document).on('click', '.reject-dispute', function () {
            self.#rejectedDispute($(this).data('id'));
        });

        $(document).on('click', '#btn-export', function () {
            const table = self.#goodReceiptNotesTable.DataTable();
            const params = table.ajax.params();
            const searchParams = new URLSearchParams();

            if (params?.search?.value) {
                searchParams.append('search[value]', params.search.value);
            }

            if (Array.isArray(params?.columns)) {
                params.columns.forEach((column, index) => {
                    if (column?.data) {
                        searchParams.append(`columns[${index}][data]`, column.data);
                    }

                    if (column?.search?.value) {
                        searchParams.append(`columns[${index}][search][value]`, column.search.value);
                    }
                });
            }

            const queryString = searchParams.toString();
            const exportUrl = '/FACTWM/ts/good-receipt-notes/export';

            window.location.href = queryString ? `${exportUrl}?${queryString}` : exportUrl;
        });

        // Event listener untuk checkbox changes
        $(document).on('change', '.row-checkbox', function () {
            const $this = $(this);
            const isChecked = $this.is(':checked');
            const $row = $this.closest('tr');

            const currentGrNumber = $row.find('.gr-number').text().trim();
            const currentRefType = $row.find('.ref-type').data('type');
            const currentReturnRef = String($row.find('.ref-type').data('return-ref') ?? '').trim();

            // Prevent recursive triggering
            if ($this.data('syncing')) return;

            $('#factwmf006-table tbody tr').each(function () {
                const $refTypeSpan = $(this).find('.ref-type');
                const refType = $refTypeSpan.data('type');
                const returnRef = String($refTypeSpan.data('return-ref') ?? '').trim();
                const grNumber = $(this).find('.gr-number').text().trim();
                const $pairCheckbox = $(this).find('.row-checkbox');

                if ($pairCheckbox.is($this)) return; // skip self

                let shouldSync = false;

                if (currentRefType === 'RECEIPT') {
                    // This row is RECEIPT — find RETURN rows that reference this GR number
                    shouldSync = refType === 'RETURN' && returnRef === currentGrNumber;
                } else if (currentRefType === 'RETURN') {
                    // This row is RETURN — find RECEIPT rows whose GR number matches our return-ref
                    shouldSync = (refType === 'RECEIPT') && grNumber === currentReturnRef;
                }

                if (shouldSync) {
                    $pairCheckbox.data('syncing', true);
                    $pairCheckbox.prop('checked', isChecked);
                    $pairCheckbox.removeData('syncing');
                }
            });

            self.#toggleSummaryCard();
        });

        $(document).on('change', '#entries', e => {
            const perPage = $(e.target).val();
            this.#goodReceiptNotesTable.DataTable().page.len(perPage).draw();
        });
    }

    /**
     * Initialize summary card functionality
     */
    #initializeSummaryCard() {
        // Initial check saat page load
        this.#toggleSummaryCard();
    }

    /**
     * Toggle summary card visibility based on selected checkboxes
     */
    #toggleSummaryCard() {
        const checkedCheckboxes = $(".row-checkbox:checked:not(:disabled)");
        const summaryCard = $("#summary-card");

        if (checkedCheckboxes.length > 0) {
            summaryCard.removeClass('d-none');
            this.#updateSummaryData(checkedCheckboxes);
        } else {
            summaryCard.addClass('d-none');
            this.#clearSummaryData();
        }
    }

    /**
     * Update summary data from backend
     */
    #updateSummaryData(checkedCheckboxes) {
        const selectedIds = [];

        checkedCheckboxes.each(function () {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length > 0) {
            axios.post(this.#goodReceiptNotesEndpoint + '/summary', {
                ids: selectedIds
            })
                .then(response => {
                    const data = response.data.data;

                    // Update nilai di summary card dengan format rupiah
                    $('#subtotal').val(this.#formatRupiah(data.subtotal));
                    $('#ppn').val(this.#formatRupiah(data.ppn));
                    $('#dppNilaiLain').val(this.#formatRupiah(data.dpp_nilai_lain));
                    $('#total').val(this.#formatRupiah(data.total));
                    $('#btn-approve-selected').prop('disabled', false);
                })
                .catch(error => {
                    console.error('Error fetching summary:', error);
                    toast.error('Failed to load summary data');
                    this.#clearSummaryData();
                });
        }
    }

    /**
     * Clear summary data fields
     */
    #clearSummaryData() {
        $('#subtotal').val('');
        $('#ppn').val('');
        $('#dppNilaiLain').val('');
        $('#total').val('');
    }

    #resetSelectionState() {
        $('#btn-approve-selected').prop('disabled', true).addClass('disabled');
        $('#select-all').prop('checked', false);
        $('.row-checkbox:checked:not(:disabled)').prop('checked', false);
        $('#summary-card').addClass('d-none');
        this.#clearSummaryData();
    }

    /**
     * Format number to Rupiah format
     */
    #formatRupiah(angka) {
        if (!angka || angka === 0) return '0';

        const number = parseFloat(angka);
        return number.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    #rejectedDispute(grId) {
        axios.get(this.#goodReceiptNotesEndpoint + '/' + grId).then(response => {
            const gr = response.data.data;

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
                    const endpoint = `${this.#goodReceiptNotesEndpoint}/reject-dispute/${grId}`;
                    const description = result.value.description;

                    axios
                        .post(endpoint, {
                            description: description
                        })
                        .then(async response => {
                            this.#goodReceiptNotesTable.DataTable().ajax.reload();
                            toast.success(response.data.message);
                        })
                        .catch(error => {
                            toast.error(error.response.data.message);
                        });
                }
            });
        });
    }

    #detailsTable(details = []) {
        console.log(details);

        let rows = details
            .map(
                (detail, index) => `
          <tr>
            <td>${detail.VORDER_NO ?? '-'}</td>
            <td>${detail.VLINE_NO ?? '-'}</td>
            <td>${detail.VRELEASE_NO ?? '-'}</td>
            <td>${detail.VMATERIAL_CODE ?? '-'}</td>
            <td>${detail.VDESCRIPTION ?? '-'}</td>
            <td>${detail.IQTY ?? '-'}</td>
            <td>${detail.UOM ?? '-'}</td>
            <td>${this.formatNumber(detail.VPRICE ?? '-')}</td>
            <td>${this.formatNumber(detail.VAMOUNT ?? '-')}</td>
            <td>${this.formatNumber(detail.DPP ?? '-')}</td>
            <td>${this.formatNumber(detail.PPN ?? '-')}</td>
            <td>${this.formatNumber(detail.TOTAL ?? '-')}</td>
          </tr>
        `
            )
            .join('');

        return `
      <table class="table table-bordered mb-0">
        <thead>
          <tr>
            <th>Order No</th>
            <th>Line No</th>
            <th>Release No</th>
            <th>Material Code</th>
            <th>Description</th>
            <th>Quantity</th>
            <th>UOM</th>
            <th>Price</th>
            <th>Subtotal</th>
            <th>DPP Nilai Lain lain</th>
            <th>PPN</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    `;
    }

    #approved(grId) {
        axios.get(this.#goodReceiptNotesEndpoint + '/' + grId).then(response => {
            const gr = response.data.data;

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
                    const endpoint = `${this.#goodReceiptNotesEndpoint}/approve/${grId}`;

                    axios
                        .post(endpoint, {
                            DAPPROVE: isApproved ? null : new Date().toISOString().slice(0, 10)
                        })
                        .then(async response => {
                            this.#goodReceiptNotesTable.DataTable().ajax.reload();
                            toast.success(response.data.message);
                        })
                        .catch(error => {
                            toast.error(error.response.data.message);
                        });
                }
            });
        });
    }

    #approvedSelected() {
        const selectedIds = $('input[name="selected[]"]:checked')
            .map((_, checkbox) => checkbox.value)
            .get();

        Swal.fire({
            title: 'Are you sure?',
            text: `You sure want to approve ${selectedIds.length} selected data?`,
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Yes, approve it!',
            customClass: {
                confirmButton: 'btn btn-outline-danger',
                cancelButton: 'btn btn-primary'
            }
        }).then(result => {
            if (result.isConfirmed) {
                axios
                    .post(this.#goodReceiptNotesEndpoint + '/approve-multiple', { ids: selectedIds })
                    .then(async response => {
                        this.#goodReceiptNotesTable.DataTable().ajax.reload(() => {
                            this.#resetSelectionState();
                        }, false);
                        toast.success(response.data.message);
                    })
                    .catch(error => {
                        toast.error(
                            error.response?.data?.message,
                            '<span class="text-white">Error</span>' || 'Error occurred'
                        );
                    });
            }
        });
    }

    #approvedDispute(grId) {
        axios.get(this.#goodReceiptNotesEndpoint + '/' + grId).then(response => {
            const gr = response.data.data;

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
                    const endpoint = `${this.#goodReceiptNotesEndpoint}/approve-dispute/${grId}`;

                    axios
                        .post(endpoint)
                        .then(async response => {
                            this.#goodReceiptNotesTable.DataTable().ajax.reload();
                            toast.success(response.data.message);
                        })
                        .catch(error => {
                            toast.error(error.response.data.message);
                        });
                }
            });
        });
    }

    formatNumber(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new GoodReceiptNotes().init();
});
