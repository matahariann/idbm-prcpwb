import axios from 'axios';
import { toast } from '../../../../helpers'; 
import Swal from 'sweetalert2';

class DailyRequest {
    #dailyRequestTable = $('#prcpwbf005-table');

    #columnIndexMap = {
    'VVENDORNO'             : 1,
    'VVENDORNAME'           : 2,
    'DWANTEDRECEIPTDATE'    : 3,
    'VTIME'                 : 4,
    'VPARTNO'               : 5,
    'VPARTDESCRIPTION'      : 6,
    'IQUANTITY'             : 9,  
    'IQUANTITYCONFIRMATION' : 10, 
    'IQUANTITYACTUAL'       : 11, 
    'VSTATUS'               : 12, 
    'VPONO'                 : 13, 
    'VDAILYREQNO'           : 14, 
    'VDELIVERYNOTENO'       : 15, 
    'VPRODUCTFAMILY'        : 16, 
    'DMODI'                 : 17, 
};


    init() {
        const self = this;

        // Pindahkan tfoot ke thead jika diperlukan untuk filter per kolom
        this.#dailyRequestTable.on('init.dt', function () {
            var tfoot = self.#dailyRequestTable.find('tfoot tr');
            var thead = self.#dailyRequestTable.find('thead');
            if (tfoot.length) {
                tfoot.appendTo(thead);
            }
        });

        this.#filterEvents();
        this.#events();
    }

    #events(){
        const self = this;
        $(document).on('click', '#export-excel', function () {
            self.#dailyRequestTable.DataTable().button('.buttons-excel').trigger();
        });
    }

    #filterEvents() {
        // Handle perubahan jumlah entries (10, 25, 50, dsb)
        $(document).on('change', '#entries', e => {
            const perPage = $(e.target).val();
            const table = this.#dailyRequestTable.DataTable();
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

        // Sort by column
        $(document).on('change', '#sort-column', () => {
            this.#applySort();
        });

        // Sort direction
        $(document).on('change', '#sort-direction', () => {
            if ($('#sort-column').val()) {
                this.#applySort();
            }
        });
    }

    #applySort() {
        const column = $('#sort-column').val();
        const direction = $('#sort-direction').val();
        const table = this.#dailyRequestTable.DataTable();

        console.log('sort column:', column, 'index:', this.#columnIndexMap[column]); // debug

        if (!column) {
            // Reset sorting jika kolom dikosongkan
            table.order([]).draw();
            return;
        }

        const colIndex = this.#columnIndexMap[column];
        if (colIndex !== undefined) {
            table.order([colIndex, direction]).draw();
        }
    }

    #updateQuery(params) {
        const table = this.#dailyRequestTable.DataTable();
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
    new DailyRequest().init();
});