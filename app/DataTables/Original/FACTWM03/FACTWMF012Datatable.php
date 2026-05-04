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
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES as GRNote;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_PO as VerifyPo;
use Yajra\DataTables\QueryDataTable;
use Illuminate\Support\Facades\Auth;

final class FACTWMF012Datatable extends DataTable
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
            ->editColumn('action', function ($row) {
                $button = '-';

                if (($row->status_invoice == 'PROCESSING' || $row->status_invoice == 'ESCALATED')) {
                    $button = $this->getActionButton($row);
                }

                return $button;
            })
            ->editColumn('status_grn', function ($row) {
                $status_grn = '-';
                if ($row->transaction_category == 'PO') {
                    // $po = VerifyPo::find($row->id);
                    // if ($po) {
                    // $gr = GRNote::where('VGR_NUMBER', $po->VGRN_NUMBER)->first();
                    // $status_grn = $gr->VSTATUS;
                    // }
                    $status_grn = $row->status_grn;
                }

                return $status_grn;
            })
            ->editColumn('pdf_invoice', function ($row) {
                $button =  '-';
                $url = 'verify-po';
                if ($row->transaction_category == 'NON PO') {
                    $url = 'verify-non-po';
                }

                if (!empty($row->pdf_invoice)) {

                    $fileUrl = route($url . '.download', [$row->po_id, 'invoice']);

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

                    $fileUrl = route($url . '.download', [$row->po_id, 'tax']);

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
            // ->editColumn('grn_date', function ($row) {
            //     return Carbon::parse($row->grn_date)->format('Y-m-d H:i');
            // })
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

            ->addColumn('aging_grn', function ($row) {
                if (!$row->grn_date) return 0;
                return round(Carbon::now()->diffInDays(Carbon::parse($row->grn_date)), 0);
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
            // ->editColumn('BISACCEPTPRIVACY', function ($row) {
            //     return $row['BISACCEPTPRIVACY'] ? 'Accepted' : 'Not Accepted';
            // })
            ->rawColumns(['checkbox', 'status_invoice', 'pdf_invoice', 'pdf_tax_invoice', 'action', 'expand'])
            ->setRowId('IID')
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
            ->where('VSTATUS', 'submit')
            ->orderBy('DMODI', 'desc'); // ✅ Tambahkan ini
    }

    public function getPOQuery()
    {
        return DB::table('FACTWM_TRHVERIFY_PO as p')
            ->leftJoin('FACTWM_MSHSUPPLIERS as fm', 'fm.VSUPPLIER_CODE', '=', 'p.VSUPPLIER_CODE')
            ->leftJoin('FACTWM_TRHGR_NOTES as gr', function ($join) {
                $join->whereRaw(
                    'p."VGR_NUMBER_IID" IS NOT NULL
                    AND json_array_length(p."VGR_NUMBER_IID") > 0
                    AND gr."IID" = ANY (
                        array_remove(
                            regexp_split_to_array(
                                regexp_replace(p."VGR_NUMBER_IID"::text, \'[\\[\\]\\s]\', \'\', \'g\'),
                                \',\'
                            ),
                            \'\'
                        )::int[]
                    )'
                );
            })
            ->select([
                DB::raw('gr."IID" AS "id"'),
                DB::raw('p."IID" AS "po_id"'),
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

                DB::raw('p."VINVOICE_FILE" AS "pdf_invoice"'),
                DB::raw('p."VTAX_INVOICE_FILE" AS "pdf_tax_invoice"'),
                DB::raw("'PO' AS \"transaction_category\""),
                DB::raw('p."VSTATUS_INVOICE" AS "status_invoice"'),
                DB::raw('p."DSUBMITTED" AS "date_submitted"'),
                DB::raw('p."DAPPROVED" AS "date_approved"'),
                DB::raw('p."VPYHSICAL_DOC_STATUS" AS "doc_status"'),
                DB::raw('gr."VSTATUS" AS "status_grn"'),
                // DB::raw('gr."IID" AS "grn_id"'),
                DB::raw('gr."VGR_NUMBER" AS "grn_no"'),
                DB::raw('gr."VDELIVERY_NUMBER" AS "delivery_no"'),
                DB::raw('gr."VPO_NUMBER" AS "po_number"'),
                DB::raw('gr."DGR" AS "grn_date"'),
                DB::raw('NULL AS "aging_grn"'),
                DB::raw('NULL::timestamp AS "date_physical_submit"'),
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
                DB::raw('p."IID" AS "po_id"'),
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

                DB::raw('p."VPDF_INVOICE" AS "pdf_invoice"'),
                DB::raw('p."VPDF_TAX" AS "pdf_tax_invoice"'),
                DB::raw("'NON PO' AS \"transaction_category\""),
                DB::raw('p."VSTATUS_INVOICE" AS "status_invoice"'),
                DB::raw('p."DSUBMITTED" AS "date_submitted"'),
                DB::raw('p."DAPPROVED" AS "date_approved"'),
                DB::raw('p."VPYHSICAL_DOC_STATUS" AS "doc_status"'),
                DB::raw('NULL AS "status_grn"'),
                // DB::raw('NULL AS "grn_id"'),
                DB::raw('NULL AS "grn_no"'),
                DB::raw('NULL AS "delivery_no"'),
                DB::raw('NULL AS "po_number"'),
                DB::raw('NULL AS "aging_grn"'),
                DB::raw('NULL::timestamp AS "grn_date"'),
                DB::raw('NULL::timestamp AS "date_physical_submit"'),
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
            ->setTableId('factwmf012-table')
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
            // Column::computed('action')
            //     ->title('ACTION')
            //     ->exportable(false)
            //     ->printable(false)
            //     ->orderable(false)
            //     ->searchable(false)
            //     ->addClass('text-center'),

            Column::make('status_invoice')->title('STATUS INVOICE')->addClass('text-nowrap'),
            Column::make('status_grn')->title('STATUS GRN')->addClass('text-nowrap'),
            // Column::make('date_approved')->title('DATE APPROVED')->addClass('text-nowrap'),
            Column::make('doc_status')->title('PHYSICAL DOC STATUS')->addClass('text-nowrap'),
            Column::make('transaction_category')->title('TRANSACTION CATEGORY')->addClass('text-nowrap'),
            Column::make('bs_no')->title('BS NO')->addClass('text-nowrap'),
            Column::make('unique_code')->title('UNIQUE NO')->addClass('text-nowrap'),
            Column::make('inv_no')->title('INV NO')->addClass('text-nowrap'),
            Column::make('tax_inv_no')->title('TAX INV NO')->addClass('text-nowrap'),
            Column::make('grn_no')->title('GRN NO')->addClass('text-nowrap'),
            Column::make('delivery_no')->title('DELIVERY NO')->addClass('text-nowrap'),
            Column::make('po_number')->title('PO NUMBER')->addClass('text-nowrap'),
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
            Column::make('grn_date')->title('GRN DATE')->addClass('text-nowrap'),
            Column::make('date_submitted')->title('DATE SUBMIT TO PORTAL')->addClass('text-nowrap'),
            // Column::make('plan_paydate')->title('PLAN PAYDATE')->addClass('text-nowrap'),
            Column::make('inv_date')->title('INV DATE')->addClass('text-nowrap'),
            // Column::make('aging_ap')->title('AGING AP')->addClass('text-nowrap text-end'),
            Column::make('tax_inv_date')->title('TAX INV DATE')->addClass('text-nowrap'),
            Column::make('date_approved')->title('DATE PHYSICAL SUBMIT')->addClass('text-nowrap'),
            Column::make('aging_grn')->title('AGING GRN')->addClass('text-nowrap'),

            // Column::make('pdf_invoice')
            //     ->title('PDF INVOICE')
            //     ->orderable(false)
            //     ->searchable(false)
            //     ->addClass('text-center'),

            // Column::make('pdf_tax_invoice')
            //     ->title('PDF TAX INVOICE')
            //     ->orderable(false)
            //     ->searchable(false)
            //     ->addClass('text-center'),
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

    private function getActionButton($data): string
    {
        $button = '<div class="d-flex gap-2">
            <!-- Reject -->
            <button
                class="btn btn-warning btn-icon rounded-circle text-white reject-invoice"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                title="Reject"
                data-id="' . $data->id . '"
                data-category="' . $data->transaction_category . '"
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
            >
                <i class="icon-base ti tabler-check"></i>
            </button>

        </div>';

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
            let table = $('#factwmf012-table').DataTable();
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
                url: `/FACTWM/rt/overview/detail/\${rowData.id}`,
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
            var isLastColumn = index === totalColumns - 1;

            // Skip checkbox (index 0), Picture (index 1), and Action (last index)
            if (isFirstColumn || (isLastColumn && ' . ($skipLastColumn ? 'true' : 'false') . ')) {
                searchRow.append(th);
                return true; // continue
            }

            else if (title.toLowerCase() === "pdf invoice" || title.toLowerCase() === "pdf tax invoice") {
                searchRow.append(th);
                return true; // continue
            }

            // Special handling for Status Invoice column - dropdown

            // Special handling for Status Invoice column - dropdown
            else if (title.toLowerCase() === "status invoice") {
                var select = $("<select>", {
                    "class": "form-select form-select-sm",
                });

                select.append($("<option>", {
                    value: "",
                    text: "select"
                }));

                var userTypes = ["PAID", "PRELIMENARY", "FAILED", "CANCEL", "ESCALATED", "WAITING"];
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

                input.daterangepicker({
                    singleDatePicker: true,
                    autoUpdateInput: false,
                    locale: {
                        cancelLabel: "Clear",
                        format: "YYYY-MM-DD"
                    }
                });

                input.on("apply.daterangepicker", function (ev, picker) {
                    var selectedDate = picker.startDate.format("YYYY-MM-DD");

                    column.search(selectedDate).draw();
                    $(this).val(selectedDate);
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
}
