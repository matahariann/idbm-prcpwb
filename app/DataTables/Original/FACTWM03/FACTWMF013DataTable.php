<?php

namespace App\DataTables\Original\FACTWM03;

use App\Helpers\Helpers;
use App\Models\FACTWM03\FACTWM_FILE as FACTWMF013;
use App\Traits\DataTableTrait;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\CollectionDataTable;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER as Supplier;

class FACTWMF013DataTable extends DataTable
{
    use DataTableTrait;
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<FACTWMF013> $query Results from query() method.
     */
    public function dataTable(Builder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('users', function ($row) {
                return '
                    <div class="d-flex align-items-center gap-2">
                        <img src="/assets/img/initial-logo.svg" class="rounded-circle" width="32">
                        <div>
                            <div class="fw-semibold">' . $row->users?->VNAME . '</div>
                            <small class="text-muted">' . $row->users?->VEMAIL . '</small>
                        </div>
                    </div>
                ';
            })
            ->editColumn('ISIZE', function ($row) {
                return $this->formatBytes($row->ISIZE);
            })
            ->editColumn('DMODI', function ($row) {
                return $row->DMODI
                    ? Carbon::parse($row->DMODI, 'UTC')
                    ->setTimezone('Asia/Jakarta')
                    ->format('Y-m-d H:i:s') : null;
            })
            ->rawColumns(['users'])
            ->filter(function ($query) {
                $date = request('filter_date');
                $supplierId = Helpers::getSupplierId();

                $supplier = null;
                $supplier_code = null;
                if (!empty($supplierId)) {
                    $supplier = Supplier::find($supplierId);
                    $supplier_code = $supplier->VSUPPLIER_CODE;
                }

                if (!empty($date)) {
                    $this->applyDateRangeFilter($query, 'DMODI', $date);
                    // $query->whereBetween('DMODI', [$start_date, $end_date]);
                } else {
                    $query->whereDate('DMODI', date('Y-m-d'));
                }

                // filter supplier
                $query->when($supplier_code, function ($query) use ($supplier_code) {
                    return $query->where('VSUPPLIER_CODE', $supplier_code);
                });
            });
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<FACTWMF013>
     */
    public function query(FACTWMF013 $model): QueryBuilder
    {
        return $model->with(['users'])->newQuery();
    }
    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('factwmf013-table')
            ->columns($this->getColumns())
            ->ajax([
                'url' => route('document-managements.dataTable'),
                'type' => 'GET',
                'data' => 'function(d) {
                    d.filter_date = $("#filter_date").val();
                }',
            ])
            ->parameters([
                'processing' => false,
                'orderCellsTop' => true,
                'dom' => 'r' .
                    "<'table-responsive border-top'tr>" .
                    "<'d-flex align-items-center justify-content-center justify-content-lg-between flex-wrap gap-2 text-center px-6 mt-6'ip>",
            ]);
    }


    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('VNAME')->title('FILE NAME'),
            Column::make('VEXTENSION')->title('TYPE FILE'),
            Column::make('ISIZE')->title('SIZE'),
            Column::make('DMODI')->title('LAST MODIFIED'),
            Column::computed('users')
                ->title('USERS')
                ->exportable(false)
                ->printable(false),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'FACTWMF013_' . date('YmdHis');
    }

    private function formatBytes($bytes, $precision = 0)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
