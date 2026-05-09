import axios from 'axios';
import { toast } from '../../../../helpers'; 
import Swal from 'sweetalert2';

class DailyRequest {
    #dailyRequestTable = $('#prcpwbf005-table');
    // #dailyRequestEndpoint = 'PRCPWB/ts/daily-request';
    #keyword = '';
    #selectedIds  = new Set();
    #excludedIds  = new Set();
    #isSelectAllRecords = false;
    #totalRecords = 0;
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

        this.#dailyRequestTable.on('preXhr.dt', function (e, settings, data) {
            if (self.#keyword) data['keyword'] = self.#keyword;
        });

        this.#dailyRequestTable.on('draw.dt', function () {
            const dtApi = self.#dailyRequestTable.DataTable();
            self.#totalRecords = dtApi.page.info().recordsTotal;
            self.#restoreCheckboxStates();
            self.#updateButtonStates();
        });

        this.#filterEvents();
        this.#events();
    }

    #restoreCheckboxStates() {
        $("input[name='selected[]']").each((_, el) => {
            const id = $(el).val();
            if (this.#isSelectAllRecords) {
                $(el).prop('checked', !this.#excludedIds.has(id));
            } else {
                $(el).prop('checked', this.#selectedIds.has(id));
            }
        });

        const totalOnPage   = $("input[name='selected[]']").length;
        const checkedOnPage = $("input[name='selected[]']:checked").length;
        $('#select-all').prop('checked', totalOnPage > 0 && totalOnPage === checkedOnPage);
    }

    #getSelectedCount() {
        if (this.#isSelectAllRecords) {
            return this.#totalRecords - this.#excludedIds.size;
        }
        return this.#selectedIds.size;
    }

    #updateButtonStates() {
        const count = this.#getSelectedCount();

        if (count > 0) {
            $('#export-excel').removeClass('disabled');
        } else {
            $('#export-excel').addClass('disabled');
        }
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
                this.#keyword = $(e.target).val();
                this.#updateQuery({ keyword: this.#keyword });
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

    #events(){
        const self = this;

        // Checkbox individual
        $(document).on('change', "input[name='selected[]']", function () {
            const id      = $(this).val();
            const checked = $(this).is(':checked');

            if (self.#isSelectAllRecords) {
                if (!checked) {
                    self.#excludedIds.add(id);
                } else {
                    self.#excludedIds.delete(id);
                }
            } else {
                if (checked) {
                    self.#selectedIds.add(id);
                } else {
                    self.#selectedIds.delete(id);
                }
            }

            self.#restoreCheckboxStates();
            self.#updateButtonStates();
        });

        // Select-all header checkbox
        $(document).on('click', '#select-all', function () {
            if (this.checked) {
                self.#isSelectAllRecords = true;
                self.#selectedIds.clear();
                self.#excludedIds.clear();
            } else {
                self.#clearAllSelections();
            }

            self.#restoreCheckboxStates();
            self.#updateButtonStates();
        });

        // Export Excel
        $(document).on('click', '#export-excel', function () {
            if (self.#getSelectedCount() === 0) {
                toast.error('Pilih minimal satu data untuk diekspor.');
                return;
            }
            self.#exportSelected();
        });
    }

    #buildExportUrl() {
        const params = new URLSearchParams();

        if (this.#isSelectAllRecords) {
            params.append('selectAll', '1');
            if (this.#keyword) params.append('keyword', this.#keyword);
            this.#excludedIds.forEach(id => params.append('excludedIds[]', id));
        } else {
            this.#selectedIds.forEach(id => params.append('ids[]', id));
        }

        return `/PRCPWB/ts/daily-request/export?${params.toString()}`;
    }

    #exportSelected() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/PRCPWB/ts/daily-request/export';
        form.style.display = 'none';

        // CSRF Token
        const csrf = document.createElement('input');
        csrf.type  = 'hidden';
        csrf.name  = '_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrf);

        if (this.#isSelectAllRecords) {
            this.#appendHidden(form, 'selectAll', '1');
            if (this.#keyword) this.#appendHidden(form, 'keyword', this.#keyword);
            this.#excludedIds.forEach(id => this.#appendHidden(form, 'excludedIds[]', id));
        } else {
            this.#selectedIds.forEach(id => this.#appendHidden(form, 'ids[]', id));
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    // Helper
    #appendHidden(form, name, value) {
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = name;
        input.value = value;
        form.appendChild(input);
    }

    #clearAllSelections() {
        this.#isSelectAllRecords = false;
        this.#selectedIds.clear();
        this.#excludedIds.clear();
    }

    #applySort() {
        const column = $('#sort-column').val();
        const direction = $('#sort-direction').val();
        const table = this.#dailyRequestTable.DataTable();

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