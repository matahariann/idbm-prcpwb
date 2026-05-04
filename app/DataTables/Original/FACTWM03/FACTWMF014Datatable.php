<?php

namespace App\DataTables\Original\FACTWM03;

use App\Models\FACTWM03\FACTWM_LOGLOGIN_HISTORY as LogHistory;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

final class FACTWMF014Datatable extends DataTable
{
    use DataTableTrait;

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Supplier> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="form-check-input" name="selected-service[]" value="' . ($row->IID ?? '') . '">';
            })
            ->editColumn('DMODI', function ($data) {
                return Carbon::parse($data->DLASTLOGIN)->timezone('Asia/Jakarta')->format('d M Y H:i');
            })
            ->editColumn('BISACCEPTPRIVACY', function ($data) {
                return $data->BISACCEPTPRIVACY ? 'Accepted' : 'Not Accepted';
            })
            ->rawColumns(['checkbox', 'DLASTLOGIN'])
            ->setRowId('IID')
            ->filter(function ($query) {
                $keyword = request('keyword');
                if (! empty($keyword)) {
                    $query->whereAny(['VUSERNAME', 'VFULLNAME', 'VEMAIL', 'VUSERTYPE'], 'ILIKE', "%{$keyword}%");
                }
            });
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Supplier>
     */
    public function query(LogHistory $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('DCREA', 'desc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('hituamf014-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'processing' => false,
                'orderCellsTop' => true,
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
                'dom' => 'r' .
                    "<'table-responsive border-top'tr>" .
                    "<'d-flex align-items-center justify-content-center justify-content-lg-between flex-wrap gap-2 text-center px-6 mt-6'ip>",
                'initComplete' => '
                    function () {
                        var table = this.api();
                        ' . $this->getScriptForSearchRow(false) . '
                    }
                ',
                'drawCallback' => '
                    function() {
                        $("#select-all-service").off("click").on("click", function(){
                            var checked = this.checked;
                            $("input[name=\'selected-service[]\']").prop("checked", checked).trigger("change");
                        });

                        $("input[name=\'selected-service[]\']").off("change").on("change", function(){
                            var anyChecked = $("input[name=\'selected-service[]\']:checked").length > 0;
                            $("#btn-delete-selected-service").toggleClass("d-none", !anyChecked);

                            var allChecked = $("input[name=\'selected-service[]\']").length === $("input[name=\'selected-service[]\']:checked").length;
                            $("#select-all-service").prop("checked", allChecked);
                        });
                    }
                ',
            ]);
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Login_History-' . date('YmdHis');
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('checkbox')
                ->title('<input type="checkbox" class="form-check-input" id="select-all-service">')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(30)
                ->addClass('text-center'),
            Column::make('VUSERNAME')->title('USERNAME')->addClass('text-nowrap'),
            Column::make('VFULLNAME')->title('FULL NAME')->addClass('text-nowrap'),
            Column::make('VEMAIL')->title('EMAIL')->addClass('text-nowrap'),
            Column::make('VUSERTYPE')->title('USER TYPE')->addClass('text-nowrap'),
            Column::make('VIPADDRESS')->title('IP ADDRESS')->addClass('text-nowrap'),
            Column::make('VUSERAGENT')->title('USER AGENT')->addClass('text-nowrap'),
            Column::make('BISACCEPTPRIVACY')->title('IS ACCEPT PRIVACY')->addClass('text-nowrap'),
            Column::make('DLASTLOGIN')->title('LAST LOGIN')->addClass('text-nowrap'),
        ];
    }
}
