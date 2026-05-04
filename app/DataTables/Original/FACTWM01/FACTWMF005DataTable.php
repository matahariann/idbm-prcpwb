<?php

namespace App\DataTables\Original\FACTWM01;

use App\Models\FACTWM01\FACTWM_MSHINFORMATION as Information;
use App\Traits\DataTableTrait;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

// Datatable master information
class FACTWMF005DataTable extends DataTable
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
                        <input type="checkbox" class="form-check-input" name="selected-service[]" value="' . $row->IID . '">
                    </div>
                ';
            })
            ->addColumn('action', fn($data) => $this->getActionButton($data))
            ->editColumn('VNOTES', function ($data) {
                return strlen($data->VNOTES) > 50 ? substr($data->VNOTES, 0, 150) . '...' : $data->VNOTES;
            })
            ->editColumn('ITOTALVIEW', function ($data) {
                return number_format($data->ITOTALVIEW) ?? '0';
            })
            ->editColumn('VFILE_INFORMATION', function ($data) {
                return $this->getFilePreview($data->VFILE_INFORMATION, 'PDF Information');
            })
            ->editColumn('VUPDLOAD_DATA_VENDOR', function ($data) {
                return $this->getFilePreview($data->VUPDLOAD_DATA_VENDOR, 'Data Vendor');
            })
            ->editColumn('VUPDLOAD_FOTO_ASSET', function ($data) {
                return $this->getFilePreview($data->VUPDLOAD_FOTO_ASSET, 'Photo Asset', true);
            })
            ->editColumn('DFROM', function ($data) {
                return Carbon::parse($data->DFROM)->format('d M Y');
            })
            ->editColumn('DTO', function ($data) {
                return Carbon::parse($data->DTO)->format('d M Y');
            })
            ->editColumn('DCREA', function ($data) {
                return $data->DMODI
                    ? Carbon::parse($data->DMODI)->format('d M Y H:i')
                    : null; // atau '-'
            })
            ->editColumn('DMODI', function ($data) {
                return Carbon::parse($data->DMODI)->format('d M Y H:i');
            })
            ->rawColumns(['checkbox', 'action', 'VFILE_INFORMATION', 'VUPDLOAD_DATA_VENDOR', 'VUPDLOAD_FOTO_ASSET', 'VNOTES'])
            ->setRowId('IID')
            ->filterColumn('DFROM', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DFROM', $keyword);
            })
            ->filterColumn('DTO', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DTO', $keyword);
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
                         onclick="window.open(\'' . $fullPath . '\', \'_blank\')" data-bs-toggle="tooltip" data-placement="top" title="Lihat Gambar">
                </div>
            ';
            // <a href="' . $fullPath . '"
            //            target="_blank"
            //            class="badge ' . $badgeClass . ' d-inline-flex align-items-center gap-1"
            //            style="text-decoration: none;">
            //             <i class="' . $iconClass . '" style="font-size: 1rem;"></i>
            //             <span>View</span>
            //         </a>
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
            ->setTableId('factwmf005-table')
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
                        ' . $this->getScriptForSearchRowCustom() . '
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
                            if (anyChecked) {
                                $("#btn-delete-selected-service").removeClass("disabled");
                            } else {
                                $("#btn-delete-selected-service").addClass("disabled");
                            }

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
            Column::make('VUPDLOAD_FOTO_ASSET')->title('Picture')->class('text-nowrap'),
            Column::make('VNOTES')->title('Note')->class('text-nowrap'),
            Column::make('VUSER_TYPE')->title('User Type')->class('text-nowrap'),
            Column::make('ITOTALVIEW')->title('Total View')->class('text-nowrap'),
            Column::make('DFROM')->title('Start Date')->class('text-nowrap'),
            Column::make('DTO')->title('Expired Date')->class('text-nowrap'),
            // Column::make('VCATEGORY')->title('Category')->class('text-nowrap'),
            // Column::make('VFILE_INFORMATION')->title('File Information')->class('text-nowrap'),
            // Column::make('VUPDLOAD_DATA_VENDOR')->title('Upload Data Vendor')->class('text-nowrap'),
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
        return 'FACTWMF005_' . date('YmdHis');
    }

    private function getActionButton($data): string
    {
        $buttons = [
            [
                'action' => 'edit',
                'service' => 'FACTWMF005-Update',
                'icon' => 'pencil',
                'class' => 'edit-information',
            ],
            [
                'action' => 'delete',
                'service' => 'FACTWMF005-Delete',
                'icon' => 'trash',
                'class' => 'delete-information',
            ],
        ];

        return $this->actionButtons($data, $buttons);
    }

    private function getScriptForSearchRowCustom($skipLastColumn = true)
    {
        $script = '
        var thead = $(table.table().header());
        var headerCells = thead.find("tr:first th");

        // Create search row
        var searchRow = $("<tr></tr>");

        headerCells.each(function(index) {
            var column = table.column(index);
            var title = $(this).text().trim();
            var th = $("<th></th>");

            // Get total columns
            var totalColumns = headerCells.length;
            var isFirstColumn = index === 0;
            var isSecondColumn = index === 1;
            var isLastColumn = index === totalColumns - 1;

            // Skip checkbox (index 0), Picture (index 1), and Action (last index)
            if (isFirstColumn || isSecondColumn || (isLastColumn && ' . ($skipLastColumn ? 'true' : 'false') . ')) {
                searchRow.append(th);
                return true; // continue
            }

            // Special handling for User Type column - dropdown
            if (title.toLowerCase() === "user type") {
                var select = $("<select>", {
                    "class": "form-select form-select-sm",
                });

                select.append($("<option>", {
                    value: "",
                    text: "select"
                }));

                var userTypes = ["All", "Internal", "Supplier"];
                userTypes.forEach(function(type) {
                    select.append($("<option>", {
                        value: type.toLowerCase(),
                        text: type
                    }));
                });

                th.html(select);

                select.on("change", function() {
                    column.search($(this).val()).draw();
                });
            }
            // Date columns
            else if (/date/i.test(title)) {
                var input = $("<input>", {
                    "class": "form-control form-control-sm daterange-input",
                    "placeholder": "Select date",
                    "readonly": true
                });

                th.html(input);

                input.daterangepicker({
                    autoUpdateInput: false,
                    locale: {
                        cancelLabel: "Clear",
                        format: "DD MMM YYYY"
                    }
                });

                input.on("apply.daterangepicker", function(ev, picker) {
                    var startDate = picker.startDate.format("YYYY-MM-DD");
                    var endDate = picker.endDate.format("YYYY-MM-DD");
                    column.search(startDate + " to " + endDate).draw();
                    $(this).val(picker.startDate.format("DD MMM YYYY") + " - " + picker.endDate.format("DD MMM YYYY"));
                });

                input.on("cancel.daterangepicker", function(ev, picker) {
                    column.search("").draw();
                    $(this).val("");
                });
            }
            // Default text input for other columns
            else {
                var input = $("<input>", {
                    "class": "form-control form-control-sm",
                    "placeholder": "Search..."
                });

                th.html(input);

                input.on("keyup", function() {
                    column.search(input.val()).draw();
                });
            }

            searchRow.append(th);
        });

        // Append search row to thead
        thead.append(searchRow);
        ';

        return $script;
    }
}
