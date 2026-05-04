<?php

namespace App\DataTables\Original\FACTWM02;

use App\Models\FACTWM02\FACTWM_TRHGR_NOTES as GRNote;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class FACTWMF007DataTable extends DataTable
{
    use DataTableTrait;

    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder<GRNote>  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('total_amount', function ($row) {
                return number_format($row->total_amount, 2); // or currency format
            })
            ->editColumn('period_date', function ($row) {
                // Convert "2025-12" → "December 2025"
                return Carbon::createFromFormat('Y-m', $row->period_date)->format('F Y');
            })
            ->addColumn('action', function ($row) {
                return '
                    <button class="btn btn-primary" id="view" data-month="'.$row->period_date.'">
                        <i class="menu-icon ti tabler-eye"></i>
                        View
                    </button>
                ';
            })
            ->setRowId('id')
            // ->filter(function ($query) {
            //     $columns = request()->get('columns');

            //     $totalGrSearch = $columns[1]['search']['value'] ?? null;

            //     if (is_numeric($totalGrSearch)) {
            //         $query->havingRaw(
            //             'SUM(("FACTWM_TRDGR_NOTE_DETAILS"."VAMOUNT")::numeric) >= ?',
            //             [$totalGrSearch]
            //         );
            //     }
            // })
            // ->filter(function ($query) {
            //     $columns = request()->get('columns');
            //     $amount = $columns[1]['search']['value'] ?? null;

            //     if (is_numeric($amount)) {
            //         $query->havingRaw(
            //             'SUM(("FACTWM_TRDGR_NOTE_DETAILS"."VAMOUNT")::numeric) >= ?',
            //             [(float) $amount]
            //         );
            //     }
            // }, true) // <- WAJIB matikan global search
            ->filter(function ($query) {
                $columns = request()->get('columns');

                // total_gr
                $totalGr = $columns[2]['search']['value'] ?? null;
                if (is_numeric($totalGr)) {
                    $query->havingRaw(
                        'COUNT(DISTINCT "FACTWM_TRHGR_NOTES"."IID") >= ?',
                        [(int) $totalGr]
                    );
                }

                // total_approved
                $totalApproved = $columns[3]['search']['value'] ?? null;
                if (is_numeric($totalApproved)) {
                    $query->havingRaw(
                        'COUNT(DISTINCT CASE
                WHEN "FACTWM_TRHGR_NOTES"."VSTATUS_SUBMITTED" <> \'PENDING\'
                THEN "FACTWM_TRHGR_NOTES"."IID"
            END) >= ?',
                        [(int) $totalApproved]
                    );
                }

                // total_new
                $totalNew = $columns[4]['search']['value'] ?? null;
                if (is_numeric($totalNew)) {
                    $query->havingRaw(
                        'COUNT(DISTINCT CASE
                WHEN "FACTWM_TRHGR_NOTES"."VSTATUS_SUBMITTED" = \'PENDING\'
                THEN "FACTWM_TRHGR_NOTES"."IID"
            END) >= ?',
                        [(int) $totalNew]
                    );
                }
            }, true) // true = MATIKAN GLOBAL SEARCH

            // ->filterColumn('total_verified', function ($query, $keyword) {
            //     if (is_numeric($keyword)) {
            //         $query->having('total_approved', '>=', (int) $keyword);
            //     }
            // })
            // ->filterColumn('total_unverified', function ($query, $keyword) {
            //     if (is_numeric($keyword)) {
            //         $query->having('total_new', '>=', (int) $keyword);
            //     }
            // })
            // ->filterColumn('total_amount', function ($query, $keyword) {
            //     if (is_numeric($keyword)) {
            //         $query->having('total_amount', '>=', (float) $keyword);
            //     }
            // })
            ->orderColumn('total_gr', function ($query, $order) {
                $query->orderBy('total_gr', $order);
            })
            ->orderColumn('total_approved', function ($query, $order) {
                $query->orderBy('total_approved', $order);
            })
            ->orderColumn('total_new', function ($query, $order) {
                $query->orderBy('total_new', $order);
            })
            ->orderColumn('total_amount', function ($query, $order) {
                $query->orderBy('total_amount', $order);
            })
            ->filterColumn('period_date', function ($query, $keyword) {
                if (str_contains($keyword, ' to ')) {
                    [$startDate, $endDate] = array_map('trim', explode(' to ', $keyword, 2));

                    if ($startDate !== '' && $endDate !== '') {
                        $startMonth = Carbon::parse($startDate)->format('Y-m');
                        $endMonth = Carbon::parse($endDate)->format('Y-m');

                        $query->havingRaw(
                            'TO_CHAR("FACTWM_TRHGR_NOTES"."DGR", \'YYYY-MM\') BETWEEN ? AND ?',
                            [$startMonth, $endMonth]
                        );
                    }

                    return;
                }

                if (trim($keyword) !== '') {
                    $query->havingRaw(
                        'TO_CHAR("FACTWM_TRHGR_NOTES"."DGR", \'YYYY-MM\') = ?',
                        [Carbon::parse(trim($keyword))->format('Y-m')]
                    );
                }
            })
            ->orderColumn('period_date', function ($query, $order) {
                $query->orderBy('period_date', $order);
            });
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<GRNote>
     */
    public function query(GRNote $model): QueryBuilder
    {
        if (! request()->filled('month')) {
            // Keep the same sortable aliases even when returning no rows.
            return $model->newQuery()
                ->selectRaw("
                    NULL::text as period_date,
                    0 as total_gr,
                    0 as total_approved,
                    0 as total_new,
                    0 as total_details,
                    0::numeric as total_amount
                ")
                ->whereRaw('1 = 0');
        }

        $monthRange = request('month');

        [$start, $end] = explode(' to ', $monthRange);

        $startDate = Carbon::createFromFormat('Y-m', $start)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $end)->endOfMonth();

        return $model->newQuery()
            ->from(DB::raw('"FACTWM_TRHGR_NOTES"'))
            ->leftJoin(
                DB::raw('"FACTWM_TRDGR_NOTE_DETAILS"'),
                DB::raw('"FACTWM_TRHGR_NOTES"."IID"'),
                '=',
                DB::raw('"FACTWM_TRDGR_NOTE_DETAILS"."IID_GR_NOTE"')
            )
            ->select(
                DB::raw('TO_CHAR("FACTWM_TRHGR_NOTES"."DGR", \'YYYY-MM\') as period_date'),

                DB::raw('COUNT(DISTINCT "FACTWM_TRHGR_NOTES"."IID") as total_gr'),

                DB::raw('COUNT(DISTINCT CASE
            WHEN "FACTWM_TRHGR_NOTES"."VSTATUS_SUBMITTED" NOT IN (\'PENDING\')
            THEN "FACTWM_TRHGR_NOTES"."IID"
        END) as total_approved'),

                DB::raw('COUNT(DISTINCT CASE
            WHEN "FACTWM_TRHGR_NOTES"."VSTATUS_SUBMITTED" IN (\'PENDING\')
            THEN "FACTWM_TRHGR_NOTES"."IID"
        END) as total_new'),

                DB::raw('COUNT("FACTWM_TRDGR_NOTE_DETAILS"."IID") as total_details'),
                DB::raw('SUM(("FACTWM_TRDGR_NOTE_DETAILS"."VAMOUNT")::numeric) as total_amount')
            )
            ->whereBetween(
                DB::raw('"FACTWM_TRHGR_NOTES"."DGR"'),
                [$startDate, $endDate]
            )
            ->where(
                DB::raw('"FACTWM_TRHGR_NOTES"."VVENDOR_CODE"'),
                Auth::user()->supplierUser->VSUPPLIER_CODE
            )
            ->whereIn(
                DB::raw('"FACTWM_TRHGR_NOTES"."VSTATUS"'),
                ['APPROVED', 'CLOSED']
            )
            ->whereNull(DB::raw('"FACTWM_TRHGR_NOTES"."DDELETE"'))
            ->groupByRaw('TO_CHAR("FACTWM_TRHGR_NOTES"."DGR", \'YYYY-MM\')');
        // ->orderByRaw('TO_CHAR("FACTWM_TRHGR_NOTES"."DGR", \'YYYY-MM\') asc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('factwmf007-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'processing' => false,
                'pageLength' => 10,
                'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                'orderCellsTop' => true,
                'columnDefs' => [
                    [
                        'className' => 'text-center text-nowrap',
                        'targets' => '_all',
                    ],
                ],
                'buttons' => [
                    [
                        'extend' => 'excel',
                        'className' => 'd-none',
                        'filename' => $this->filename(),
                        'exportOptions' => [
                            'columns' => ':visible:not(:first-child):not(:last-child)',
                        ],
                    ],
                ],
                'initComplete' => '
                    function () {

                        '.$this->seachRow().'

                    }
                ',
                'dom' => 'r'.
                    "<'table-responsive border-top'tr>".
                    "<'d-flex align-items-center justify-content-center justify-content-lg-between flex-wrap gap-2 text-center px-6 mt-6'ip>",
                'drawCallback' => '
                    function() {
                        $("#select-all").off("click").on("click", function(){
                            var checked = this.checked;
                            $("input[name=\'selected[]\']").prop("checked", checked).trigger("change");
                        });

                        $("input[name=\'selected[]\']").off("change").on("change", function(){
                            $("input[name=\'selected[]\']").not(this).prop("checked", false);
                            var anyChecked = $("input[name=\'selected[]\']:checked").length > 0;
                            $("#btn-delete-selected").toggleClass("d-none", !anyChecked);

                            var allChecked = $("input[name=\'selected[]\']").length === $("input[name=\'selected[]\']:checked").length;
                            $("#select-all").prop("checked", allChecked);
                        });
                    }
                ',
                'order' => [[0, 'desc']],
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('period_date')->title('Period Date'),
            // Column::make('currency')->title('Currency'),
            Column::make('total_amount')->title('Amount')->searchable(false)->orderable(true),
            Column::make('total_gr')->title('Total Transaksi')->searchable(false)->orderable(true),
            Column::make('total_approved')->title('Total Verified')->searchable(false)->orderable(true),
            Column::make('total_new')->title('Total Unverified')->searchable(false)->orderable(true),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->searchable(false)
                ->width(60)
                ->addClass('text-center'),
        ];
    }

    private function seachRow()
    {
        return 'var table = this.api();
        var headerCells = $(table.table().header()).find("th");

        // Create a new row for search inputs in thead
        var searchRow = $("<tr></tr>");

        headerCells.each(function(index) {
            var column = table.column(index);
            var title = $(this).text();
            var th = $("<th></th>");
            var totalColumns = headerCells.length;
            var isFirstColumn = index === 0;
            var isSecondColumn = index === 1;
            var isLastColumn = index === totalColumns - 1;

            if(isFirstColumn || isSecondColumn || isLastColumn) {
                searchRow.append(th);
                return true;
            }

            // Check if the header text contains "Date" or "Last Login" (case-insensitive)
            if (/date/i.test(title)) {
                var input = $("<input>", {
                    "class": "form-control daterange-input",
                    "placeholder": "Select date",
                    "type": "text"
                });

                th.html(input);

                // Initialize Daterangepicker
                input.daterangepicker({
                    autoUpdateInput: false,
                    locale: {
                        autoApply: true,
                        cancelLabel: "Clear",
                        format: "YYYY-MM-DD"
                    }
                });

                // Event when a date range is applied
                input.on("apply.daterangepicker", function(ev, picker) {
                    var startDate = picker.startDate.format("YYYY-MM-DD");
                    var endDate = picker.endDate.format("YYYY-MM-DD");
                    column.search(startDate + " to " + endDate).draw();
                    $(this).val(startDate + " - " + endDate);
                });

                // Event for clearing the date picker
                input.on("cancel.daterangepicker", function(ev, picker) {
                    column.search("").draw();
                    $(this).val("");
                });
            } else {
                var input = $("<input>", {
                    "class": "form-control form-control-sm",
                    "placeholder": "Search " + title,
                    "type": "text"
                });

                th.html(input);

                let searchTimeout;

                input.on("keyup change", function () {
                    const value = this.value;

                    clearTimeout(searchTimeout);

                    searchTimeout = setTimeout(function () {
                        if (column.search() !== value) {
                            column.search(value).draw();
                        }
                    }, 500); // jeda 500 ms
                });
            }

            searchRow.append(th);
        });

        // Append search row to thead
        $(table.table().header()).append(searchRow);';
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'FACTWMF007_'.date('YmdHis');
    }
}
