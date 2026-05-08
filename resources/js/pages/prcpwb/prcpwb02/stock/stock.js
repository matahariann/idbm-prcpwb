import axios from 'axios';
import { toast } from '../../../../helpers'; 
import Swal from 'sweetalert2';

class Stock {
    #stockTable = $('#prcpwbf006-table');

    init() {
        const self = this;

        // Pindahkan tfoot ke thead jika diperlukan untuk filter per kolom
        this.#stockTable.on('init.dt', function () {
            var tfoot = self.#stockTable.find('tfoot tr');
            var thead = self.#stockTable.find('thead');
            if (tfoot.length) {
                tfoot.appendTo(thead);
            }
        });

        this.#filterEvents();
        this.#events();
    }

    #events(){
        const self = this;

        // Eksport Excel
        $(document).on('click', '#export-excel', function () {
            self.#stockTable.DataTable().button('.buttons-excel').trigger();
        });

        // Eksport PDF
        $(document).on('click', '#export-pdf', function () {
            self.#exportPdf();
        });
    }

    #filterEvents() {
        // Handle perubahan jumlah entries (10, 25, 50, dsb)
        $(document).on('change', '#entries', e => {
            const perPage = $(e.target).val();
            const table = this.#stockTable.DataTable();
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
        const table = this.#stockTable.DataTable();
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

    #exportPdf() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

        const table   = this.#stockTable.DataTable();
        const allData = table.rows({ search: 'applied' }).data().toArray();

        // Ambil header dari kolom yang visible (skip kolom index & action)
        const visibleColumns = table.columns(':visible').indexes().toArray()
            .filter(i => i !== 0); // skip kolom DT_RowIndex

        const headers = visibleColumns.map(i =>
            $(table.column(i).header()).text().trim()
        );

        // Ambil data rows
        const rows = allData.map(row => {
            return visibleColumns.map(i => {
                const cell = table.cell(
                    table.rows({ search: 'applied' }).nodes()[allData.indexOf(row)],
                    i
                );
                // Strip HTML (untuk kolom judgment)
                const raw = String(table.column(i).data()[allData.indexOf(row)] ?? '');
                const stripped = raw.replace(/<[^>]*>/g, '').trim();
                return stripped || String(Object.values(row)[i] ?? '');
            });
        });

        // Warna berdasarkan nilai judgment 
        const judgmentColIndex = headers.indexOf('Judgment');

        // Header dokumen 
        const pageW = doc.internal.pageSize.getWidth();

        // Logo area / accent bar
        doc.setFillColor(25, 118, 210); // biru
        doc.rect(0, 0, pageW, 18, 'F');

        // Judul
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(13);
        doc.setTextColor(255, 255, 255);
        doc.text('PRCPWB – Transaction Data Stock', 14, 7);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(220, 235, 255);
        doc.text('IDBM – PO Web', 14, 13);

        // Tanggal export (kanan atas)
        const now = new Date();
        const dateStr = now.toLocaleString('id-ID', {
            day: '2-digit', month: 'long', year: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
        doc.setFontSize(7.5);
        doc.setTextColor(200, 225, 255);
        doc.text(`Exported: ${dateStr}`, pageW - 14, 13, { align: 'right' });

        // autoTable 
        doc.autoTable({
            head: [headers],
            body: rows,
            startY: 22,
            margin: { left: 14, right: 14 },
            styles: {
                fontSize: 7.5,
                cellPadding: { top: 3, bottom: 3, left: 4, right: 4 },
                lineColor: [220, 228, 240],
                lineWidth: 0.3,
                textColor: [30, 40, 60],
                font: 'helvetica',
                overflow: 'linebreak',
            },
            headStyles: {
                fillColor: [25, 118, 210],
                textColor: [255, 255, 255],
                fontStyle: 'bold',
                fontSize: 8,
                halign: 'center',
                cellPadding: { top: 4, bottom: 4, left: 4, right: 4 },
            },
            alternateRowStyles: {
                fillColor: [240, 246, 255],
            },
            didParseCell: (data) => {
                // Warnai kolom Judgment
                if (data.section === 'body' && data.column.index === judgmentColIndex) {
                    const val = String(data.cell.raw).toLowerCase();
                    if (val === 'green') {
                        data.cell.styles.fillColor = [46, 204, 113];
                        data.cell.styles.textColor = [255, 255, 255];
                        data.cell.styles.fontStyle = 'bold';
                        data.cell.styles.halign = 'center';
                    } else if (val === 'red') {
                        data.cell.styles.fillColor = [231, 76, 60];
                        data.cell.styles.textColor = [255, 255, 255];
                        data.cell.styles.fontStyle = 'bold';
                        data.cell.styles.halign = 'center';
                    } else if (val === 'yellow') {
                        data.cell.styles.fillColor = [241, 196, 15];
                        data.cell.styles.textColor = [50, 40, 0];
                        data.cell.styles.fontStyle = 'bold';
                        data.cell.styles.halign = 'center';
                    }
                }
                // Kolom numerik rata kanan
                const numericCols = ['Current Stock', 'DR Qty', 'Outstanding'];
                if (data.section === 'body' && numericCols.includes(headers[data.column.index])) {
                    data.cell.styles.halign = 'right';
                }
            },
            didDrawPage: (data) => {
                // Footer tiap halaman
                const pageH = doc.internal.pageSize.getHeight();
                doc.setFillColor(240, 244, 250);
                doc.rect(0, pageH - 10, pageW, 10, 'F');
                doc.setFontSize(7);
                doc.setTextColor(120, 130, 150);
                doc.text(
                    `Page ${data.pageNumber} of ${doc.internal.getNumberOfPages()}`,
                    pageW / 2, pageH - 4, { align: 'center' }
                );
                doc.text('© IDBM – PO Web System', 14, pageH - 4);
            },
        });

        const filename = `PRCPWBF006_${now.toISOString().slice(0,10).replace(/-/g,'')}.pdf`;
        doc.save(filename);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new Stock().init();
});