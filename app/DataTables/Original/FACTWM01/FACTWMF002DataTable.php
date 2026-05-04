<?php

namespace App\DataTables\Original\FACTWM01;

use App\Models\FACTWM01\FACTWM_MSHSUPPLIER as Supplier;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class FACTWMF002DataTable extends DataTable
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
                        <input type="checkbox" class="form-check-input" name="selected[]" value="' . $row->IID . '">

                        <button class="btn btn-sm btn-view-methods"
                            data-id="' . $row->IID . '"
                            title="View Methods">
                            <i class="menu-icon icon-base ti tabler-square-plus"></i>
                        </button>
                    </div>
                ';
            })
            ->addColumn('action', fn($data) => $this->getActionButton($data))
            ->editColumn('BPKP', function ($data) {
                return $data->BPKP ? 'PKP' : 'Non-PKP';
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
            ->filter(function ($query) {
                $keyword = request('keyword');

                if (! empty($keyword)) {
                    $query->whereAny(['VSUPPLIER_CODE', 'VNAME', 'VADDRESS', 'VNPWP'], 'ILIKE', "%{$keyword}%");
                }
            });
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Supplier>
     */
    public function query(Supplier $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('DCREA', 'desc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('factwmf002-table')
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
                    $("#select-all").off("click").on("click", function(){
                        var checked = this.checked;
                        $("input[name=\'selected[]\']").prop("checked", checked).trigger("change");
                    });

                    $("input[name=\'selected[]\']").off("change").on("change", function(){
                        var checkedCount = $("input[name=\'selected[]\']:checked").length;

                        // Jika tepat 1 data dipilih, enable kedua tombol
                        if (checkedCount === 1) {
                            $("#btn-delete-selected").removeClass("disabled");
                            $("#btn-eksport").removeClass("disabled");
                        }
                        // Jika tidak ada yang dipilih, disable kedua tombol
                        else if (checkedCount === 0) {
                            $("#btn-delete-selected").addClass("disabled");
                            $("#btn-eksport").addClass("disabled");
                        }
                        // Jika lebih dari 1 dipilih, disable btn-delete-selected tapi enable btn-eksport
                        else {
                            $("#btn-delete-selected").addClass("disabled");
                            $("#btn-eksport").removeClass("disabled");
                        }

                        var allChecked = $("input[name=\'selected[]\']").length === checkedCount;
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
            Column::make('VSUPPLIER_CODE')->title('Vendor Code'),
            Column::make('VNAME')->title('Vendor Name'),
            Column::make('VNPWP')->title('NPWP'),
            Column::make('VNIK')->title('NIK'),
            Column::make('BPKP')->title('Status PKP'),
            Column::make('VPAYMENT_TERM')->title('Term of payment'),
            Column::make('VADDRESS')->title('Address'),
            Column::make('VGROUP')->title('Supplier Group'),
            Column::make('VSTAT_GROUP')->title('Supplier Stat Group'),
            Column::make('VTAX_CODE')->title('Tax Code'),
            // Column::make('VCREA')->title('Created By'),
            // Column::make('DCREA')->title('Created Date'),
            // Column::make('VMODI')->title('Modified By'),
            // Column::make('DMODI')->title('Modified Date'),
            // Column::computed('action')
            //     ->exportable(false)
            //     ->printable(false)
            //     ->width(60)
            //     ->addClass('text-center'),
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
                'service' => 'HITUAMF010-Update',
                'icon' => 'pencil',
                'class' => 'edit-menu',
            ],
            [
                'action' => 'delete',
                'service' => 'HITUAMF010-Delete',
                'icon' => 'trash',
                'class' => 'delete-menu',
            ],
        ];

        return $this->actionButtons($data, $buttons);
    }
}
