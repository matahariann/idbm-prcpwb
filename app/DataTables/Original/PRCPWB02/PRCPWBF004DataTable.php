<?php

namespace App\DataTables\Original\PRCPWB02;

use App\Models\PRCPWB02\PRCPWB_TRHPO as PO;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

// Inbox PO
class PRCPWBF004DataTable extends DataTable
{
    use DataTableTrait;

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<PO> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('VORDERNO', function ($row) {
                $url = route('po.detail', ['id' => $row->IID]);
                return '<a href="' . $url . '" class="text-primary text-decoration-underline">' . $row->VORDERNO . '</a>';
            })
            ->editColumn('DRELEASEDATE', function ($data) {
                return $data->DRELEASEDATE
                    ? Carbon::parse($data->DRELEASEDATE)->format('d M Y H:i')
                    : null;
            })
            ->editColumn('DCONFIRMDATE', function ($data) {
                return $data->DCONFIRMDATE
                    ? Carbon::parse($data->DCONFIRMDATE)->format('d M Y H:i')
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
            ->setRowId('IID')
            ->rawColumns(['VORDERNO'])

            ->filterColumn('DRELEASEDATE', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DRELEASEDATE', $keyword);
            })
            ->filterColumn('DCONFIRMDATE', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DCONFIRMDATE', $keyword);
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
                    $query->whereAny(['PRCPWB_TRHPO.VORDERNO', 'PRCPWB_TRHPO.VVENDORNO', 'VVENDORNAME'], 'ILIKE', "%{$keyword}%");
                }
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
     * @return QueryBuilder<PO>
     */
    public function query(PO $model): QueryBuilder
    {
        return $model->newQuery()
            ->leftJoin('PRCPWB_MSHVENDORS', 'PRCPWB_TRHPO.VVENDORNO', '=', 'PRCPWB_MSHVENDORS.VVENDORNO')
            ->select([
                'PRCPWB_TRHPO.*', 
                'PRCPWB_MSHVENDORS.VVENDORNAME as VVENDORNAME'
            ]);
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('prcpwbf004-table')
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
            Column::make('VORDERNO')->title('PO Number'),
            Column::make('VVENDORNO')->title('Vendor ID'),
            Column::make('VVENDORNAME')->title('Vendor Name'),
            Column::make('VSTATUS')->title('Status'),
            Column::make('DRELEASEDATE')->title('Release Date'),
            Column::make('VCONFIRMTEXT')->title('Confirmaion Text'),
            Column::make('DCONFIRMDATE')->title('Confirmation Date'),
            Column::make('IREVISIONNO')->title('Revision No'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'PRCPWBF004_' . date('YmdHis');
    }
}
