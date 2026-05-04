<?php

namespace App\DataTables\Original\FACTWM03;

use App\Traits\DataTableTrait;
use Carbon\Carbon;
// use App\Models\FACTWM03\FACTWM_LOGLOGIN_HISTORY as LogHistory;
// use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
// use Ramsey\Collection\Collection;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Collection;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\QueryDataTable;
use Illuminate\Support\Facades\Auth;
use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION as Config;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER as Supplier;
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES as GRNote;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_PO as VerifyPo;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_NON_PO as VerifyNonPo;
use Exception;

final class FACTWMF011Datatable extends DataTable
{
    use DataTableTrait;

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Supplier> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): QueryDataTable
    {
        return (new QueryDataTable($query))
            ->editColumn('preview-pdf', function ($row) {
                $button = '-';

                if (($row->status_invoice == 'PRELIMENARY')) {
                    $button = $this->getPreviewPDFButton($row);
                }

                return $button;
            })
            ->editColumn('action', function ($row) {
                $button = '-';

                if (($row->status_invoice == 'PROCESSING' || $row->status_invoice == 'ESCALATED' || $row->status_invoice == 'FAILED')) {
                    $button = $this->getActionButton($row);
                }

                return $button;
            })
            ->editColumn('pdf_invoice', function ($row) {
                $button =  '-';
                $url = 'verify-po';
                if ($row->transaction_category == 'NON PO') {
                    $url = 'verify-non-po';
                }

                if (!empty($row->pdf_invoice)) {

                    $fileUrl = route($url . '.download', [$row->id, 'invoice']);

                    $button = '<a href="' . $fileUrl . '" target="_blank" class="btn btn-sm btn-outline-primary" title="Download/View File">
                        <i class="ti tabler-file-type-pdf"></i>
                    </a>';
                }
                return $button;
            })
            ->editColumn('pdf_tax_invoice', function ($row) {
                $button =  '-';
                $url = 'verify-po';
                if ($row->transaction_category == 'NON PO') {
                    $url = 'verify-non-po';
                }

                if (!empty($row->pdf_tax_invoice)) {

                    $fileUrl = route($url . '.download', [$row->id, 'tax']);

                    $button = '<a href="' . $fileUrl . '" target="_blank" class="btn btn-sm btn-outline-primary" title="Download/View File">
                        <i class="ti tabler-file-type-pdf"></i>
                    </a>';
                }
                return $button;
            })
            // ->editColumn('plan_paydate', function ($row) {
            //     $button =  '-';
            //     // if (!empty($row['pdf_tax_invoice'])) {
            //     //     $button =  ' <a href="' . asset('assets/pdf/report-invoice-dummy.pdf') . '" target="_blank"><i class="icon-base ti tabler-file-type-pdf"></i></a>';
            //     // }

            //     return $button;
            // })
            // ->editColumn('aging_ap', function ($row) {
            //     $button =  '-';
            //     // if (!empty($row['pdf_tax_invoice'])) {
            //     //     $button =  ' <a href="' . asset('assets/pdf/report-invoice-dummy.pdf') . '" target="_blank"><i class="icon-base ti tabler-file-type-pdf"></i></a>';
            //     // }

            //     return $button;
            // })
            ->editColumn('inv_date', function ($row) {
                return Carbon::parse($row->inv_date)->format('Y-m-d');
            })
            ->editColumn('tax_inv_date', function ($row) {
                return Carbon::parse($row->tax_inv_date)->format('Y-m-d');
            })
            ->editColumn('status_invoice', function ($row) {
                $color = $this->colorBadge($row->status_invoice);
                return '<span class="badge bg-' . $color . '">' . $row->status_invoice . '</span>';
            })
            // ->addColumn('checkbox', function ($row) {
            //     return '<input type="checkbox" class="form-check-input"
            //             name="selected-service[]" value="' . $row['IID'] . '">';
            // })
            ->addColumn('expand', function () {
                return '
                    <button class="btn btn-sm dt-expand">
                        <i class="icon-base ti tabler-plus"></i>
                    </button>
                ';
            })

            ->editColumn('doc_status', function ($row) {
                return $row->doc_status;
            })

            ->editColumn('date_submitted', function ($row) {
                return $row->date_submitted;
            })

            ->editColumn('date_approved', function ($row) {
                return $row->date_approved;
            })

            ->editColumn('sub_total', function ($row) {
                $total = $this->setCurrency((int)$row->sub_total);

                return $total;
            })

            ->editColumn('amount_before_pph', function ($row) {
                $total = $this->setCurrency((int)$row->sub_total ?? 0);

                return $total;
            })


            ->editColumn('dpp_nilai_lain', function ($row) {
                $total = $this->setCurrency((int)$row->dpp_nilai_lain ?? 0);

                return $total;
            })

            ->editColumn('dpp_pph', function ($row) {
                $total = $this->setCurrency((int)$row->dpp_pph);

                return $total;
            })

            ->editColumn('tarif_ppn', function ($row) {
                $total = $this->setCurrency((int)$row->tarif_ppn);

                return $total;
            })

            ->editColumn('nilai_pph', function ($row) {
                $total = $this->setCurrency((int)$row->nilai_pph);

                return $total;
            })

            ->editColumn('tarif_pph', function ($row) {
                $total = $this->setCurrency((int)$row->tarif_pph);

                return $total;
            })

            ->editColumn('grand_total', function ($row) {
                $total = $this->setCurrency((int)$row->grand_total);

                return $total;
            })
            ->editColumn('require_materai_ocr', function ($row) {
                return $row->require_materai_ocr ?: '-';
            })
            ->editColumn('ocr_materai_status', function ($row) {
                return $row->ocr_materai_status ?: '-';
            })
            // ->editColumn('BISACCEPTPRIVACY', function ($row) {
            //     return $row['BISACCEPTPRIVACY'] ? 'Accepted' : 'Not Accepted';
            // })

            ->rawColumns(['checkbox', 'preview-pdf', 'status_invoice', 'pdf_invoice', 'pdf_tax_invoice', 'action', 'expand'])
            ->setRowId('IID')
            ->filterColumn('inv_date', function ($query, $keyword) {
                $dates = explode(',', $keyword);
                $start_date = $dates[0];
                $end_date = $dates[1];
                $query->whereBetween('inv_date', [$start_date, $end_date]);
            })
            ->filter(function ($dataTable) {
                $keyword = request('keyword');

                if (! empty($keyword)) {
                    return $dataTable->filter(function ($row) use ($keyword) {
                        return str_contains(strtolower($row['inv_no']), strtolower($keyword))
                            || str_contains(strtolower($row['supplier_code']), strtolower($keyword))
                            || str_contains(strtolower($row['supplier_name']), strtolower($keyword))
                            || str_contains(strtolower($row['nik']), strtolower($keyword));
                    });
                }
            });
    }


    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Supplier>
     */
    public function query(): QueryBuilder
    {
        $request = $this->request();

        $poQuery = $this->getPOQuery();
        $nonPoQuery = $this->getNonPOQuery();

        $unionQuery = $poQuery->unionAll($nonPoQuery);

        return DB::query()
            ->fromSub($unionQuery, 'combined')
            ->when(
                Auth::user()?->supplierUser?->VSUPPLIER_CODE,
                fn($q, $supplierCode) =>
                $q->where('supplier_code', $supplierCode)
            )
            ->where("VSTATUS", 'submit')
            ->orderBy('DMODI', 'desc'); // ✅ Tambahkan ini
    }

    public function getPOQuery()
    {
        return DB::table('FACTWM_TRHVERIFY_PO as p')
            ->leftJoin('FACTWM_MSHSUPPLIERS as fm', 'fm.VSUPPLIER_CODE', '=', 'p.VSUPPLIER_CODE')
            ->select([
                DB::raw('p."IID" AS "id"'),
                DB::raw('p."VBILLING_STATEMENT" AS "bs_no"'),
                DB::raw('p."VUNIQUE_CODE" AS "unique_code"'),
                DB::raw('p."VINVOICE_NUMBER" AS "inv_no"'),
                DB::raw('p."DINVOICE_DATE" AS "inv_date"'),
                DB::raw('p."VTAX_INVOICE_NUMBER" AS "tax_inv_no"'),
                DB::raw('p."DTAX_INVOICE_DATE" AS "tax_inv_date"'),
                DB::raw('fm."VSUPPLIER_CODE" AS "supplier_code"'),
                DB::raw('fm."VNAME" AS "supplier_name"'),
                DB::raw('fm."VNPWP" AS "npwp"'),
                DB::raw('fm."VNIK" AS "nik"'),

                // 🔥 CAST NUMERIC (PENTING)
                DB::raw('CAST(p."INET_AMOUNT" AS NUMERIC) AS "sub_total"'),
                DB::raw('CAST(p."IDPP" AS NUMERIC) AS "dpp_nilai_lain"'),
                DB::raw('CAST(p."IPPN" AS NUMERIC) AS "tarif_ppn"'),
                DB::raw('CAST(p."IDPP_PPH" AS NUMERIC) AS "dpp_pph"'),

                DB::raw('p."VPPH" AS "pph_pasal"'),
                DB::raw('p."VOBJECT" AS "nama_objek_pajak"'),
                DB::raw('CAST(p."FTARRIF" AS NUMERIC) AS "tarif_pph"'),
                DB::raw('CAST(p."FVALUE" AS NUMERIC) AS "nilai_pph"'),
                DB::raw('CAST(p."ITOTAL" AS NUMERIC) AS "grand_total"'),
                DB::raw('COALESCE(p."VREQUIRE_MATERAI_OCR", \'N\') AS "require_materai_ocr"'),
                DB::raw('COALESCE(p."VOCR_MATERAI_STATUS", \'-\') AS "ocr_materai_status"'),

                DB::raw('p."VINVOICE_FILE" AS "pdf_invoice"'),
                DB::raw('p."VTAX_INVOICE_FILE" AS "pdf_tax_invoice"'),
                DB::raw("'PO' AS \"transaction_category\""),
                DB::raw('p."VSTATUS_INVOICE" AS "status_invoice"'),
                DB::raw('p."DSUBMITTED" AS "date_submitted"'),
                DB::raw('p."DAPPROVED" AS "date_approved"'),
                DB::raw('p."VPYHSICAL_DOC_STATUS" AS "doc_status"'),
                DB::raw('p."VSTATUS" AS "VSTATUS"'),
                DB::raw('p."DMODI" AS "DMODI"'),
            ]);

        return $query;
    }


    public function getNonPOQuery()
    {
        return DB::table('FACTWM_TRHVERIFY_NON_PO as p')
            ->leftJoin('FACTWM_MSHSUPPLIERS as fm', 'fm.VSUPPLIER_CODE', '=', 'p.VSUPPLIER_CODE')
            ->select([
                DB::raw('p."IID" AS "id"'),
                DB::raw('p."VBILLING_STATEMENT" AS "bs_no"'),
                DB::raw('p."VUNIQUE_CODE" AS "unique_code"'),
                DB::raw('p."VINV_NO_SUPPLIER" AS "inv_no"'),
                DB::raw('p."DINV_DATE" AS "inv_date"'),
                DB::raw('p."VTAX_NUMBER" AS "tax_inv_no"'),
                DB::raw('p."DTAX_DATE" AS "tax_inv_date"'),
                DB::raw('p."VSUPPLIER_CODE" AS "supplier_code"'),
                DB::raw('fm."VNAME" AS "supplier_name"'),
                DB::raw('fm."VNPWP" AS "npwp"'),
                DB::raw('fm."VNIK" AS "nik"'),

                // ✅ NUMERIC
                DB::raw('CAST(p."INET_AMOUNT" AS NUMERIC) AS "sub_total"'),
                DB::raw('CAST(p."VDPP" AS NUMERIC) AS "dpp_nilai_lain"'),
                DB::raw('CAST(p."VPPN" AS NUMERIC) AS "tarif_ppn"'),
                DB::raw('CAST(p."IDPP_PPH" AS NUMERIC) AS "dpp_pph"'),

                // 🔥 INI YANG KURANG
                DB::raw('p."VPPH" AS "pph_pasal"'),
                DB::raw('p."VOBJECT" AS "nama_objek_pajak"'),
                DB::raw('CAST(p."FTARRIF" AS NUMERIC) AS "tarif_pph"'),
                DB::raw('CAST(p."FVALUE" AS NUMERIC) AS "nilai_pph"'),
                DB::raw('CAST(p."ITOTAL" AS NUMERIC) AS "grand_total"'),
                DB::raw('\'N\' AS "require_materai_ocr"'),
                DB::raw('\'-\' AS "ocr_materai_status"'),

                DB::raw('p."VPDF_INVOICE" AS "pdf_invoice"'),
                DB::raw('p."VPDF_TAX" AS "pdf_tax_invoice"'),
                DB::raw("'NON PO' AS \"transaction_category\""),
                DB::raw('p."VSTATUS_INVOICE" AS "status_invoice"'),
                DB::raw('p."DSUBMITTED" AS "date_submitted"'),
                DB::raw('p."DAPPROVED" AS "date_approved"'),
                DB::raw('p."VPYHSICAL_DOC_STATUS" AS "doc_status"'),
                DB::raw('p."VSTATUS" AS "VSTATUS"'),
                DB::raw('p."DMODI" AS "DMODI"'),
            ]);
    }




    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('factwmf011-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'processing' => false,
                'orderCellsTop' => true,
                'dom' => 'r' .
                    "<'table-responsive border-top'tr>" .
                    "<'d-flex align-items-center justify-content-center justify-content-lg-between flex-wrap gap-2 text-center px-6 mt-6'ip>",
                'initComplete' => '
                    function () {
                        var table = this.api();
                        ' . $this->getScriptForSearchRowCustom(false) . '
                        ' . $this->expandRowScript() . '
                    }
                ',
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            // CHECKBOX
            Column::computed('expand')
                ->title('')
                ->orderable(false)
                ->searchable(false)
                ->width(30)
                ->addClass('text-center'),

            // ACTION
            Column::computed('preview-pdf')
                ->title('PREVIEW PDF')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center'),

            Column::computed('action')
                ->title('ACTION')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center'),

            Column::make('status_invoice')->title('STATUS INVOICE')->addClass('text-nowrap'),
            Column::make('date_approved')->title('DATE APPROVED')->addClass('text-nowrap'),
            Column::make('doc_status')->title('PHYSICAL DOC STATUS')->addClass('text-nowrap'),
            Column::make('transaction_category')->title('TRANSACTION CATEGORY')->addClass('text-nowrap'),
            Column::make('bs_no')->title('BS NO')->addClass('text-nowrap'),
            Column::make('unique_code')->title('UNIQUE NO')->addClass('text-nowrap'),
            Column::make('inv_no')->title('INV NO')->addClass('text-nowrap'),
            Column::make('tax_inv_no')->title('TAX INV NO')->addClass('text-nowrap'),
            Column::make('date_submitted')->title('DATE SUBMIT')->addClass('text-nowrap'),
            // Column::make('plan_paydate')->title('PLAN PAYDATE')->addClass('text-nowrap'),
            Column::make('inv_date')->title('INV DATE')->addClass('text-nowrap'),
            // Column::make('aging_ap')->title('AGING AP')->addClass('text-nowrap text-end'),
            Column::make('tax_inv_date')->title('TAX INV DATE')->addClass('text-nowrap'),
            Column::make('supplier_code')->title('VENDOR CODE')->addClass('text-nowrap'),
            Column::make('supplier_name')->title('VENDOR NAME')->addClass('text-nowrap'),
            Column::make('npwp')->title('NPWP')->addClass('text-nowrap'),
            Column::make('nik')->title('NIK')->addClass('text-nowrap'),

            Column::make('sub_total')->title('SUB TOTAL')->addClass('text-nowrap text-end'),
            Column::make('dpp_nilai_lain')->title('DPP NILAI LAIN')->addClass('text-nowrap text-end'),
            Column::make('tarif_ppn')->title('TARIF PPN')->addClass('text-nowrap text-end'),
            Column::make('amount_before_pph')->title('AMOUNT BEFORE PPH')->addClass('text-nowrap text-end'),
            Column::make('pph_pasal')->title('PPH PASAL')->addClass('text-nowrap'),
            Column::make('nama_objek_pajak')->title('NAMA OBJEK PAJAK')->addClass('text-nowrap'),
            Column::make('dpp_pph')->title('DPP PPH')->addClass('text-nowrap text-end'),
            Column::make('tarif_pph')->title('TARIF PPH')->addClass('text-nowrap text-end'),
            Column::make('nilai_pph')->title('NILAI PPH')->addClass('text-nowrap text-end'),
            Column::make('grand_total')->title('GRAND TOTAL')->addClass('text-nowrap text-end'),
            Column::make('require_materai_ocr')->title('REQUIRE MATERAI OCR')->addClass('text-nowrap text-center'),
            Column::make('ocr_materai_status')->title('OCR MATERAI STATUS')->addClass('text-nowrap text-center'),

            Column::make('pdf_invoice')
                ->title('PDF INVOICE')
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center'),

            Column::make('pdf_tax_invoice')
                ->title('PDF TAX INVOICE')
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-center'),
        ];
    }

    private function colorBadge($status)
    {
        $color = '';
        switch ($status) {
            case 'PAID':
                $color = 'success';
                break;

            case 'PRELIMENARY':
                $color = 'bg-blue';
                break;

            case 'FAILED':
                $color = 'danger';
                break;

            case 'REJECTED':
                $color = 'dark';
                break;

            case 'WAITING':
                $color = 'info';
                break;

            case 'ESCALATED':
                $color = 'secondary';
                break;

            default:
                $color = 'warning';
                break;
        }

        return $color;
    }

    private function getPreviewPDFButton($data): string
    {
        $base = url('FACTWM/ts');

        $url = 'verify-po';
        if ($data->transaction_category == 'NON PO') {
            $url = 'verify-non-po';
        }
        $button = '<a
                href="' . $base . '/' . $url . '/preview-pdf/' . $data->id . '"
                target="_blank"
                class="btn btn-sm btn-outline-primary"
                title="Preview PDF"
            >
                <i class="ti tabler-file-type-pdf"></i>
            </a>';

        return $button;
    }

    private function getActionButton($data): string
    {
        if ($data->transaction_category == 'PO') {
            $payload = $this->mapDataPayloadPO($data->id);
        } else {
            $payload = $this->mapDataPayloadNonPO($data->id);
        }
        $payloadJson = $this->encodePayloadForHtmlAttribute($payload);
        $button = '';
        $isSupplier = Auth::check()
            && Auth::user()->supplierUser
            && Auth::user()->supplierUser->VSUPPLIER_CODE;
        if (!$isSupplier) {
            if ($data->status_invoice == 'FAILED') {
                $button = '<div class="d-flex gap-2">
                    <!-- Resend SI -->
                    <button
                        class="btn bg-secondary btn-icon rounded-circle text-white resend-si"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="Resend SI"
                        data-id="' . $data->id . '"
                        data-category="' . $data->transaction_category . '"
                        data-payload="' . $payloadJson . '"
                    >
                        <i class="ti tabler-reload" style="font-size:16px;"></i>
                    </button>

                </div>';
            } else {
                $button = '<div class="d-flex gap-2">
                    <!-- Reject -->
                    <button
                        class="btn btn-warning btn-icon rounded-circle text-white reject-invoice"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="Reject"
                        data-id="' . $data->id . '"
                        data-category="' . $data->transaction_category . '"
                        data-payload="' . $payloadJson . '"
                    >
                        <i class="icon-base ti tabler-reload"></i>
                    </button>

                    <!-- Approve -->
                    <button
                        class="btn btn-success btn-icon rounded-circle text-white approve-invoice"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        title="Approve"
                        data-id="' . $data->id . '"
                        data-category="' . $data->transaction_category . '"
                        data-payload="' . $payloadJson . '"
                    >
                        <i class="icon-base ti tabler-check"></i>
                    </button>

                </div>';
            }
        } else {
            $button = '-';
        }

        return $button;
    }

    private function expandRowScript(): string
    {
        return <<<JS
        function renderDetail(details) {
            let html = `
            <div class="p-3 bg-light">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>Part Number</th>
                            <th>Description</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Price</th>
                            <th>Curr</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">PPN</th>
                            <th class="text-end">Aging AP</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            if (Array.isArray(details) && details.length > 0) {
                details.forEach(item => {
                    html += `
                        <tr>
                            <td>\${item.part_number}</td>
                            <td>\${item.description}</td>
                            <td class="text-end">\${item.qty}</td>
                            <td class="text-end">\${Number(item.price).toLocaleString('id-ID')}</td>
                            <td>\${item.curr}</td>
                            <td class="text-end">\${Number(item.subtotal).toLocaleString('id-ID')}</td>
                            <td class="text-end">\${Number(item.ppn).toLocaleString('id-ID')}</td>
                            <td>\${item.aging_ap}</td>
                        </tr>
                    `;
                });
            } else {
                html += `
                    <tr>
                        <td colspan="7" class="text-center">No Data</td>
                    </tr>
                `;
            }

            html += `</tbody></table></div>`;
            return html;
        }

        $(document).on('click', '.dt-expand', function () {
            let tr = $(this).closest('tr');
            let table = $('#factwmf011-table').DataTable();
            let row = table.row(tr);
            let icon = $(this).find('i');
            let rowData = row.data();

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                icon.removeClass('tabler-minus').addClass('tabler-plus');
                return;
            }

            // loading state
            row.child('<div class="p-3 text-center">Loading...</div>').show();
            tr.addClass('shown');
            icon.removeClass('tabler-plus').addClass('tabler-minus');

            // kalau sudah pernah load → pakai cache
            if (rowData._details) {
                row.child(renderDetail(rowData._details)).show();
                return;
            }

            $.ajax({
                url: `/FACTWM/rt/invoices/detail/\${rowData.id}`,
                type: 'GET',
                dataType: 'json',
                data: {
                    transaction_category: rowData.transaction_category
                },
                success: function (res) {
                    rowData._details = res.data ?? [];
                    row.data(rowData); // simpan ke row DataTable
                    row.child(renderDetail(rowData._details)).show();
                },
                error: function () {
                    row.child('<div class="p-3 text-danger text-center">Failed to load data</div>').show();
                }
            });
        });
        JS;
    }


    private function setCurrency($data)
    {
        return number_format($data, 0, ',', '.');
    }

    private function getScriptForSearchRowCustom($skipLastColumn = true)
    {
        $script = '
        var thead = $(table.table().header());
        var headerCells = thead.find("tr:first th");

        // Create search row
        var searchRow = $("<tr></tr>");

        headerCells.each(function(index) {
            var column = table.column(index);
            var title = $(this).text().trim();
            var th = $("<th></th>");

            // Get total columns
            var totalColumns = headerCells.length;
            var isFirstColumn = index === 0;
            var isSecondColumn = index === 1;
            var isThirdColumn = index === 2;
            var isLastColumn = index === totalColumns - 1;

            // Skip checkbox (index 0), Picture (index 1), and Action (last index)
            if (isFirstColumn || isSecondColumn  || isThirdColumn || (isLastColumn && ' . ($skipLastColumn ? 'true' : 'false') . ')) {
                searchRow.append(th);
                return true; // continue
            }

            else if (title.toLowerCase() === "pdf invoice" || title.toLowerCase() === "pdf tax invoice") {
                searchRow.append(th);
                return true; // continue
            }

            // Special handling for Status Invoice column - dropdown
            else if (title.toLowerCase() === "status invoice") {
                var select = $("<select>", {
                    "class": "form-select form-select-sm",
                });

                select.append($("<option>", {
                    value: "",
                    text: "select"
                }));

                var userTypes = ["PAID", "PRELIMENARY", "FAILED", "REJECTED", "PROCESSING", "ESCALATED", "WAITING"];
                userTypes.forEach(function(type) {
                    select.append($("<option>", {
                        value: type.toLowerCase(),
                        text: type
                    }));
                });

                th.html(select);

                select.on("change", function() {
                    let val = $(this).val();

                    if (val) {
                        // exact match
                        column
                            .search("^" + val + "$", true, false) // regex=true, smart=false
                            .draw();
                    } else {
                        column.search("").draw();
                    }
                });
            }

            else if (title.toLowerCase() === "transaction category") {
                var select = $("<select>", {
                    "class": "form-select form-select-sm",
                });

                select.append($("<option>", {
                    value: "",
                    text: "select"
                }));

                var userTypes = ["PO", "Non PO"];
                userTypes.forEach(function(type) {
                    select.append($("<option>", {
                        value: type.toLowerCase(),
                        text: type
                    }));
                });

                th.html(select);

                select.on("change", function() {
                    let val = $(this).val();

                    if (val) {
                        // exact match
                        column
                            .search("^" + val + "$", true, false) // regex=true, smart=false
                            .draw();
                    } else {
                        column.search("").draw();
                    }
                });
            }
            // Date columns
            else if (/date/i.test(title)) {
                var input = $("<input>", {
                    class: "form-control form-control-sm date-input",
                    placeholder: "YYYY-MM-DD",
                    readonly: true
                });

                th.html(input);

                var isSingleDate = title.toLowerCase() !== "inv date";

                input.daterangepicker({
                    singleDatePicker: isSingleDate,
                    autoUpdateInput: false,
                    locale: {
                        cancelLabel: "Clear",
                        format: "YYYY-MM-DD"
                    }
                });

                input.on("apply.daterangepicker", function (ev, picker) {
                    if (isSingleDate) {
                        // Single date picker
                        var selectedDate = picker.startDate.format("YYYY-MM-DD");
                        column.search(selectedDate).draw();
                        $(this).val(selectedDate);
                    } else {
                        // Range date picker
                        var startDate = picker.startDate.format("YYYY-MM-DD");
                        var endDate = picker.endDate.format("YYYY-MM-DD");
                        var selectedDate = startDate + "," + endDate;
                        column.search(selectedDate).draw();
                        $(this).val(selectedDate);
                    }
                });

                input.on("cancel.daterangepicker", function () {
                    column.search("").draw();
                    $(this).val("");
                });
            }

            // Default text input for other columns
            else {
                var input = $("<input>", {
                    "class": "form-control form-control-sm",
                    "placeholder": "Search..."
                });

                th.html(input);

                input.on("keyup", function() {
                    column.search(input.val()).draw();
                });
            }

            searchRow.append(th);
        });

        // Append search row to thead
        thead.append(searchRow);
        ';

        return $script;
    }

    private function mapDataPayload($verifyPo)
    {
        $supplier = Supplier::where('VSUPPLIER_CODE', $verifyPo->VSUPPLIER_CODE)->first();
        $config_ppn = Config::where('VVARIABLE', 'ppn')->first();
        $config_rumus_dpp = Config::where('VVARIABLE', 'rumus_dpp')->first();
        if (!$supplier) {
            throw new Exception('Data supplier not found');
        }

        $rumus = $config_rumus_dpp->VVALUE ?? "11/12";
        [$num, $den] = array_map('floatval', explode('/', $rumus));

        $rumus_dpp = ($den == 0) ? 0 : ($num / $den);
        $ppn = $config_ppn->VVALUE ?? 12;

        $taxCode = $supplier->BPKP ? 'V11' : 'V0';

        $grNotes = GRNote::whereIn('IID', $verifyPo->VGR_NUMBER_IID)->get();
        $payload = $grNotes->map(function ($gr) use ($verifyPo, $taxCode, $ppn, $rumus_dpp) {
            $sign = $gr->VREF_TYPE === 'RETURN' ? -1 : 1;
            $NetAmount = $gr->details->sum('VAMOUNT');
            $TaxAmount = round((($rumus_dpp) * floatval($NetAmount ?? 0)) * ($ppn / 100), 2);
            $grossAmount = $NetAmount + $TaxAmount;
            return [
                "Billing_Stat_No" => $verifyPo->VBILLING_STATEMENT,
                "Supplier"        => $verifyPo->VSUPPLIER_CODE,
                "Currency"        => $gr->details[0]->VCURRENCY ?? null,
                "Order_No"        => $gr->VPO_NUMBER ?? null,
                "Reference_No"    => $gr->VGR_NUMBER ?? null,
                "Invoice_No"      => $verifyPo->VINVOICE_NUMBER,
                "Invoice_Date"    => $verifyPo->DINVOICE_DATE->format('Y-m-d') ?? null,
                "TaxCode"         => $taxCode,
                'NetAmount' => (string) (($NetAmount ?? 0) * $sign),
                'TaxAmount' => (string) (($TaxAmount ?? 0) * $sign),
                'GrossAmount' => (string) (($grossAmount ?? 0) * $sign),
            ];
        })->values()->toArray();

        return $payload;
    }

    private function mapDataPayloadPO($id)
    {
        $verifyPo = VerifyPo::where('IID', $id)->first();
        $supplier = Supplier::where('VSUPPLIER_CODE', $verifyPo->VSUPPLIER_CODE)->first();
        $config_ppn = Config::where('VVARIABLE', 'ppn')->first();
        $config_rumus_dpp = Config::where('VVARIABLE', 'rumus_dpp')->first();
        if (!$supplier) {
            throw new Exception('Data supplier not found');
        }

        $rumus = $config_rumus_dpp->VVALUE ?? "11/12";
        [$num, $den] = array_map('floatval', explode('/', $rumus));

        $rumus_dpp = ($den == 0) ? 0 : ($num / $den);
        $ppn = $config_ppn->VVALUE ?? 12;

        $taxCode = $supplier->BPKP ? 'V11' : 'V0';

        $grNotes = GRNote::whereIn('IID', $verifyPo->VGR_NUMBER_IID)->get();
        $payload = $grNotes->map(function ($gr) use ($verifyPo, $taxCode, $ppn, $rumus_dpp) {
            $sign = $gr->VREF_TYPE === 'RETURN' ? -1 : 1;
            $NetAmount = $gr->details->sum('VAMOUNT');
            $TaxAmount = round((($rumus_dpp) * floatval($NetAmount ?? 0)) * ($ppn / 100), 2);
            $grossAmount = $NetAmount + $TaxAmount;
            return [
                "Billing_Stat_No" => $verifyPo->VBILLING_STATEMENT,
                "Supplier"        => $verifyPo->VSUPPLIER_CODE,
                "Currency"        => $gr->details[0]->VCURRENCY ?? null,
                "Order_No"        => $gr->VPO_NUMBER ?? null,
                "Reference_No"    => $gr->VGR_NUMBER ?? null,
                "Invoice_No"      => $verifyPo->VINVOICE_NUMBER,
                "Invoice_Date"    => $verifyPo->DINVOICE_DATE->format('Y-m-d') ?? null,
                "TaxCode"         => $taxCode,
                'NetAmount' => (string) (($NetAmount ?? 0) * $sign),
                'TaxAmount' => (string) (($TaxAmount ?? 0) * $sign),
                'GrossAmount' => (string) (($grossAmount ?? 0) * $sign),
            ];
        })->values()->toArray();

        return $payload;
    }

    private function mapDataPayloadNonPO($id)
    {
        $verifyPo = VerifyNonPo::where('IID', $id)->first();

        $payload = [[
            "Billing_Stat_No" => $verifyPo->VBILLING_STATEMENT,
            "Supplier"     => $verifyPo->VSUPPLIER_CODE,
            "Currency"        => "IDR",
            "Order_No"        => $verifyPo->VUNIQUE_CODE,
            "Reference_No"    => null,
            "Invoice_No"      => $verifyPo->VINV_NO_SUPPLIER,
            "Invoice_Date"    => $verifyPo->DINV_DATE->format('Y-m-d') ?? null,
            "TaxCode"         => $verifyPo->VTAX_CODE,
            "NetAmount"       => (string) $verifyPo->INET_AMOUNT ?? 0,
            "TaxAmount"       => (string) $verifyPo->VPPN ?? 0,
            "GrossAmount"     => (string) $verifyPo->ITOTAL ?? 0
        ]];

        return $payload;
    }

    private function encodePayloadForHtmlAttribute(array $payload): string
    {
        $json = json_encode(
            $payload,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        if (!is_string($json)) {
            throw new Exception('Failed to encode payload to JSON');
        }

        return htmlspecialchars($json, ENT_QUOTES, 'UTF-8');
    }
}
