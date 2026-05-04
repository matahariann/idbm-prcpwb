<?php

namespace App\DataTables\Original\FACTWM02;

use App\Models\FACTWM02\FACTWM_TRHVERIFY_NON_PO as VerifyNonPo;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class FACTWMF008DataTable extends DataTable
{
    use DataTableTrait;

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('checkbox', function ($row) {
                return '
                    <button class="btn btn-sm btn-view-details"
                        data-id="' . $row->IID . '"
                        title="View Methods">
                        <i class="menu-icon icon-base ti tabler-square-plus"></i>
                    </button>
                ';
            })
            ->editColumn('VSTATUS', function ($data) {
                $statusConfig = [
                    'CREATED' => ['class' => 'badge text-info', 'bg' => 'bg-info'],
                    'SUBMITTED' => ['class' => 'badge text-primary', 'bg' => 'bg-primary'],
                    'APPROVED' => ['class' => 'badge text-success', 'bg' => 'bg-success'],
                    'DECLINED' => ['class' => 'badge text-danger', 'bg' => 'bg-danger'],
                    'PAID' => ['class' => 'badge text-success', 'bg' => 'bg-success']
                ];

                $config = $statusConfig[$data->VSTATUS] ?? ['class' => 'badge text-secondary', 'bg' => 'bg-secondary'];

                return '<span class="' . $config['class'] . '" style="background-color: var(--bs-' . str_replace('bg-', '', $config['bg']) . '-bg-subtle); padding: 0.35rem 0.65rem; border-radius: 0.375rem; font-weight: 500;">'
                    . ($data->VSTATUS ?? '-') .
                    '</span>';
            })
            ->editColumn('VSTATUS_INVOICE', function ($data) {
                $color = $this->colorBadge($data->VSTATUS_INVOICE);
                // ambil verify po
                $verifyPo = VerifyNonPo::find($data->IID);

                $payload = null;

                if ($verifyPo) {
                    $payload = [[
                        "Billing_Stat_No" => $verifyPo->VBILLING_STATEMENT,
                        "Supplier"        => $verifyPo->VSUPPLIER_CODE,
                        "Currency"        => null,
                        "Order_No"        => $verifyPo->VUNIQUE_CODE,
                        "Reference_No"    => null,
                        "Invoice_No"      => $verifyPo->VINV_NO_SUPPLIER,
                        "Invoice_Date"    => $verifyPo->DINV_DATE,
                        "TaxCode"         => $verifyPo->VTAX_NUMBER,
                        "NetAmount"       => $verifyPo->INET_AMOUNT,
                        "TaxAmount"       => $verifyPo->VPPN,
                        "GrossAmount"     => $verifyPo->ITOTAL
                    ]];
                }

                $payloadJson = htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8');

                if ($color == 'danger') {
                    return '
                        <div class="d-inline-flex align-items-center gap-2">

                            <span class="badge bg-' . $color . ' px-3 py-2">
                                ' . $data->VSTATUS_INVOICE . '
                            </span>

                            <span class="resend-si d-inline-flex align-items-center justify-content-center bg-secondary text-white rounded"
                                style="cursor:pointer; width:30px; height:30px;"
                                data-id="' . $data->IID . '"
                                data-payload=\'' . $payloadJson . '\'
                                title="Resend SI">

                                <i class="ti tabler-reload" style="font-size:16px;"></i>

                            </span>

                        </div>
                    ';
                }

                return '<span class="badge bg-' . $color . '">'
                    . $data->VSTATUS_INVOICE .
                    '</span>';
            })

            ->addColumn('preview-pdf', function ($data) {
                $button = '';
                $url = "verify-non-po";
                if ($data->VSTATUS_INVOICE == 'PRELIMENARY') {
                    $button = ' <a
                            href="' . $url . '/preview-pdf/' . $data->IID . '"
                            target="_blank"
                            class="btn btn-sm btn-outline-primary"
                            title="Preview PDF"
                        >
                            <i class="ti tabler-file-type-pdf"></i>
                        </a>';
                }
                return $button;
            })
            ->addColumn('action', fn($data) => $this->getActionButton($data))
            ->editColumn('VINV_NO_SUPPLIER', function ($data) {
                return $data->VINV_NO_SUPPLIER ?? '-';
            })
            ->editColumn('DINV_DATE', function ($data) {
                return $data->DINV_DATE ? Carbon::parse($data->DINV_DATE)->format('d M Y') : '-';
            })
            ->editColumn('IDPP_PPH', function ($data) {
                return $data->IDPP_PPH ? $this->setCurrency($data->IDPP_PPH, 2) : '-';
            })
            ->editColumn('IAMOUNT', function ($data) {
                return $data->IAMOUNT ? $this->setCurrency($data->IAMOUNT, 2) : '-';
            })
            ->editColumn('VPPH', function ($data) {
                return $data->VPPH ?? '-';
            })
            ->editColumn('VDPP', function ($data) {
                return $data->VDPP ? $this->setCurrency($data->VDPP, 2) : '-';
            })
            ->editColumn('VPPN', function ($data) {
                return $data->VPPN ? $this->setCurrency($data->VPPN, 2) : '-';
            })
            ->editColumn('VTAX_NUMBER', function ($data) {
                return $data->VTAX_NUMBER ?? '-';
            })
            ->editColumn('DTAX_DATE', function ($data) {
                return $data->DTAX_DATE ? Carbon::parse($data->DTAX_DATE)->format('d M Y') : '-';
            })
            ->editColumn('ITOTAL_AMOUNT', function ($data) {
                return $data->ITOTAL_AMOUNT ? $this->setCurrency($data->ITOTAL_AMOUNT, 2) : '-';
            })
            ->editColumn('INET_AMOUNT', function ($data) {
                return $data->INET_AMOUNT ? $this->setCurrency($data->INET_AMOUNT, 2) : '-';
            })
            ->addColumn('PDF_TAX', function ($data) {
                if (!$data->VPDF_TAX) {
                    return '<span class="text-muted">-</span>';
                }

                $fileName = basename($data->VPDF_TAX);
                $fileUrl = asset('storage/' . $data->VPDF_TAX);

                return '<a href="' . $fileUrl . '" target="_blank" class="btn btn-sm btn-outline-primary" title="Download/View File">
                            <i class="ti tabler-file-type-pdf"></i>
                        </a>';
            })
            ->addColumn('PDF_INVOICE', function ($data) {
                if (!$data->VPDF_INVOICE) {
                    return '<span class="text-muted">-</span>';
                }

                $fileName = basename($data->VPDF_INVOICE);
                $fileUrl = asset('storage/' . $data->VPDF_INVOICE);

                return '<a href="' . $fileUrl . '" target="_blank" class="btn btn-sm btn-outline-primary" title="Download/View File">
                            <i class="ti tabler-file-type-pdf"></i>
                        </a>';
            })
            ->editColumn('VQRCODE', function ($data) {
                return $data->VQRCODE ?? '-';
            })
            ->editColumn('DSUBMITTED', function ($data) {
                return $data->DSUBMITTED ? Carbon::parse($data->DSUBMITTED)->format('d M Y H:i') : '-';
            })
            ->editColumn('DAPPROVED', function ($data) {
                return $data->DAPPROVED ? Carbon::parse($data->DAPPROVED)->format('d M Y H:i') : '-';
            })
            ->editColumn('DPLAN_PAY_DATE', function ($data) {
                return $data->DPLAN_PAY_DATE ? Carbon::parse($data->DPLAN_PAY_DATE)->format('d M Y') : '-';
            })
            ->editColumn('DCREA', function ($data) {
                return $data->DCREA ? Carbon::parse($data->DCREA)->format('d M Y H:i') : '-';
            })
            ->editColumn('DMODI', function ($data) {
                return $data->DMODI ? Carbon::parse($data->DMODI)->format('d M Y H:i') : '-';
            })
            ->rawColumns(['checkbox', 'action', 'VSTATUS', 'VSTATUS_INVOICE', 'PDF_TAX', 'PDF_INVOICE', 'preview-pdf'])
            ->setRowId('IID')
            ->filterColumn('DINV_DATE', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DINV_DATE', $keyword);
            })
            ->filterColumn('DTAX_DATE', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DTAX_DATE', $keyword);
            })
            ->filterColumn('DSUBMITTED', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DSUBMITTED', $keyword);
            })
            ->filterColumn('DAPPROVED', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DAPPROVED', $keyword);
            })
            ->filterColumn('DPLAN_PAY_DATE', function ($query, $keyword) {
                $this->applyDateRangeFilter($query, 'DPLAN_PAY_DATE', $keyword);
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
                    $query->whereAny(['VINV_NO_SUPPLIER', 'VTAX_NUMBER', 'VSTATUS'], 'ILIKE', "%{$keyword}%");
                }
            });
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder
     */
    public function query(VerifyNonPo $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->where("VSTATUS", 'submit');

        $user = Auth::user();
        if ($user?->supplierUser) {
            $supplierCode = trim((string) $user->supplierUser->VSUPPLIER_CODE);

            if ($supplierCode === '') {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('VSUPPLIER_CODE', $supplierCode);
            }
        }

        return $query->orderBy('DCREA', 'desc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('factwmf008-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'processing' => false,
                'orderCellsTop' => true,
                'columnDefs' => [
                    [
                        'className' => 'text-center text-nowrap',
                        'targets' => '_all'
                    ],
                    [
                        'width' => '60px',
                        'targets' => 0
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

                        ' . $this->getScriptForSearchRowCustom() . '
                    }
                ',
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
            Column::computed('checkbox')
                ->title('')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(60)
                ->addClass('text-center'),
            Column::make('VSTATUS_INVOICE')->title('Status Invoice'),
            Column::computed('preview-pdf')
                ->title('Preview PDF')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false),
            // ->width(60)
            // ->addClass('text-center'),
            Column::make('VBILLING_STATEMENT')->title('Billing Statement'),
            Column::make('VUNIQUE_CODE')->title('Unique Code'),
            Column::make('VINV_NO_SUPPLIER')->title('INV No Supplier'),
            Column::make('DINV_DATE')->title('INV Date'),
            Column::make('IDPP_PPH')->title('Total DPP PPH'),
            Column::make('INET_AMOUNT')->title('Amount'),
            Column::make('VPPH')->title('PPH'),
            Column::make('VDPP')->title('DPP'),
            Column::make('VPPN')->title('PPN'),
            Column::make('VTAX_NUMBER')->title('Tax Number'),
            Column::make('DTAX_DATE')->title('Tax Date'),
            Column::make('ITOTAL')->title('Total Amount'),
            Column::make('VSTATUS')->title('Status'),
            Column::computed('PDF_TAX')
                ->title('PDF Tax')
                ->exportable(false)
                ->printable(false)
                ->orderable(false),
            Column::computed('PDF_INVOICE')
                ->title('PDF Invoice')
                ->exportable(false)
                ->printable(false)
                ->orderable(false),
            Column::make('VQRCODE')->title('QR Code'),
            Column::make('DSUBMITTED')->title('Submitted Date'),
            Column::make('DAPPROVED')->title('Approved Date'),
            Column::make('DPLAN_PAY_DATE')->title('Plan Pay Date'),
            Column::make('DCREA')->title('Created At'),
            Column::make('DMODI')->title('Modified At'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->searchable(false)
                ->width(100)
                ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'FACTWMF008_' . date('YmdHis');
    }

    private function getActionButton($data): string
    {
        $buttons = [];

        if (Gate::allows('update', $data) && $data->VSTATUS_INVOICE == 'WAITING') {
            $buttons[] =
                [
                    'action' => 'edit',
                    'service' => 'FACTWMF008-Update',
                    'icon' => 'pencil',
                    'class' => 'edit-non-po',
                ];
        }

        if (Gate::allows('view', $data)) {
            $buttons[] =
                [
                    'action' => 'delete',
                    'service' => 'FACTWMF008-Update',
                    'icon' => 'eye',
                    'class' => 'view-mode-non-po',
                    'title' => 'Read Only'
                ];
        }


        return $this->actionButtons($data, $buttons);
    }

    /**
     * Get script for search row with custom filters
     */
    private function getScriptForSearchRowCustom($skippedColumns = [])
    {
        $skippedColumns = implode(',', $skippedColumns);
        $script = '
        var tfoot = $(table.table().footer());
        var thead = $(table.table().header());
        var headerCells = thead.find("tr:first th");

        if (thead.find("tr").length < 2) {
            tfoot.find("tr").clone().appendTo(thead);
        }

        $(thead).find("tr:eq(1) th").each(function(index) {
            var column = table.column(index);
            var title = $(headerCells[index]).text().trim();
            var skippedColumns = [' . $skippedColumns . '];
            var isSecondColumn = index === 1;

            if (skippedColumns.includes(index) || isSecondColumn || index === $(thead).find("tr:eq(1) th").length - 1) {
                $(this).html("");
                return;
            }

            if (title.toLowerCase() === "status") {
                var select = $("<select>", {
                    "class": "form-select form-select-sm",
                });

                select.append($("<option>", {
                    value: "",
                    text: "All Status"
                }));

                var statuses = ["CREATED", "SUBMITTED", "APPROVED", "DECLINED", "PAID"];
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
            else if (/date/i.test(title)) {
                var input = $("<input>", {
                    "class": "form-control form-control-sm daterange-input",
                    "placeholder": "Select date",
                    "readonly": true
                });

                $(this).html(input);

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

    private function setCurrency($data)
    {
        return number_format((int) $data, 0, ',', '.');
    }

    private function colorBadge($status)
    {
        $color = '';
        switch ($status) {
            case 'PAID':
                $color = 'success';
                break;

            case 'PRELIMENARY':
                $color = 'bg-blue';
                break;

            case 'FAILED':
                $color = 'danger';
                break;

            case 'REJECTED':
                $color = 'dark';
                break;

            case 'WAITING':
                $color = 'info';
                break;

            case 'ESCALATED':
                $color = 'secondary';
                break;

            default:
                $color = 'warning';
                break;
        }

        return $color;
    }
}
