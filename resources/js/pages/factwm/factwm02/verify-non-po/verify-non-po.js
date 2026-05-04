import axios from 'axios';
import { _showInvalidError, toast } from '../../../../helpers.js';
import Swal from 'sweetalert2';
import { showLoadingSwal, closeSwal } from "../../../../swallLoading";

class VerifyNonPO {
    #verifyNonPOTable = $('#factwmf008-table');
    #verifyNonPOEndpoint = 'FACTWM/ts/verify-non-po';


    init() {
        const self = this;

        this.#verifyNonPOTable.on('init.dt', function () {
            $('#factwmf008-table thead tr:eq(1) th:eq(0)').html('');
        });

        this.#events();
    }

    #events() {
        this.#onClick();
    }

    #onClick() {
        const self = this;

        $(document).on('click', '.btn-view-details', async function () {
            let tr = $(this).closest('tr');
            let table = $('#factwmf008-table').DataTable();
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
                const response = await axios.get(`${self.#verifyNonPOEndpoint}/${grId}`);
                const details = response.data.data.details || [];

                row.child(self.#detailsTable(details)).show();
            } catch (error) {
                console.error(error);
                toast.error('Failed to load details');
                icon.attr('class', 'menu-icon icon-base ti tabler-square-plus');
            }
        });

        $('#btn-export').on('click', function () {
            window.location.href = '/FACTWM/ts/verify-non-po/export';
        });

        $('#btn-create').on('click', function (e) {
            window.location.href = '/FACTWM/ts/verify-non-po/create';
        });

        $(document).on('click', '.edit-non-po', function () {
            window.open(`/FACTWM/ts/verify-non-po/edit/${$(this).data('id')}`);
        });

        $(document).on('click', '.view-mode-non-po', function () {
            window.open(`/FACTWM/ts/verify-non-po/view/${$(this).data('id')}`);
        });

        $(document).on('click', '.resend-si', function (e) {
            let id = $(e.currentTarget).attr('data-id');
            let payload = $(e.currentTarget).attr('data-payload');
            const formData = new FormData();
            // formData.append('notes', notes);
            formData.append('payload', payload);
            showLoadingSwal();
            axios
                .post(`/${self.#verifyNonPOEndpoint}/reset-si/${id}`, formData)
                .then(response => {
                    const data = response.data.data;
                    toast.success(response.data.message);
                    $('#factwmf008-table').DataTable().ajax.reload();
                })
                .catch(error => {
                    if (error.response && error.response.status === 422) {
                        _showInvalidError(error.response.data.errors);
                    } else {
                        // console.log(error)
                        toast.error(error.response.data.message);
                    }
                }).finally(() => {
                    closeSwal();
                })
        });
    }

    #detailsTable(details = []) {
        let rows = details
            .map(
                (detail, index) => `
          <tr>
            <td style="white-space: normal; word-break: break-word; padding: 0.35rem 0.5rem;">${detail.VDESCRIPTION ?? '-'}</td>
            <td class="text-end text-nowrap" style="padding: 0.35rem 0.5rem;">${detail.IQTY ?? '-'}</td>
            <td class="text-center text-nowrap" style="padding: 0.35rem 0.5rem;">${detail.VUOM ?? '-'}</td>
            <td class="text-end text-nowrap" style="padding: 0.35rem 0.5rem;">${detail.IPRICE ?? '-'}</td>
            <td class="text-end text-nowrap" style="padding: 0.35rem 0.5rem;">${detail.ITOTAL ?? '-'}</td>
          </tr>
        `
            )
            .join('');

        return `
      <div class="px-2 py-1">
        <div style="max-width: 720px;">
        <table class="table table-sm table-bordered mb-0" style="width: 100%; table-layout: fixed; font-size: 0.8125rem;">
          <thead>
            <tr>
              <th style="padding: 0.4rem 0.5rem;">Description</th>
              <th class="text-end" style="padding: 0.4rem 0.5rem;">Qty</th>
              <th class="text-center" style="padding: 0.4rem 0.5rem;">UOM</th>
              <th class="text-end" style="padding: 0.4rem 0.5rem;">Price</th>
              <th class="text-end" style="padding: 0.4rem 0.5rem;">Amount</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
        </div>
      </div>
    `;
    }
}

new VerifyNonPO().init();
