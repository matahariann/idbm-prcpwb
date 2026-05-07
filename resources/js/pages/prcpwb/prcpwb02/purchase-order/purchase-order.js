import axios from 'axios';
import { toast } from '../../../../helpers'; 
import Swal from 'sweetalert2';

class PurchaseOrder {
    #poTable = $('#prcpwbf004-table');

    init() {
        const self = this;

        // Pindahkan tfoot ke thead jika diperlukan untuk filter per kolom
        this.#poTable.on('init.dt', function () {
            var tfoot = self.#poTable.find('tfoot tr');
            var thead = self.#poTable.find('thead');
            if (tfoot.length) {
                tfoot.appendTo(thead);
            }
        });

        this.#filterEvents();
        this.#events();
    }

    #events() {
        const self = this;

        // Event Klik tombol Detail
        $(document).on('click', '.detail-menu', function (e) {
            const id = $(this).data('id');
            window.location.href = `/inbox-po/detail/${id}`;
        });
    }

    #filterEvents() {
        // Handle perubahan jumlah entries (10, 25, 50, dsb)
        $(document).on('change', '#entries', e => {
            const perPage = $(e.target).val();
            const table = this.#poTable.DataTable();
            table.page.len(perPage).draw();
        });

        // Handle Search Input dengan Debounce 500ms
        let searchTimeout;
        $(document).on('keyup', '#search-input', e => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const keyword = $(e.target).val();
                this.#updateQuery({ keyword });
            }, 500);
        });
    }

    #updateQuery(params) {
        const table = this.#poTable.DataTable();
        const currentUrl = new URL(window.location.href);
        const searchParams = new URLSearchParams(currentUrl.search);

        // Update atau hapus parameter pencarian di URL
        for (const key in params) {
            if (params[key] === '' || params[key] === null || params[key] === undefined) {
                searchParams.delete(key);
            } else {
                searchParams.set(key, params[key]);
            }
        }

        // Reset ke halaman 1 setiap kali mencari
        searchParams.set('page', 1);

        // Update URL AJAX DataTable dan reload
        const newUrl = `${currentUrl.pathname}?${searchParams.toString()}`;
        table.ajax.url(newUrl).load();
        
        // Opsional: Update URL di browser tanpa reload halaman
        window.history.pushState({}, '', newUrl);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new PurchaseOrder().init();
});