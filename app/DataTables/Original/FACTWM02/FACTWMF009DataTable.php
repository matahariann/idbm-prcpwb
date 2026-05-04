<?php

namespace App\DataTables\Original\FACTWM02;

use App\Models\FACTWM02\FACTWM_TRHVERIFY_PO;
use App\Models\FACTWM02\FACTWM_TRHVERIFY_NON_PO;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Facades\DataTables;

class FACTWMF009DataTable extends DataTable
{
    /**
     * Build the DataTable class.
     */
    public function dataTable($query)
    {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('SUBMITTED_DATE', function ($row) {
                return $row->SUBMITTED_DATE ? date('d/m/Y H:i', strtotime($row->SUBMITTED_DATE)) : '-';
            });
    }

    /**
     * Get the query source of dataTable.
     */
    public function query()
    {
        // Query untuk PO
        $queryPO = FACTWM_TRHVERIFY_PO::query()
            ->select([
                DB::raw("'PO' as TYPE"),
                'FACTWM_TRHVERIFY_PO.IID',
                'FACTWM_MSHSUPPLIERS.VNAME as SUPPLIER_NAME',
                'FACTWM_TRHVERIFY_PO.VINVOICE_NUMBER as NO_INVOICE',
                'FACTWM_TRHVERIFY_PO.VBILLING_STATEMENT as NO_BS',
                'FACTWM_TRHVERIFY_PO.VUNIQUE_CODE as UNIK_CODE',
                'FACTWM_TRHVERIFY_PO.DSUBMITTED as SUBMITTED_DATE',
                'FACTWM_TRHVERIFY_PO.DCREA',
            ])
            ->leftJoin('FACTWM_MSHSUPPLIERS', 'FACTWM_TRHVERIFY_PO.VSUPPLIER_CODE', '=', 'FACTWM_MSHSUPPLIERS.VSUPPLIER_CODE')
            ->whereNotNull('FACTWM_TRHVERIFY_PO.DSUBMITTED')
            ->whereNull('FACTWM_TRHVERIFY_PO.DDELETE');

        // Query untuk Non-PO
        $queryNonPO = FACTWM_TRHVERIFY_NON_PO::query()
            ->select([
                DB::raw("'NON-PO' as TYPE"),
                'FACTWM_TRHVERIFY_NON_PO.IID',
                'FACTWM_MSHSUPPLIERS.VNAME as SUPPLIER_NAME',
                'FACTWM_TRHVERIFY_NON_PO.VINV_NO_SUPPLIER as NO_INVOICE',
                'FACTWM_TRHVERIFY_NON_PO.VBILLING_STATEMENT as NO_BS',
                'FACTWM_TRHVERIFY_NON_PO.VUNIQUE_CODE as UNIK_CODE',
                'FACTWM_TRHVERIFY_NON_PO.DSUBMITTED as SUBMITTED_DATE',
                'FACTWM_TRHVERIFY_NON_PO.DCREA',
            ])
            ->leftJoin('FACTWM_MSHSUPPLIERS', 'FACTWM_TRHVERIFY_NON_PO.VSUPPLIER_CODE', '=', 'FACTWM_MSHSUPPLIERS.VSUPPLIER_CODE')
            ->whereNotNull('FACTWM_TRHVERIFY_NON_PO.DSUBMITTED')
            ->whereNull('FACTWM_TRHVERIFY_NON_PO.DDELETE');

        // Apply date range filter if exists
        if ($this->request()->has('start_date') && $this->request()->has('end_date')) {
            $startDate = $this->request()->get('start_date');
            $endDate = $this->request()->get('end_date');

            $queryPO->whereBetween('FACTWM_TRHVERIFY_PO.DSUBMITTED', [$startDate, $endDate]);
            $queryNonPO->whereBetween('FACTWM_TRHVERIFY_NON_PO.DSUBMITTED', [$startDate, $endDate]);
        }

        // Union kedua query
        return $queryPO->union($queryNonPO)->orderBy('SUBMITTED_DATE', 'desc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('factwmf009-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'processing' => true,
                'serverSide' => true,
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
            Column::make('DT_RowIndex')
                ->title('NO')
                ->searchable(false)
                ->orderable(false)
                ->width(50)
                ->addClass('text-center'),
            Column::make('SUPPLIER_NAME')
                ->title('SUPPLIER NAME'),
            Column::make('NO_INVOICE')
                ->title('NO INVOICE'),
            Column::make('NO_BS')
                ->title('NO BS'),
            Column::make('UNIK_CODE')
                ->title('UNIK CODE'),
            Column::make('SUBMITTED_DATE')
                ->title('SUBMITTED DATE'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'HistoryScanPO_' . date('YmdHis');
    }
}
