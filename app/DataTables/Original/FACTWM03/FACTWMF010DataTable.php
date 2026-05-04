<?php

namespace App\DataTables\Original\FACTWM03;

use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION;
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES;
use App\Traits\DataTableTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

final class FACTWMF010DataTable extends DataTable
{
    use DataTableTrait;

    /**
     * Build the DataTable class.
     */
    public function dataTable($query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('expand', function ($row) {
                return '<button class="btn btn-icon expand-row" data-id="' . $row->IID . '">
                    <i class="ti tabler-plus"></i>
                </button>';
            })
            // ->addColumn('action', fn($data) => $this->getActionButton($data))
            ->editColumn('VREF_TYPE', function ($row) {
                $type = strtoupper($row->VREF_TYPE ?? '-');
                $typeClass = match ($type) {
                    'RECEIPT' => 'badge bg-label-success',
                    'RETURN', 'RETUR' => 'badge bg-label-danger',
                    default => 'badge bg-label-secondary',
                };

                return '<span class="' . $typeClass . '">' . $type . '</span>';
            })
            ->editColumn('VRETURN_REF', function ($row) {
                return $row->VRETURN_REF ?: '-';
            })
            ->editColumn('VSTATUS', function ($row) {
                $status = explode('-', $row->VSTATUS ?? 'NEW')[0];
                $statusClass = match ($status) {
                    'APPROVED' => 'badge text-success',
                    'NEW'      => 'badge text-secondary',
                    'DISPUTED' => 'badge text-danger',
                    'CLOSED'   => 'badge text-info',
                    default    => 'badge text-secondary'
                };

                $bgClass = match ($status) {
                    'APPROVED' => 'bg-success',
                    'NEW'      => 'bg-secondary',
                    'DISPUTED' => 'bg-danger',
                    'CLOSED'   => 'bg-info',
                    default    => 'bg-secondary'
                };

                return '<span class="' . $statusClass . '" style="background-color: var(--bs-' . str_replace('bg-', '', $bgClass) . '-bg-subtle); padding: 0.35rem 0.65rem; border-radius: 0.375rem; font-weight: 500;">'
                    . ($status ?? '-') .
                    '</span>';
            })
            ->addColumn('status_grn_raw', function ($row) {
                return $row->VSTATUS ?? 'New';
            })
            ->editColumn('DGR', function ($row) {
                return $row->DGR ? Carbon::parse($row->DGR)->format('Y-m-d') : '-';
            })
            ->addColumn('ammount_before_pph', function ($row) {
                $total = $row->details->sum(function ($detail) {
                    return floatval($detail->VAMOUNT ?? 0);
                });
                return number_format($total, 0, ',', '.');
            })
            ->addColumn('aging_grn', function ($row) {
                if (!$row->DGR) return 0;
                return round(Carbon::now()->diffInDays(Carbon::parse($row->DGR)), 0);
            })
            ->addColumn('items_json', function ($row) {
                $items = $row->details->map(function ($detail) use ($row) {
                    $confData = FACTWM_MSHCONFIGURATION::whereIn('VVARIABLE', ['ppn', 'rumus_dpp'])->pluck('VVALUE');
                    [$one, $two] = array_map('floatval', explode('/', $confData[1]));
                    return [
                        'part_number'    => $detail->VMATERIAL_CODE,
                        'description'    => $detail->VDESCRIPTION,
                        'qty'            => $detail->IQTY,
                        'price'          => floatval($detail->VPRICE ?? 0),
                        'currency'       => $detail->VCURRENCY ?? '-',
                        'sub_total'      => floatval($detail->VAMOUNT ?? 0),
                        'dpp_nilai_lain' => round(($one / $two) * floatval($detail->VAMOUNT ?? 0), 2),
                        'ppn'            => round((((int)$confData[1]) * floatval($detail->VAMOUNT ?? 0)) * ((int)$confData[0] / 100), 2),
                    ];
                });
                return json_encode($items);
            })
            ->rawColumns(['expand', 'action', 'VSTATUS', 'VREF_TYPE'])
            ->setRowId('IID')
            ->filterColumn('status_grn_raw', function ($query, $keyword) {
                $status = trim($keyword, '^$');
                $query->where('VSTATUS', $status);
            })
            ->filterColumn('VREF_TYPE', function ($query, $keyword) {
                $query->where('VREF_TYPE', 'like', "%{$keyword}%");
            })
            ->filterColumn('VRETURN_REF', function ($query, $keyword) {
                $query->where('VRETURN_REF', 'like', "%{$keyword}%");
            })
            ->filterColumn('VGR_NUMBER', function ($query, $keyword) {
                $query->where('VGR_NUMBER', 'like', "%{$keyword}%");
            })
            // FIX: tambahkan filterColumn DGR agar daterange search bekerja di server-side
            ->filterColumn('DGR', function ($query, $keyword) {
                if (str_contains($keyword, ' to ')) {
                    [$start, $end] = explode(' to ', $keyword);
                    $query->whereBetween('DGR', [
                        Carbon::parse(trim($start))->startOfDay(),
                        Carbon::parse(trim($end))->endOfDay(),
                    ]);
                } else {
                    $query->whereDate('DGR', Carbon::parse(trim($keyword)));
                }
            })
            ->filterColumn('VDELIVERY_NUMBER', function ($query, $keyword) {
                $query->where('VDELIVERY_NUMBER', 'like', "%{$keyword}%");
            })
            ->filterColumn('VPO_NUMBER', function ($query, $keyword) {
                $query->where('VPO_NUMBER', 'like', "%{$keyword}%");
            })
            ->filterColumn('VVENDOR_CODE', function ($query, $keyword) {
                $query->where('VVENDOR_CODE', 'like', "%{$keyword}%");
            })
            ->filterColumn('VVENDOR_NAME', function ($query, $keyword) {
                $query->where('VVENDOR_NAME', 'like', "%{$keyword}%");
            });
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(FACTWM_TRHGR_NOTES $model)
    {
        return $model->newQuery()
            ->with('details')
            ->whereNull('DDELETE')
            ->when(
                Auth::user()?->supplierUser?->VSUPPLIER_CODE,
                fn($q, $supplierCode) =>
                $q->where('VVENDOR_CODE', $supplierCode)
            )
            ->select('FACTWM_TRHGR_NOTES.*');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('factwmf010-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'processing'    => true,
                'serverSide'    => true,
                'orderCellsTop' => true,
                'order'         => [[5, 'desc']],
                'dom'           => 'r' .
                    "<'table-responsive border-top'tr>" .
                    "<'d-flex align-items-center justify-content-center justify-content-lg-between flex-wrap gap-2 text-center px-6 mt-6'ip>",
                'initComplete' => '
                    function () {
                        var table = this.api();
                        ' . $this->getScriptForSearchRowCustom(false) . '

                        function decodeHtml(html) {
                            const txt = document.createElement("textarea");
                            txt.innerHTML = html;
                            return txt.value;
                        }

                        $("#factwmf010-table tbody").on("click", ".expand-row", function() {
                            var btn  = $(this);
                            var tr   = btn.closest("tr");
                            var row  = table.row(tr);
                            var icon = btn.find("i");

                            if (row.child.isShown()) {
                                row.child.hide();
                                icon.removeClass("tabler-minus").addClass("tabler-plus");
                                tr.removeClass("shown");
                            } else {
                                var data  = row.data();
                                var items = [];

                                try {
                                    items = typeof data.items_json === "string"
                                        ? JSON.parse(decodeHtml(data.items_json))
                                        : data.items_json || [];
                                } catch(e) {
                                    console.error("Error parsing items:", e);
                                    items = [];
                                }

                                row.child(formatChildRow(items)).show();
                                icon.removeClass("tabler-plus").addClass("tabler-minus");
                                tr.addClass("shown");
                            }
                        });

                        function formatChildRow(items) {
                            if (!items || items.length === 0) {
                                return `
                                    <div class="p-3 bg-light text-center">
                                        <p class="text-muted mb-0">No items available</p>
                                    </div>
                                `;
                            }

                            var html = `
                                <div class="p-3 bg-light">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>PART NO</th>
                                                <th>DESCRIPTION</th>
                                                <th class="text-end">QTY</th>
                                                <th class="text-end">PRICE</th>
                                                <th>CURR</th>
                                                <th class="text-end">SUBTOTAL</th>
                                                <th class="text-end">DPP NILAI LAIN</th>
                                                <th class="text-end">PPN</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;

                            items.forEach(function(item) {
                                html += `
                                    <tr>
                                        <td>${item.part_number || "-"}</td>
                                        <td>${item.description || "-"}</td>
                                        <td class="text-end">${item.qty || 0}</td>
                                        <td class="text-end">${new Intl.NumberFormat("id-ID").format(item.price || 0)}</td>
                                        <td>${item.currency || "-"}</td>
                                        <td class="text-end">${new Intl.NumberFormat("id-ID").format(item.sub_total || 0)}</td>
                                        <td class="text-end">${new Intl.NumberFormat("id-ID").format(item.dpp_nilai_lain || 0)}</td>
                                        <td class="text-end">${new Intl.NumberFormat("id-ID").format(item.ppn || 0)}</td>
                                    </tr>
                                `;
                            });

                            html += `
                                        </tbody>
                                    </table>
                                </div>
                            `;

                            return html;
                        }
                    }
                ',
                'drawCallback' => '
                    function() {
                        var existingTooltips = document.querySelectorAll(\'[data-bs-toggle="tooltip"]\');
                        existingTooltips.forEach(function(el) {
                            var tooltip = bootstrap.Tooltip.getInstance(el);
                            if (tooltip) { tooltip.dispose(); }
                        });

                        var tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[data-bs-toggle="tooltip"]\'));
                        tooltipTriggerList.map(function (tooltipTriggerEl) {
                            return new bootstrap.Tooltip(tooltipTriggerEl, {
                                html: true,
                                delay: { show: 300, hide: 100 },
                                customClass: "custom-tooltip-large"
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
            Column::computed('expand')
                ->title('')
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(30)
                ->addClass('text-center'),
            Column::make('VSTATUS')->title('STATUS GRN')->addClass('text-nowrap')->searchable(false),
            Column::computed('status_grn_raw')->name('status_grn_raw')->visible(false)->searchable(true),
            Column::make('VREF_TYPE')->title('REFERENCE TYPE')->addClass('text-nowrap'),
            Column::make('VRETURN_REF')->title('RETURN REFERENCE')->addClass('text-nowrap'),
            Column::make('VGR_NUMBER')->title('GRN NO')->addClass('text-nowrap'),
            Column::make('DGR')->title('GRN DATE')->addClass('text-nowrap'),
            Column::make('VDELIVERY_NUMBER')->title('DELIVERY NO')->addClass('text-nowrap'),
            Column::make('VPO_NUMBER')->title('PO NUMBER')->addClass('text-nowrap'),
            Column::make('VVENDOR_CODE')->title('VENDOR CODE')->addClass('text-nowrap'),
            Column::make('VVENDOR_NAME')->title('VENDOR NAME')->addClass('text-nowrap'),
            Column::computed('ammount_before_pph')->title('AMOUNT BEFORE PPH')->addClass('text-nowrap text-end')->orderable(false)->searchable(false),
            Column::computed('aging_grn')->title('AGING GRN')->addClass('text-nowrap text-end')->orderable(false)->searchable(false),
            Column::computed('items_json')->visible(false)->searchable(false)->orderable(false),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'FACTWMF010_' . date('YmdHis');
    }

    /**
     * Get script for search row with custom filters
     */
    private function getScriptForSearchRowCustom($skipLastColumn = true)
    {
        $script = '
        var thead = $(table.table().header());
        var headerCells = thead.find("tr:first th");

        if (thead.find("tr").length < 2) {
            var searchRow = $("<tr></tr>");
            thead.find("tr:first th").each(function () {
                searchRow.append("<th></th>");
            });
            thead.append(searchRow);
        }

        $(thead).find("tr:eq(1) th").each(function(index) {
            var title = $(headerCells[index]).text().trim();

            // ─── ROOT CAUSE FIX ───────────────────────────────────────────────────────
            // Gunakan index + ":visible" agar index DOM <th> dipetakan ke kolom DataTables
            // yang tepat. Tanpa ini, kolom hidden (status_grn_raw di index 2, items_json
            // di index 11) menyebabkan semua kolom sesudahnya mendapat index yang salah,
            // sehingga search dikirim ke kolom yang berbeda dari yang ditampilkan.
            var column = table.column(index + ":visible");
            // ─────────────────────────────────────────────────────────────────────────

            // Skip kolom expand (index 0 DOM)
            if (index === 0) {
                $(this).html("");
                return;
            }

            // Kolom tanpa judul (computed seperti AMOUNT, AGING) → kosongkan
            if (title === "") {
                $(this).html("");
                return;
            }

            // Status GRN → dropdown, search diarahkan ke hidden kolom status_grn_raw
            if (title.toLowerCase() === "status grn") {
                var select = $("<select>", { "class": "form-select form-select-sm" });
                select.append($("<option>", { value: "", text: "All Status" }));
                ["APPROVED", "NEW", "DISPUTED", "CLOSED"].forEach(function(status) {
                    select.append($("<option>", { value: status, text: status }));
                });
                $(this).html(select);

                select.on("change", function() {
                    var val = $(this).val();
                    var statusRawIndex = table.column("status_grn_raw:name").index();
                    if (val) {
                        table.column(statusRawIndex).search("^" + val + "$", true, false).draw();
                    } else {
                        table.column(statusRawIndex).search("").draw();
                    }
                });
                return;
            }

            // Kolom mengandung "Date" → daterangepicker
            if (/date/i.test(title)) {
                var dateInput = $("<input>", {
                    "class": "form-control form-control-sm daterange-input",
                    "placeholder": "Select date",
                    "readonly": true
                });
                $(this).html(dateInput);

                dateInput.daterangepicker({
                    autoUpdateInput: false,
                    locale: { cancelLabel: "Clear", format: "DD MMM YYYY" }
                });

                dateInput.on("apply.daterangepicker", function(ev, picker) {
                    var startDate = picker.startDate.format("YYYY-MM-DD");
                    var endDate   = picker.endDate.format("YYYY-MM-DD");
                    column.search(startDate + " to " + endDate).draw();
                    $(this).val(picker.startDate.format("DD MMM YYYY") + " - " + picker.endDate.format("DD MMM YYYY"));
                });

                dateInput.on("cancel.daterangepicker", function() {
                    column.search("").draw();
                    $(this).val("");
                });
                return;
            }

            // Default: text input untuk kolom lainnya
            var textInput = $("<input>", {
                "class": "form-control form-control-sm",
                "placeholder": "Search..."
            });
            $(this).html(textInput);

            textInput.on("keyup", function() {
                column.search(textInput.val()).draw();
            });
        });
        ';

        return $script;
    }
}
