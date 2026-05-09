<?php

namespace App\DataTables\Original\PRCPWB01;

use App\Models\PRCPWB01\PRCPWB_MSHVENDOR as Vendor;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PRCPWBF002DataTable extends DataTable
{
    use DataTableTrait;

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Vendor> $query Results from query() method.
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
            ->addColumn('action', fn($data) => $this->getActionButton($data))
            ->editColumn('VIMPORT', function ($data) {
                return $data->VIMPORT?->name;
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
            ->rawColumns(['checkbox', 'action'])

            ->filterColumn('DCREA', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DCREA', $keyword);
            })
            ->filterColumn('DMODI', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DMODI', $keyword);
            })
            ->filter(function ($query) {
                $keyword = request('keyword');

                if (! empty($keyword)) {
                    $query->whereAny(['VVENDORNO', 'VVENDORNAME'], 'ILIKE', "%{$keyword}%");
                }
            });
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Vendor>
     */
    public function query(Vendor $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('prcpwbf002-table')
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
                        'title' => 'PRCPWB - Master Data Vendor | IDBM - PO Web',
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
                'drawCallback' => 'function() {}',
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
            Column::make('VVENDORNO')->title('Vendor ID'),
            Column::make('VVENDORNAME')->title('Vendor Name'),
            Column::make('VCONTACT')->title('Contact'),
            Column::make('VADDRESS')->title('Address'),            
            Column::make('VIMPORT')->title('Import'),            
            Column::make('DCREA')->title('Created Date'),          
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'PRCPWBF002_' . date('YmdHis');
    }

    private function getActionButton($data): string
    {
        $buttons = [
            [
                'action' => 'edit',
                'service' => 'PRCPWBF002-Update',
                'icon' => 'pencil',
                'class' => 'edit-menu',
            ],
            [
                'action' => 'delete',
                'service' => 'PRCPWBF002-Delete',
                'icon' => 'trash',
                'class' => 'delete-menu',
            ],
        ];

        return $this->actionButtons($data, $buttons);
    }
}
