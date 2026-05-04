import axios from "axios";
import flatpickr from "flatpickr";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect"
import moment from "moment";

class VerifyPo {
    #verifyPoTable = $('#factwmf007-table');
    #verifyPoEndpoint = 'FACTWM/ts/verify-po';
    #selectedMonth = null;

    init() {
        const self = this;

        this.#events();
        this.#monthPicker();
    }

    #monthPicker() {
        const self = this;

        flatpickr('#month', {
            mode: 'range',
            dateFormat: 'Y-m',
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: "M Y", //defaults to "F Y"
                    altFormat: "F Y", //defaults to "F Y"
                })
            ],
            onClose: (selectedDates, dateStr, instance) => {
                if (selectedDates.length === 2) {
                    // Format start and end dates to YYYY-MM
                    const start = moment(selectedDates[0]).format("YYYY-MM");
                    const end = moment(selectedDates[1]).format("YYYY-MM");

                    const month = start + ' to ' + end;

                    self.#selectedMonth = month;
                    self.#updateQuery({ month })

                } else if (selectedDates.length === 1) {
                    // Single month selected
                    const single = formatDate(selectedDates[0], "Y-m");
                }
            }
        });
    }

    #events() {
        this.#filterEvents();
        this.#clickEvents();
    }

    #clickEvents() {
        const self = this;

        $(document).on('click', '#view', function () {
            const month = $(this).data('month');
            axios.post('/general/store-cache', {
                key: 'month_verify_po',
                payload: month
            })
                .then(() => {
                    // window.open(`/${self.#verifyPoEndpoint}/view`);
                    window.location.href = `/${self.#verifyPoEndpoint}/view`
                })
                .catch((error) => {
                    console.log(error)
                })
        });
    }

    #filterEvents() {
        const self = this;
        $(document).on('change', '#entries', e => {
            const perPage = $(e.target).val();
            const table = this.#verifyPoTable.DataTable();

            table.page.len(perPage).draw();
        });
    }

    #updateQuery(params) {
        const table = this.#verifyPoTable.DataTable();
        const currentUrl = new URL(window.location.href);
        const searchParams = new URLSearchParams(currentUrl.search);

        // Update parameters
        for (const key in params) {
            // delete key parameters if its empty for clearance
            if (params[key] === '' || params[key] === null || params[key] === undefined) {
                searchParams.delete(key);
            } else {
                searchParams.set(key, params[key]);
            }
        }

        searchParams.set('page', 1);

        // Update the AJAX URL and reload
        const newUrl = `${currentUrl.pathname}?${searchParams.toString()}`;
        table.ajax.url(newUrl).load();
    }
}

new VerifyPo().init();
