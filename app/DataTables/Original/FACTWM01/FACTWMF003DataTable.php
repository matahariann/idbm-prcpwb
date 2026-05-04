<?php

namespace App\DataTables\Original\FACTWM01;

use App\Models\FACTWM01\FACTWM_MSHSUPPLIER_COMMUNICATION_METHOD as Supplier;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class FACTWMF003DataTable extends DataTable
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
                return '
                    <div class="d-flex gap-2 align-items-center">
                        <input type="checkbox" class="form-check-input" name="selected[]" value="' . $row->id . '">
                    </div>
                ';
            })
            ->addColumn('action', fn($data) => $this->getActionButton($data))
            ->editColumn('VUSERNAME', function ($data) {
                return $data->VUSERNAME ?? '-';
            })
            ->editColumn('DCREA', function ($data) {
                return Carbon::parse($data->DCREA)->format('d M Y H:i');
            })
            ->editColumn('DMODI', function ($data) {
                return $data->DMODI
                    ? Carbon::parse($data->DMODI)->format('d M Y H:i')
                    : null; // atau '-'
            })
            ->rawColumns(['checkbox', 'action'])
            ->setRowId('IID')
            ->filterColumn('DCREA', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DCREA', $keyword);
            })
            ->filterColumn('DMODI', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DMODI', $keyword);
            })
            ->filter(function ($query) {
                $keyword = request('keyword');

                if (! empty($keyword)) {
                    $query->whereAny(['VUSERNAME', 'VNAME', 'VDESCRIPTION', 'VMETHOD_ID', 'VVALUE'], 'ILIKE', "%{$keyword}%");
                }
            });
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Supplier>
     * @var \App\Models\User $userSupplier
     */
    public function query(Supplier $model): QueryBuilder
    {
        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;
        $supplierCode = $userSupplier ? $userSupplier->VSUPPLIER_CODE : null;

        return $model->newQuery()->where('VSUPPLIER_CODE', $supplierCode)->orderBy('DCREA', 'desc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('factwmf003-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'processing' => false,
                'orderCellsTop' => true,
                'columnDefs' => [
                    [
                        'className' => 'text-center text-nowrap',
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
                            $("#btn-delete-selected-service").toggleClass("d-none", !anyChecked);

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
            Column::computed('checkbox')
                ->title('<input type="checkbox" class="form-check-input" id="select-all-service">')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(30)
                ->addClass('text-center'),
            Column::make('VUSERNAME')->title('Username'),
            Column::make('VNAME')->title('Name'),
            Column::make('VDESCRIPTION')->title('Description'),
            Column::make('VMETHOD_ID')->title('Communication Method'),
            Column::make('VVALUE')->title('Value'),
            Column::make('VCREA')->title('Created By'),
            Column::make('DCREA')->title('Created Date'),
            Column::make('VMODI')->title('Modified By'),
            Column::make('DMODI')->title('Modified Date'),
            Column::computed('action')
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
        return 'HITUAMF010_' . date('YmdHis');
    }

    private function getActionButton($data): string
    {
        $buttons = [
            [
                'action' => 'edit',
                'service' => 'FACTWMF003-Update',
                'icon' => 'pencil',
                'class' => 'edit-request',
            ],
            [
                'action' => 'delete',
                'service' => 'FACTWMF003-Delete',
                'icon' => 'trash',
                'class' => 'delete-request',
            ],
        ];

        return '<div id="action-buttons-' . $data->IID . '">' . $this->actionButtons($data, $buttons) . '</div>';
    }
}
