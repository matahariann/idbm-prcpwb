<?php

namespace App\DataTables\Original\HITUAM01;

use App\Models\HITUAM01\HITUAM_MSHINFORMATION as Information;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class HITUAMF013DataTable extends DataTable
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
            ->editColumn('VFILE_INFORMATION', function ($data) {
                return $this->getFilePreview($data->VFILE_INFORMATION, 'PDF Information');
            })
            ->editColumn('VUPDLOAD_DATA_VENDOR', function ($data) {
                return $this->getFilePreview($data->VUPDLOAD_DATA_VENDOR, 'Data Vendor');
            })
            ->editColumn('VUPDLOAD_FOTO_ASSET', function ($data) {
                return $this->getFilePreview($data->VUPDLOAD_FOTO_ASSET, 'Photo Asset', true);
            })
            ->editColumn('DCREA', function ($data) {
                return Carbon::parse($data->DCREA)->format('d M Y H:i');
            })
            ->editColumn('DMODI', function ($data) {
                return $data->DMODI
                    ? Carbon::parse($data->DMODI)->format('d M Y H:i')
                    : null; // atau '-'
            })
            ->rawColumns(['checkbox', 'action', 'VFILE_INFORMATION', 'VUPDLOAD_DATA_VENDOR', 'VUPDLOAD_FOTO_ASSET'])
            ->setRowId('IID')
            ->filter(function ($query) {
                $keyword = request('keyword');

                if (! empty($keyword)) {
                    $query->whereAny(['VNOTES', 'VUSER_TYPE', 'VCATEGORY', 'DFROM', 'DTO'], 'ILIKE', "%{$keyword}%");
                }
            });
    }

    /**
     * Get file preview button with icon based on file type
     */
    private function getFilePreview(?string $filePath, string $label, bool $isImage = false): string
    {
        if (empty($filePath)) {
            return '<span class="badge bg-label-secondary">No File</span>';
        }

        $fullPath = asset('storage/' . $filePath);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Determine icon and color based on file type
        $iconClass = 'icon-base ti tabler-file';
        $badgeClass = 'bg-label-secondary';

        if ($isImage || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
            $iconClass = 'icon-base ti tabler-photo';
            $badgeClass = 'bg-label-success';
        } elseif ($extension === 'pdf') {
            $iconClass = 'icon-base ti tabler-file-type-pdf';
            $badgeClass = 'bg-label-danger';
        } elseif (in_array($extension, ['xlsx', 'xls', 'csv'])) {
            $iconClass = 'icon-base ti tabler-file-spreadsheet';
            $badgeClass = 'bg-label-info';
        } elseif (in_array($extension, ['doc', 'docx'])) {
            $iconClass = 'icon-base ti tabler-file-word';
            $badgeClass = 'bg-label-primary';
        }

        // For images, show thumbnail preview
        if ($isImage || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return '
                <div class="d-flex align-items-center gap-2">
                    <img src="' . $fullPath . '"
                         alt="' . $label . '"
                         class="rounded"
                         style="width: 40px; height: 40px; object-fit: cover; cursor: pointer;"
                         onclick="window.open(\'' . $fullPath . '\', \'_blank\')">
                    <a href="' . $fullPath . '"
                       target="_blank"
                       class="badge ' . $badgeClass . ' d-inline-flex align-items-center gap-1"
                       style="text-decoration: none;">
                        <i class="' . $iconClass . '" style="font-size: 1rem;"></i>
                        <span>View</span>
                    </a>
                </div>
            ';
        }

        // For other files, show badge with icon
        return '
            <a href="' . $fullPath . '"
               target="_blank"
               class="badge ' . $badgeClass . ' d-inline-flex align-items-center gap-1"
               style="text-decoration: none;">
                <i class="' . $iconClass . '" style="font-size: 1rem;"></i>
                <span>View ' . strtoupper($extension) . '</span>
            </a>
        ';
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Information>
     */
    public function query(Information $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('DCREA', 'desc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('hituamf013-table')
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
            Column::make('VNOTES')->title('Notes')->class('text-nowrap'),
            Column::make('DFROM')->title('From Date')->class('text-nowrap'),
            Column::make('DTO')->title('To Date')->class('text-nowrap'),
            Column::make('VUSER_TYPE')->title('User Type')->class('text-nowrap'),
            Column::make('VCATEGORY')->title('Category')->class('text-nowrap'),
            Column::make('VFILE_INFORMATION')->title('File Information')->class('text-nowrap'),
            Column::make('VUPDLOAD_DATA_VENDOR')->title('Upload Data Vendor')->class('text-nowrap'),
            Column::make('VUPDLOAD_FOTO_ASSET')->title('Upload Photo Asset')->class('text-nowrap'),
            Column::make('VCREA')->title('Created By')->class('text-nowrap'),
            Column::make('DCREA')->title('Created Date')->class('text-nowrap'),
            Column::make('VMODI')->title('Modified By')->class('text-nowrap'),
            Column::make('DMODI')->title('Modified Date')->class('text-nowrap'),
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
        return 'HITUAMF013_' . date('YmdHis');
    }

    private function getActionButton($data): string
    {
        $buttons = [
            [
                'action' => 'edit',
                'service' => 'HITUAMF013-Update',
                'icon' => 'pencil',
                'class' => 'edit-information',
            ],
            [
                'action' => 'delete',
                'service' => 'HITUAMF013-Delete',
                'icon' => 'trash',
                'class' => 'delete-information',
            ],
        ];

        return $this->actionButtons($data, $buttons);
    }
}
