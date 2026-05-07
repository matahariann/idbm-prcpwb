<?php

namespace App\DataTables\Original\PRCPWB02;

use App\Models\PRCPWB02\PRCPWB_TRHDAILY_REQUEST as DailyRequest;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

// Daily Request
class PRCPWBF005DataTable extends DataTable
{
    use DataTableTrait;

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<DailyRequest> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('BAL', function ($data) {
                return $data->BAL ?? 0;
            })
            ->editColumn('DWANTEDRECEIPTDATE', function ($data) {
                return Carbon::parse($data->DWANTEDRECEIPTDATE)->format('d M Y H:i');
            })
            ->editColumn('VTIME', function ($data) {
                return Carbon::parse($data->VTIME)->format('H:i');
            })
            ->editColumn('DCREA', function ($data) {
                return Carbon::parse($data->DCREA)->format('d M Y H:i');
            })
            ->editColumn('DMODI', function ($data) {
                if ($data->VSTATUS->value === 'Received') {
                    return $data->DMODI ? Carbon::parse($data->DMODI)->format('d/m/Y H:i:s') : '-';
                }
                return '';
            })
            ->setRowId('IID')

            ->filterColumn('DWANTEDRECEIPTDATE', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DWANTEDRECEIPTDATE', $keyword);
            })
            ->filterColumn('VTIME', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'VTIME', $keyword);
            })
            ->filterColumn('DCREA', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DCREA', $keyword);
            })
            ->filterColumn('DMODI', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DMODI', $keyword);
            })
            ->filter(function ($query) {
                $keyword = request('keyword');

                if (! empty($keyword)) {
                    $query->whereAny(['PRCPWB_TRHDAILYREQUESTS.VVENDORNO', 'VVENDORNAME', 'PRCPWB_TRHDAILYREQUESTS.VPARTNO', 'PRCPWB_TRHDAILYREQUESTS.VPARTDESCRIPTION'], 'ILIKE', "%{$keyword}%");
                }
            });
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<DailyRequest>
     */
    public function query(DailyRequest $model): QueryBuilder
{
    return $model->newQuery()
        ->leftJoin('PRCPWB_MSHVENDORS', 'PRCPWB_TRHDAILYREQUESTS.VVENDORNO', '=', 'PRCPWB_MSHVENDORS.VVENDORNO')
        ->select([
            'PRCPWB_TRHDAILYREQUESTS.*',
            'PRCPWB_MSHVENDORS.VVENDORNAME as VVENDORNAME',
        ])

        ->selectSub(function ($query) {
            $query->selectRaw('COALESCE(SUM(y."IQUANTITY"), 0)')
                ->from('PRCPWB_TRHDAILYREQUESTS as y')
                ->whereColumn('y.VVENDORNO', 'PRCPWB_TRHDAILYREQUESTS.VVENDORNO')
                ->whereColumn('y.VPARTNO', 'PRCPWB_TRHDAILYREQUESTS.VPARTNO')
                ->where('y.VSTATUS', '!=', 'Received')
                ->whereRaw("DATE_TRUNC('month', y.\"DWANTEDRECEIPTDATE\") = DATE_TRUNC('month', \"PRCPWB_TRHDAILYREQUESTS\".\"DWANTEDRECEIPTDATE\")")
                ->whereRaw("y.\"DWANTEDRECEIPTDATE\" BETWEEN DATE_TRUNC('month', CURRENT_DATE) AND \"PRCPWB_TRHDAILYREQUESTS\".\"DWANTEDRECEIPTDATE\" - INTERVAL '1 day'");
        }, 'BAL');
}

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('prcpwbf005-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'processing' => false,
                'orderCellsTop' => true,
                'columnDefs' => [
                    [
                        'className' => 'text-start text-nowrap',
                        'targets' => '_all' // apply to all columns
                    ]
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
                        var table = this.api();
                        ' . $this->getScriptForSearchRow(false) . '
                    }
                ',
                'dom' => 'r' .
                    "<'table-responsive border-top'tr>" .
                    "<'d-flex align-items-center justify-content-center justify-content-lg-between flex-wrap gap-2 text-center px-6 mt-6'ip>",
                'drawCallback' => '
                    function() {
                        $("#select-all-service").off("click").on("click", function(){
                            var checked = this.checked;
                            $("input[name=\'selected-service[]\']").prop("checked", checked).trigger("change");
                        });

                        $("input[name=\'selected-service[]\']").off("change").on("change", function(){
                            var anyChecked = $("input[name=\'selected-service[]\']:checked").length > 0;
                            $("#btn-delete-selected").toggleClass("d-none", !anyChecked);

                            var allChecked = $("input[name=\'selected-service[]\']").length === $("input[name=\'selected-service[]\']:checked").length;
                            $("#select-all-service").prop("checked", allChecked);
                        });
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
            Column::make('DT_RowIndex')->title('No')->searchable(false)->orderable(false),
            Column::make('VVENDORNO')->title('Vendor ID'),
            Column::make('VVENDORNAME')->title('Vendor Name'),
            Column::make('DWANTEDRECEIPTDATE')->title('Wanted Receipt Date'),
            Column::make('VTIME')->title('Time'),
            Column::make('VPARTNO')->title('Part Number'),
            Column::make('VPARTDESCRIPTION')->title('Part Description'),
            Column::make('BAL')->title('BAL')->searchable(false),
            Column::make('VUNITMEAS')->title('UOM'),
            Column::make('IQUANTITY')->title('QTY DR'),
            Column::make('IQUANTITYCONFIRMATION')->title('QTY SJ'),
            Column::make('IQUANTITYACTUAL')->title('QTY ACT'),
            Column::make('VSTATUS')->title('Status'),
            Column::make('VPONO')->title('PO Number'),
            Column::make('VDAILYREQNO')->title('DR Number'),
            Column::make('VDELIVERYNOTENO')->title('SJ Number'),
            Column::make('VPRODUCTFAMILY')->title('PROD FAMILY'),
            Column::make('DMODI')->title('Actual Receipt Date'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'PRCPWBF005_' . date('YmdHis');
    }
}
