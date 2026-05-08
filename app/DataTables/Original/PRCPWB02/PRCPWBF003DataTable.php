<?php

namespace App\DataTables\Original\PRCPWB02;

use App\Models\PRCPWB02\PRCPWB_TRHFORECAST as Forecast;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

// Inbox Forecast
class PRCPWBF003DataTable extends DataTable
{
    use DataTableTrait;

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Forecast> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn() // Untuk kolom 'No'
            ->addColumn('register_no', function ($row) {
                $period = trim($row->VPERIOD); 
                $revNo = str_pad($row->IREVNO, 2, '0', STR_PAD_LEFT);
                $vendorNoShort = substr(trim($row->VVENDORNO), 1, 5);
                $destination = $row->VDESTINATIONID;

                $registerNo = "{$period}-{$revNo}-{$vendorNoShort}-{$destination}";
                $url = route('forecast.detail', ['id' => $row->IID]);
                return '<a href="' . $url . '" class="text-primary text-decoration-underline">' . $registerNo . '</a>';
            })
            ->editColumn('DRELEASEDATE', function ($data) {
                return $data->DRELEASEDATE
                    ? Carbon::parse($data->DRELEASEDATE)->format('d M Y H:i')
                    : null;
            })
            ->editColumn('DCREA', function ($data) {
                return Carbon::parse($data->DCREA)->format('d M Y H:i');
            })
            ->editColumn('DMODI', function ($data) {
                return $data->DMODI
                    ? Carbon::parse($data->DMODI)->format('d M Y H:i')
                    : null;
            })
            ->addColumn('action', fn($data) => $this->getActionButton($data))
            ->rawColumns(['register_no', 'action'])
            ->setRowId('IID')

            ->filterColumn('register_no', function ($query, $keyword) {
                $sql = '"PRCPWB_TRHFORECASTS"."VPERIOD" || \'-\' || 
                        LPAD(CAST("PRCPWB_TRHFORECASTS"."IREVNO" AS TEXT), 2, \'0\') || \'-\' || 
                        SUBSTRING("PRCPWB_TRHFORECASTS"."VVENDORNO" FROM 2 FOR 5) || \'-\' || 
                        "PRCPWB_TRHFORECASTS"."VDESTINATIONID"';
                        
                $query->whereRaw("({$sql}) ILIKE ?", ["%{$keyword}%"]);
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
                    $query->where(function ($q) use ($keyword) {
                        // 1. Kolom String Biasa
                        $q->where('PRCPWB_TRHFORECASTS.VVENDORNO', 'ILIKE', "%{$keyword}%")
                        ->orWhere('PRCPWB_MSHVENDORS.VVENDORNAME', 'ILIKE', "%{$keyword}%")
                        ->orWhere('PRCPWB_TRHFORECASTS.VSTATUS', 'ILIKE', "%{$keyword}%")
                        ->orWhere('PRCPWB_TRHFORECASTS.VCONFIRMNOTES', 'ILIKE', "%{$keyword}%");

                        // 2. Kolom Virtual (Register No)
                        $sqlRegister = '"PRCPWB_TRHFORECASTS"."VPERIOD" || \'-\' || 
                                        LPAD(CAST("PRCPWB_TRHFORECASTS"."IREVNO" AS TEXT), 2, \'0\') || \'-\' || 
                                        SUBSTRING("PRCPWB_TRHFORECASTS"."VVENDORNO" FROM 2 FOR 5) || \'-\' || 
                                        "PRCPWB_TRHFORECASTS"."VDESTINATIONID"';
                        $q->orWhereRaw("({$sqlRegister}) ILIKE ?", ["%{$keyword}%"]);
                    });
                }
            })

            ->orderColumn('register_no', function ($query, $order) {
                $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
                $query->orderByRaw('
                    "PRCPWB_TRHFORECASTS"."VPERIOD" || \'-\' || 
                    LPAD(CAST("PRCPWB_TRHFORECASTS"."IREVNO" AS TEXT), 2, \'0\') || \'-\' || 
                    SUBSTRING("PRCPWB_TRHFORECASTS"."VVENDORNO" FROM 2 FOR 5) || \'-\' || 
                    "PRCPWB_TRHFORECASTS"."VDESTINATIONID" ' . $order
                );
            })
            ->orderColumn('DRELEASEDATE', function ($query, $order) {
                $query->orderBy('DRELEASEDATE', $order);
            })
            ->orderColumn('DCONFIRMDATE', function ($query, $order) {
                $query->orderBy('DCONFIRMDATE', $order);
            });
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<PRCPWBF003>
     */
    public function query(Forecast $model): QueryBuilder
    {
        return $model->newQuery()
            ->leftJoin('PRCPWB_MSHVENDORS', 'PRCPWB_TRHFORECASTS.VVENDORNO', '=', 'PRCPWB_MSHVENDORS.VVENDORNO')
            ->select([
                'PRCPWB_TRHFORECASTS.*', 
                'PRCPWB_MSHVENDORS.VVENDORNAME as VVENDORNAME'
            ]);
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('prcpwbf003-table')
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
                        ' . $this->getScriptForSearchRow() . '
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
            Column::make('DT_RowIndex')->title('No')->searchable(false)->orderable(false)->width(30),
            Column::computed('register_no')->title('Register No')->searchable(true)->orderable(true),
            Column::make('VVENDORNO')->title('Vendor ID'),
            Column::make('VVENDORNAME')->title('Vendor Name'),
            Column::make('VDESTINATIONID')->title('Destination'),
            Column::make('VSTATUS')->title('Status'),
            Column::make('DRELEASEDATE')->title('Release Date'),
            Column::make('VCONFIRMNOTES')->title('Confirmation Text'),
            Column::make('DCONFIRMDATE')->title('Confirmation Date'),
            Column::computed('action')
                ->title('Detail')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'PRCPWBF003_' . date('YmdHis');
    }

    private function getActionButton($data): string
    {
        $buttons = [
            [
                'action' => 'detail',
                'service' => 'PRCPWBF003-Detail',
                'icon' => 'eye',
                'class' => 'detail-menu',
            ]
        ];

        return $this->actionButtons($data, $buttons);
    }
}
