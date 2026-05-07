import axios from 'axios';
import { toast } from '../../../../helpers';
import Swal from 'sweetalert2';

class Vendor {
    #vendorTable = $('#prcpwbf002-table');
    #keyword = '';

    init() {
        const self = this;
        // DataTable init
        this.#vendorTable.on('init.dt', function () {
            var tfoot = self.#vendorTable.find('tfoot tr');
            var thead = self.#vendorTable.find('thead');
            if (tfoot.length) {
                tfoot.appendTo(thead);
            }
        });

        this.#vendorTable.on('preXhr.dt', function(e, settings, data) {
            if (self.#keyword) {
                data['keyword'] = self.#keyword;
            }
        });

        this.#filterEvents();
    }

    #filterEvents() {
        $(document).on('change', '#entries', e => {
            const perPage = $(e.target).val();
            const table = this.#vendorTable.DataTable();
            table.page.len(perPage).draw();
        });

        let searchTimeout;
        $(document).on('keyup', '#search-input', e => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.#keyword = $(e.target).val();
                this.#vendorTable.DataTable().ajax.reload();
            }, 500);
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new Vendor().init();
});