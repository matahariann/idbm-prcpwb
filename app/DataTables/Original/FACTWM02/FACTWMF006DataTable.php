<?php

namespace App\DataTables\Original\FACTWM02;

use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION;
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class FACTWMF006DataTable extends DataTable
{
    use DataTableTrait;

    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('checkbox', function ($row) {
                // Cek apakah status adalah NEW
                $roles = Auth::user()->roles->pluck('VROLENAME')->toArray()[0];
                $isAdmin = $roles === 'admin';
                if ($isAdmin) {
                    $isDisabled = $row->VSTATUS !== 'DISPUTED' || $row->VREF_TYPE !== 'RECEIPT';
                    $disabledAttr = $isDisabled ? 'disabled' : '';
                    $cursorStyle = $isDisabled ? 'cursor: not-allowed; opacity: 0.5;' : 'cursor: pointer;';
                } else {
                    $isDisabled = $row->VSTATUS !== 'NEW' || $row->VREF_TYPE !== 'RECEIPT';
                    $disabledAttr = $isDisabled ? 'disabled' : '';
                    $cursorStyle = $isDisabled ? 'cursor: not-allowed; opacity: 0.5;' : 'cursor: pointer;';
                }

                return '
                    <div class="d-flex gap-2 align-items-center justify-content-center">
                        <input type="checkbox"
                               class="form-check-input row-checkbox"
                               name="selected[]"
                               value="' . $row->IID . '"
                               ' . $disabledAttr . '
                               style="' . $cursorStyle . '"
                               data-status="' . $row->VSTATUS . '">

                        <button class="btn btn-sm btn-view-methods"
                            data-id="' . $row->IID . '"
                            title="View Methods">
                            <i class="menu-icon icon-base ti tabler-square-plus"></i>
                        </button>
                    </div>
                ';
            })
            ->editColumn('VSTATUS', function ($data) {
                $status = explode('-', $data->VSTATUS)[0];
                $statusClass = match ($status) {
                    'APPROVED' => 'badge text-success',
                    'NEW' => 'badge text-secondary',
                    'DISPUTED' => 'badge text-danger',
                    'CLOSED' => 'badge text-info',
                    default => 'badge text-secondary'
                };

                $bgClass = match ($status) {
                    'APPROVED' => 'bg-success',
                    'NEW' => 'bg-secondary',
                    'DISPUTED' => 'bg-danger',
                    'CLOSED' => 'bg-info',
                    default => 'bg-secondary'
                };

                return '<span class="' . $statusClass . '" style="background-color: var(--bs-' . str_replace('bg-', '', $bgClass) . '-bg-subtle); padding: 0.35rem 0.65rem; border-radius: 0.375rem; font-weight: 500;">'
                    . ($status ?? '-') .
                    '</span>';
            })
            ->editColumn('VREF_TYPE', function ($data) {
                $typeClass = match (strtolower($data->VREF_TYPE)) {
                    'receipt' => 'badge bg-label-success',
                    'return' => 'badge bg-label-danger',
                    'retur' => 'badge bg-label-danger',
                    default => 'badge bg-label-secondary'
                };

                $typeClass .= ' ref-type';

                return '<span class="' . $typeClass . '" data-type="' . ($data->VREF_TYPE ?? '-') . '" data-return-ref="' . ($data->VRETURN_REF ?? '-') . '">' . ($data->VREF_TYPE ?? '-') . '</span>';
            })
            ->editColumn('VGR_NUMBER', function ($data) {
                return '<span class="gr-number">' . $data->VGR_NUMBER . '</span>';
            })
            ->editColumn('VRETURN_REF', function ($data) {
                $ref_number = $data->VRETURN_REF ?? '-';
                return '<span class="gr-reference">' . $ref_number . '</span>';
            })
            ->addColumn('action', fn($data) => $this->getActionButton($data))
            ->editColumn('DGR', function ($data) {
                return $data->DGR ? Carbon::parse($data->DGR)->format('d M Y H:i') : '-';
            })
            ->editColumn('DAPPROVE', function ($data) {
                return $data->DAPPROVE ? Carbon::parse($data->DAPPROVE)->format('d M Y H:i') : '-';
            })
            ->editColumn('DDISPUTE', function ($data) {
                return $data->DDISPUTE ? Carbon::parse($data->DDISPUTE)->format('d M Y H:i') : '-';
            })
            ->editColumn('VDISPUTEDESC', function ($data) {
                if (! $data->VDISPUTEDESC) {
                    return '<span class="text-muted">-</span>';
                }

                $text = strlen($data->VDISPUTEDESC) > 50
                    ? substr($data->VDISPUTEDESC, 0, 50) . '...'
                    : $data->VDISPUTEDESC;

                return '<span title="' . htmlspecialchars($data->VDISPUTEDESC) . '">' . htmlspecialchars($text) . '</span>';
            })
            ->editColumn('VDISPUTEREJECTDESC', function ($data) {
                if (! $data->VDISPUTEREJECTDESC) {
                    return '<span class="text-muted">-</span>';
                }

                $text = strlen($data->VDISPUTEREJECTDESC) > 50
                    ? substr($data->VDISPUTEREJECTDESC, 0, 50) . '...'
                    : $data->VDISPUTEREJECTDESC;

                return '<span title="' . htmlspecialchars($data->VDISPUTEREJECTDESC) . '">' . htmlspecialchars($text) . '</span>';
            })
            ->addColumn('VDISPUTEFILE', function ($data) {
                if (! $data->VDISPUTEFILE) {
                    return '<span class="text-muted">-</span>';
                }

                $fileName = basename($data->VDISPUTEFILE);
                $fileUrl = asset('storage/' . $data->VDISPUTEFILE);

                return '<a href="' . $fileUrl . '" target="_blank" class="btn btn-sm btn-outline-primary" title="Download/View File">
                            <i class="icon-base ti tabler-upload"></i> ' . htmlspecialchars($fileName) . '
                        </a>';
            })
            ->editColumn('DSYNC', function ($data) {
                return $data->DSYNC ? Carbon::parse($data->DSYNC)->format('d M Y H:i') : '-';
            })
            ->editColumn('DCREA', function ($data) {
                return $data->DCREA ? Carbon::parse($data->DCREA)->format('d M Y H:i') : '-';
            })
            ->editColumn('DMODI', function ($data) {
                return $data->DMODI ? Carbon::parse($data->DMODI)->format('d M Y H:i') : '-';
            })
            ->rawColumns(['checkbox', 'action', 'VSTATUS', 'VREF_TYPE', 'VGR_NUMBER', 'VDISPUTEDESC', 'VDISPUTEREJECTDESC', 'VDISPUTEFILE', 'VRETURN_REF'])
            ->setRowId('IID')
            ->filterColumn('DGR', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DGR', $keyword);
            })
            ->filterColumn('DAPPROVE', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DAPPROVE', $keyword);
            })
            ->filterColumn('DDISPUTE', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DDISPUTE', $keyword);
            })
            ->filterColumn('DSYNC', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DSYNC', $keyword);
            })
            ->filterColumn('DCREA', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DCREA', $keyword);
            })
            ->filterColumn('DMODI', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DMODI', $keyword);
            })
            // ->filterColumn('VDISPUTEDESC', function ($query, $keyword) {
            //     $query->where('VDISPUTEDESC', 'ILIKE', "%{$keyword}%");
            // })
            ->filter(function ($query) {
                $keyword = request('keyword');

                if (! empty($keyword)) {
                    $query->whereAny(['VGR_NUMBER', 'VDELIVERY_NUMBER', 'VPO_NUMBER', 'VVENDOR_NAME', 'VSTATUS'], 'ILIKE', "%{$keyword}%");
                }
            });
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(FACTWM_TRHGR_NOTES $model): QueryBuilder
    {
        $config = FACTWM_MSHCONFIGURATION::where('VVARIABLE', 'n_day')->pluck('VVALUE', 'VVARIABLE');
        $dataShowFrom = ! $config->isEmpty() ? (int) $config['n_day'] : 0; // jadi data akan di tampilkan setelah h+$dataShowFrom
        // jika yang login berasal dari eksternal atau supplier, tampilkan hanya data milik supplier tersebut
        $query = $model->newQuery();
        $query->where('DCREA', '<=', Carbon::now()->subDays($dataShowFrom));

        $user = Auth::user();
        if ($user?->supplierUser) {
            $supplierCode = trim((string) $user->supplierUser->VSUPPLIER_CODE);

            if ($supplierCode === '') {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('VVENDOR_CODE', $supplierCode);
            }
        }

        if (!request()->has('order')) {
            $query->orderBy('DCREA', 'desc');
        }
        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('factwmf006-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'processing' => false,
                'orderCellsTop' => true,
                'columnDefs' => [
                    [
                        'className' => 'text-center text-nowrap',
                        'targets' => '_all',
                    ],
                    [
                        'width' => '60px',
                        'targets' => 0,
                    ],
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

                        // Tambahkan tfoot ke thead
                        var tfoot = table.table().footer();
                        var thead = table.table().header();
                        $(tfoot).find("tr").appendTo($(thead));

                        ' . $this->getScriptForSearchRowCustom(false) . '

                        // Pindahkan checkbox select-all ke search row (baris kedua) - PASTI SETELAH SEARCH ROW DIBUAT
                        setTimeout(function() {
                            var checkboxHtml = \'<div class="d-flex justify-content-start align-items-center" style="height: 100%; padding: 0.5rem 0;"><input type="checkbox" id="select-all" class="form-check-input" style="width: 18px; height: 18px; cursor: pointer;"></div>\';
                            $("#factwmf006-table thead tr:eq(1) th:first").html(checkboxHtml);

                            // Event handler untuk select-all - hanya centang yang status NEW
                            $("#select-all").off("click").on("click", function(){
                                var checked = this.checked;
                                $(".row-checkbox:not(:disabled)").prop("checked", checked).trigger("change");
                            });
                        }, 100);
                    }
                ',
                'dom' => 'r' .
                    "<'table-responsive border-top'tr>" .
                    "<'d-flex align-items-center justify-content-center justify-content-lg-between flex-wrap gap-2 text-center px-6 mt-6'ip>",
                'drawCallback' => '
                    function() {
                        // Event handler untuk individual checkbox
                        $(".row-checkbox").off("change").on("change", function(){
                            // Cek apakah ada checkbox yang dicentang (dan tidak disabled)
                            var anyChecked = $(".row-checkbox:checked:not(:disabled)").length > 0;
                            if (anyChecked) {
                                $("#btn-approve-selected").removeClass("disabled");
                            } else {
                                $("#btn-approve-selected").addClass("disabled");
                            }

                            // Update status select-all checkbox berdasarkan checkbox yang tidak disabled
                            var enabledCheckboxes = $(".row-checkbox:not(:disabled)");
                            var enabledChecked = $(".row-checkbox:checked:not(:disabled)");
                            var allChecked = enabledCheckboxes.length > 0 && enabledCheckboxes.length === enabledChecked.length;
                            $("#select-all").prop("checked", allChecked);

                            // Set indeterminate jika ada yang dicentang tapi tidak semua
                            var someChecked = enabledChecked.length > 0 && !allChecked;
                            $("#select-all").off("click").on("click", function(){
                                var checked = this.checked;
                                $(".row-checkbox:not(:disabled)").prop("checked", checked).trigger("change");

                                // Trigger change event untuk update button state
                                $(".row-checkbox:not(:disabled)").first().trigger("change");
                            });

                            var initialChecked = $(".row-checkbox:checked:not(:disabled)").length > 0;
                            if (initialChecked) {
                                $("#btn-approve-selected").removeClass("disabled");
                            } else {
                                $("#btn-approve-selected").addClass("disabled");
                            }
                        });

                        // Re-bind select-all setelah draw - hanya centang yang tidak disabled
                        $("#select-all").off("click").on("click", function(){
                            var checked = this.checked;
                            $(".row-checkbox:not(:disabled)").prop("checked", checked).trigger("change");
                        });

                        // Destroy existing tooltips first
                        var existingTooltips = document.querySelectorAll(\'[data-bs-toggle="tooltip"]\');
                        existingTooltips.forEach(function(el) {
                            var tooltip = bootstrap.Tooltip.getInstance(el);
                            if (tooltip) {
                                tooltip.dispose();
                            }
                        });

                        // Initialize Bootstrap tooltips with custom configuration
                        var tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[data-bs-toggle="tooltip"]\'));
                        tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl, {
                                html: true,  // Allow HTML content
                                delay: { show: 300, hide: 100 },  // Delay before showing/hiding
                                customClass: "custom-tooltip-large"  // Custom class for styling
                            });
                        });
                    }
                ',
                'rowCallback' => '
                    function(row, data) {
                        $(row).find(".icon-base").css({
                            "font-size": "2rem",
                            "width": "2rem",
                            "height": "2rem"
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
                ->title('')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(60)
                ->addClass('text-center'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->searchable(false)
                ->width(80)
                ->addClass('text-center'),
            Column::make('VSTATUS')->title('Status'),
            Column::make('VREF_TYPE')->title('Reference Type'),
            Column::make('VRETURN_REF')->title('Return Reference'),
            Column::make('VGR_NUMBER')->title('GR Number'),
            Column::make('VDELIVERY_NUMBER')->title('Delivery Number'),
            Column::make('VPO_NUMBER')->title('PO Number'),
            Column::make('VVENDOR_CODE')->title('Vendor Code'),
            Column::make('VVENDOR_NAME')->title('Vendor Name'),
            // Column::make('VCURRENCY')->title('Currency'),
            Column::make('DGR')->title('GR Date'),
            Column::make('DAPPROVE')->title('Approve Date'),
            Column::make('DDISPUTE')->title('Dispute Date'),
            Column::make('VDISPUTEDESC')
                ->title('Dispute Description')
                ->exportable(false)
                ->printable(false)
                ->orderable(false),
            Column::make('VDISPUTEREJECTDESC')
                ->title('Dispute Reject Description')
                ->exportable(false)
                ->printable(false)
                ->orderable(false),
            Column::computed('VDISPUTEFILE')
                ->title('Dispute File')
                ->exportable(false)
                ->printable(false)
                ->orderable(false),
            Column::make('DSYNC')->title('Sync Date'),
            Column::make('DCREA')->title('Created Date'),
            Column::make('DMODI')->title('Modified Date'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'FACTWMF006_' . date('YmdHis');
    }

    // Contoh di method getActionButton() dengan tooltip panjang

    private function getActionButton($data): string
    {
        $buttons = [];
        $user = Auth::user();
        // $roles = $user->roles->pluck('VROLENAME')->toArray()[0] ?? null;
        $services = collect($user->serviceNames());

        $isFinanceAdmin = $services->contains('FACTWMF006-Approval');
        if ($isFinanceAdmin) {
            if ($data->VSTATUS === 'DISPUTED') {
                $buttons[] = [
                    'action' => 'approve',
                    'service' => 'FACTWMF006-Approval',
                    'icon' => 'check',
                    'class' => 'approve-dispute text-success',
                    'title' => '<strong>Approve Dispute</strong><br>Klik untuk menyetujui dispute yang diajukan. Tindakan ini tidak akan mengubah status.',
                ];

                $buttons[] = [
                    'action' => 'reject',
                    'service' => 'FACTWMF006-Approval',
                    'icon' => 'x',
                    'class' => 'reject-dispute text-danger',
                    'title' => '<strong>Reject Dispute</strong><br>Klik untuk menolak dispute yang diajukan. Tindakan ini akan mengubah status kembali.',
                ];
            }
        }

        $eksternalUser = $services->contains('FACTWMF006-Dispute');
        if ($eksternalUser) {
            if ($data->VSTATUS === 'CLOSED') {
                $buttons[] = [
                    'action' => 'dispute',
                    'service' => 'FACTWMF006-Dispute',
                    'icon' => 'refresh-alert',
                    'class' => 'dispute-menu disabled',
                    'disabled' => true,
                    'title' => 'Status CLOSED tidak dapat di-dispute. Hubungi administrator untuk informasi lebih lanjut.',
                ];
                $buttons[] = [
                    'action' => 'approve',
                    'service' => 'FACTWMF006-Dispute',
                    'icon' => 'checkup-list',
                    'class' => 'approve-menu text-success disabled',
                    'disabled' => true,
                    'title' => 'Status CLOSED tidak dapat disetujui. Data ini sudah dalam status final.',
                ];
            } else {
                if ($data->VSTATUS === 'DISPUTED' || str_starts_with($data->VSTATUS, 'DISPUTED-')) {
                    $buttons[] = [
                        'action' => 'dispute',
                        'service' => 'FACTWMF006-Dispute',
                        'icon' => 'refresh-alert',
                        'class' => 'dispute-menu',
                        'disabled' => true,
                        'title' => '<strong>Update Dispute</strong><br>Data sedang dalam status dispute. Klik untuk mengupdate informasi dispute atau menambahkan catatan baru.',
                    ];
                    $buttons[] = [
                        'action' => 'approve',
                        'service' => 'FACTWMF006-Dispute',
                        'icon' => 'checkup-list',
                        'class' => 'approve-menu text-success disabled',
                        'disabled' => true,
                        'title' => 'Data dalam status dispute tidak dapat disetujui. Silakan selesaikan dispute terlebih dahulu.',
                    ];
                } else {
                    if ($data->VSTATUS === 'APPROVED') {
                        $buttons[] = [
                            'action' => 'dispute',
                            'service' => 'FACTWMF006-Dispute',
                            'icon' => 'refresh-alert',
                            'class' => 'dispute-menu',
                            'disabled' => true,
                            'title' => '<strong>Ajukan Dispute</strong><br>Klik untuk mengajukan dispute pada data ini. Anda dapat menambahkan deskripsi dan lampiran file sebagai bukti pendukung.',
                        ];

                        $buttons[] = [
                            'action' => 'unapprove',
                            'service' => 'FACTWMF006-Dispute',
                            'icon' => 'circle-x',
                            'class' => 'approve-menu',
                            'data' => [
                                'icon-color' => '#ff8a00',
                            ],
                            'title' => '<strong>Batalkan Approval</strong><br>Membatalkan persetujuan yang sudah diberikan. Status akan kembali menjadi NEW dan dapat diproses ulang.',
                        ];
                    } else {
                        $buttons[] = [
                            'action' => 'dispute',
                            'service' => 'FACTWMF006-Dispute',
                            'icon' => 'refresh-alert',
                            'class' => 'dispute-menu',
                            'disabled' => false,
                            'title' => '<strong>Ajukan Dispute</strong><br>Klik untuk mengajukan dispute pada data ini. Anda dapat menambahkan deskripsi dan lampiran file sebagai bukti pendukung.',
                        ];

                        $buttons[] = [
                            'action' => 'approve',
                            'service' => 'FACTWMF006-Dispute',
                            'icon' => 'checkup-list',
                            'class' => 'approve-menu text-success',
                            'title' => '<strong>Setujui Data</strong><br>Menyetujui data GR ini. Setelah disetujui, data akan berstatus APPROVED dan dapat diproses lebih lanjut ke sistem.',
                        ];
                    }
                }
            }
        }

        if ($data->VREF_TYPE == 'RETURN') {
            return '-';
        }
        return $this->actionButtonsCustom($data, $buttons);
    }

    private function actionButtonsCustom($data, $actions)
    {
        $user = Auth::user();
        $button = '';
        $services = collect($user->serviceNames());

        foreach ($actions as $action) {
            $class = $action['action'] . '-' . strtolower(last(explode(' ', $action['service'])));
            $class = $action['class'] ?? $class;
            $url = $action['url'] ?? 'javascript:void(0)';
            $dataAttributes = $action['data'] ?? [];
            $title = $action['title'] ?? '';

            $isDisabled = $action['disabled'] ?? false;

            if ($services->contains($action['service'])) {
                $disabledStyle = $isDisabled ? 'pointer-events: none; opacity: 0.5; cursor: not-allowed;' : '';
                $disabledAttr = $isDisabled ? 'aria-disabled="true"' : '';

                $customButton = '<a href="' . $url . '"
                                class="' . $class . '"
                                data-id="' . $data->IID . '"
                                ' . $disabledAttr . '
                                style="' . $disabledStyle . '"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="' . htmlspecialchars($title) . '"
                                ' . collect($dataAttributes)->map(fn($value, $key) => 'data-' . $key . '="' . htmlspecialchars($value) . '"')->implode(' ') . '>
                                <i class="icon-base ti tabler-' . ($action['icon'] ?? 'settings') . '" style="font-size: 2rem; color: ' . htmlspecialchars($dataAttributes['icon-color'] ?? 'inherit') . ';"></i>
                            </a>';

                if (isset($action['custom'])) {
                    $customButton = $action['custom'];
                }

                $button .= $customButton;
            }
        }

        return $button ?: '-';
    }

    /**
     * Get script for search row with custom filters
     */
    private function getScriptForSearchRowCustom($skipLastColumn = true)
    {
        $script = '
        var tfoot = $(table.table().footer());
        var thead = $(table.table().header());
        var headerCells = thead.find("tr:first th");

        // Pastikan tfoot sudah ada di thead
        if (thead.find("tr").length < 2) {
            tfoot.find("tr").clone().appendTo(thead);
        }

        $(thead).find("tr:eq(1) th").each(function(index) {
            var column = table.column(index);
            var title = $(headerCells[index]).text().trim();

            // Skip first column (checkbox) and second column (actions)
            var skipLast = ' . ($skipLastColumn ? 'true' : 'false') . ';
            var columnSkiped = index === 0 || (index === 1 && skipLast) || (index === $(thead).find("tr:eq(1) th").length - 1 && skipLast) ||
            title.toLowerCase() === "dispute file";

            if (columnSkiped) {
                $(this).html("");
                return;
            }

            // Special handling for Status column
            if (title.toLowerCase() === "status") {
                var select = $("<select>", {
                    "class": "form-select form-select-sm",
                });

                // Add default option
                select.append($("<option>", {
                    value: "",
                    text: "All Status"
                }));

                // Add status options
                var statuses = ["APPROVED", "NEW", "DISPUTED", "CLOSED"];
                statuses.forEach(function(status) {
                    select.append($("<option>", {
                        value: status,
                        text: status
                    }));
                });

                $(this).html(select);

                select.on("change", function() {
                    column.search($(this).val()).draw();
                });
            }
            // Check if the header text contains "Date" (case-insensitive)
            else if (/date/i.test(title)) {
                var input = $("<input>", {
                    "class": "form-control form-control-sm daterange-input",
                    "placeholder": "Select date",
                    "readonly": true
                });

                $(this).html(input);

                // Initialize Daterangepicker
                input.daterangepicker({
                    autoUpdateInput: false,
                    locale: {
                        cancelLabel: "Clear",
                        format: "DD MMM YYYY"
                    }
                });

                // Event when a date range is applied
                input.on("apply.daterangepicker", function(ev, picker) {
                    var startDate = picker.startDate.format("YYYY-MM-DD");
                    var endDate = picker.endDate.format("YYYY-MM-DD");
                    column.search(startDate + " to " + endDate).draw();
                    $(this).val(picker.startDate.format("DD MMM YYYY") + " - " + picker.endDate.format("DD MMM YYYY"));
                });

                // Event for clearing the date picker
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

                $(this).html(input);

                input.on("keyup", function() {
                    column.search(input.val()).draw();
                });
            }
        });
        ';

        return $script;
    }
}
