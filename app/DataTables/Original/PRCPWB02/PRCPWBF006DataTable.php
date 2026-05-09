<?php

namespace App\DataTables\Original\PRCPWB02;

use App\Models\PRCPWB02\VW_PRCPWB_VENDORFILTER as VendorFilterView;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;


// Stock Vendor
class PRCPWBF006DataTable extends DataTable
{
    use DataTableTrait;

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<VendorFilterView> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('checkbox', function ($row) {
                return '
                    <div class="d-flex gap-2 align-items-center">
                        <input type="checkbox" class="form-check-input" name="selected[]" value="' . $row->IID . '">
                    </div>
                ';
            })
            ->editColumn('upload_date', function($row) {
                return \Carbon\Carbon::parse($row->upload_date)->format('Y-m-d');
            })
            ->addColumn('time', function($row) {
                return \Carbon\Carbon::parse($row->upload_date)->format('H:i:s');
            })
            ->editColumn('qty_on_hand', fn($row) => number_format($row->qty_on_hand, 2, '.', ','))
            ->editColumn('qty_dr', fn($row) => number_format($row->qty_dr, 2, '.', ','))
            ->editColumn('bal', fn($row) => number_format($row->bal, 2, '.', ','))
            ->editColumn('judgment', function($row) {
                $color = match($row->judgment) {
                    'Green'  => '#26F305',
                    'Red'    => '#E53935',
                    'Yellow' => '#DFFF00',
                    default  => 'transparent'
                };
                return '<div style="background-color: '.$color.'; font-weight: bold; padding: 5px; text-align: center; border-radius: 4px;">' 
                        . $row->judgment . 
                       '</div>';
            })
            ->rawColumns(['checkbox', 'judgment']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<VendorFilterView>
     */
    public function query(VendorFilterView $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('prcpwbf006-table')
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
                        'title' => 'PRCPWB - Transaction Data Stock | IDBM - PO Web',
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
                        $("#select-all").off("click").on("click", function(){
                            var checked = this.checked;
                            $("input[name=\'selected[]\']").prop("checked", checked).trigger("change");
                        });

                        $("input[name=\'selected[]\']").off("change").on("change", function(){
                            var anyChecked = $("input[name=\'selected[]\']:checked").length > 0;
                            $("#btn-delete-selected").toggleClass("d-none", !anyChecked);

                            var allChecked = $("input[name=\'selected[]\']").length === $("input[name=\'selected[]\']:checked").length;
                            $("#select-all").prop("checked", allChecked);
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
            Column::computed('checkbox')
                ->title('<input type="checkbox" class="form-check-input" id="select-all">')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(30)
                ->addClass('text-center'),
            Column::make('upload_date')->title('Date')->width(90),
            Column::make('time')->title('Time')->width(75)->searchable(false), // Kolom tambahan buatan
            Column::make('vendor_no')->title('Vendor ID')->width(70),
            Column::make('vendor_name')->title('Vendor Name')->width(150),
            Column::make('part_no')->title('Part No')->width(150),
            Column::make('description')->title('Description')->width(200),
            Column::make('unit_meas')->title('UOM')->width(40),
            Column::make('qty_on_hand')->title('Current Stock')->addClass('text-right')->width(80),
            Column::make('qty_dr')->title('DR Qty')->addClass('text-right')->width(60),
            Column::make('bal')->title('Outstanding')->addClass('text-right')->width(80),
            Column::make('judgment')->title('Judgment')->width(90)->addClass('text-center'),
            Column::make('remark')->title('Remark')->width(100),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'PRCPWBF006_' . date('YmdHis');
    }
}
