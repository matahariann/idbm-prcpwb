<?php

namespace App\Exports\FACTWM\FACTWM02;

use App\Models\FACTWM01\FACTWM_MSHCONFIGURATION;
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FACTWMF006 implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function __construct(
        private ?Request $request = null
    ) {}

    public function collection()
    {
        $config = FACTWM_MSHCONFIGURATION::where('VVARIABLE', 'n_day')->pluck('VVALUE', 'VVARIABLE');
        $dataShowFrom = ! $config->isEmpty() ? (int) $config['n_day'] : 0;

        $query = FACTWM_TRHGR_NOTES::with('details')
            ->where('DCREA', '<=', Carbon::now()->subDays($dataShowFrom))
            ->orderBy('DCREA', 'desc');

        $user = Auth::user();
        if ($user?->supplierUser) {
            $supplierCode = trim((string) $user->supplierUser->VSUPPLIER_CODE);

            if ($supplierCode === '') {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('VVENDOR_CODE', $supplierCode);
            }
        }

        $this->applyFilters($query);

        return $query
            ->get()
            ->flatMap(function ($grNote) {
                if ($grNote->details->isEmpty()) {
                    return [[
                        'VREF_TYPE' => $grNote->VREF_TYPE,
                        'VRETURN_REF' => $grNote->VRETURN_REF,
                        'VGR_NUMBER' => $grNote->VGR_NUMBER,
                        'VDELIVERY_NUMBER' => $grNote->VDELIVERY_NUMBER,
                        'VPO_NUMBER' => $grNote->VPO_NUMBER,
                        'VVENDOR_NAME' => $grNote->VVENDOR_NAME,
                        'VSTATUS' => $grNote->VSTATUS,
                        'VCURRENCY' => $grNote->VCURRENCY,
                        'VMATERIAL_CODE' => null,
                        'VDESCRIPTION' => null,
                        'IQTY' => null,
                        'UOM' => null,
                        'VPRICE' => null,
                        'VAMOUNT' => null,
                        'DGR' => $grNote->DGR ? Carbon::parse($grNote->DGR)->format('d M Y H:i') : null,
                        'DAPPROVE' => $grNote->DAPPROVE ? Carbon::parse($grNote->DAPPROVE)->format('d M Y H:i') : null,
                        'DDISPUTE' => $grNote->DDISPUTE ? Carbon::parse($grNote->DDISPUTE)->format('d M Y H:i') : null,
                        'DSYNC' => $grNote->DSYNC ? Carbon::parse($grNote->DSYNC)->format('d M Y H:i') : null,
                        'VCREA' => $grNote->VCREA,
                        'DCREA' => $grNote->DCREA ? Carbon::parse($grNote->DCREA)->format('d M Y H:i') : null,
                        'VMODI' => $grNote->VMODI,
                        'DMODI' => $grNote->DMODI ? Carbon::parse($grNote->DMODI)->format('d M Y H:i') : null,
                    ]];
                }

                return $grNote->details->map(function ($detail, $index) use ($grNote) {
                    return [
                        'VREF_TYPE' => $grNote->VREF_TYPE,
                        'VRETURN_REF' => $grNote->VRETURN_REF,
                        'VGR_NUMBER' => $grNote->VGR_NUMBER,
                        'VDELIVERY_NUMBER' => $grNote->VDELIVERY_NUMBER,
                        'VPO_NUMBER' => $grNote->VPO_NUMBER,
                        'VVENDOR_NAME' => $grNote->VVENDOR_NAME,
                        'VSTATUS' => $grNote->VSTATUS,
                        'VCURRENCY' => $grNote->VCURRENCY,
                        'VMATERIAL_CODE' => $detail->VMATERIAL_CODE,
                        'VDESCRIPTION' => $detail->VDESCRIPTION,
                        'IQTY' => $detail->IQTY,
                        'UOM' => $detail->UOM,
                        'VPRICE' => $detail->VPRICE,
                        'VAMOUNT' => $detail->VAMOUNT,
                        'DGR' => $grNote->DGR ? Carbon::parse($grNote->DGR)->format('d M Y H:i') : null,
                        'DAPPROVE' => $grNote->DAPPROVE ? Carbon::parse($grNote->DAPPROVE)->format('d M Y H:i') : null,
                        'DDISPUTE' => $grNote->DDISPUTE ? Carbon::parse($grNote->DDISPUTE)->format('d M Y H:i') : null,
                        'DSYNC' => $grNote->DSYNC ? Carbon::parse($grNote->DSYNC)->format('d M Y H:i') : null,
                        'VCREA' => $grNote->VCREA,
                        'DCREA' => $grNote->DCREA ? Carbon::parse($grNote->DCREA)->format('d M Y H:i') : null,
                        'VMODI' => $grNote->VMODI,
                        'DMODI' => $grNote->DMODI ? Carbon::parse($grNote->DMODI)->format('d M Y H:i') : null,
                    ];
                })->toArray();
            });
    }

    private function applyFilters($query): void
    {
        if (! $this->request) {
            return;
        }

        $globalSearch = trim((string) $this->request->input('search.value', ''));
        if ($globalSearch !== '') {
            $query->where(function ($builder) use ($globalSearch) {
                foreach ($this->searchableColumns() as $column) {
                    $builder->orWhere($column, 'ilike', "%{$globalSearch}%");
                }
            });
        }

        foreach ((array) $this->request->input('columns', []) as $column) {
            $columnName = $column['data'] ?? null;
            $searchValue = trim((string) data_get($column, 'search.value', ''));

            if ($columnName === null || $searchValue === '' || ! in_array($columnName, $this->searchableColumns(), true)) {
                continue;
            }

            if (in_array($columnName, $this->dateColumns(), true) && str_contains($searchValue, ' to ')) {
                [$startDate, $endDate] = explode(' to ', $searchValue, 2);
                $query->whereBetween($columnName, [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ]);

                continue;
            }

            $query->where($columnName, 'ilike', "%{$searchValue}%");
        }
    }

    private function searchableColumns(): array
    {
        return [
            'VSTATUS',
            'VREF_TYPE',
            'VRETURN_REF',
            'VGR_NUMBER',
            'VDELIVERY_NUMBER',
            'VPO_NUMBER',
            'VVENDOR_CODE',
            'VVENDOR_NAME',
            'DGR',
            'DAPPROVE',
            'DDISPUTE',
            'DSYNC',
            'DCREA',
            'DMODI',
        ];
    }

    private function dateColumns(): array
    {
        return [
            'DGR',
            'DAPPROVE',
            'DDISPUTE',
            'DSYNC',
            'DCREA',
            'DMODI',
        ];
    }

    public function headings(): array
    {
        return [
            'Reference Type',
            'Return Reference',
            'GR Number',
            'Delivery Number',
            'PO Number',
            'Vendor Name',
            'Status',
            'Currency',
            'Material Code',
            'Description',
            'Quantity',
            'UOM',
            'Price',
            'Amount',
            'GR Date',
            'Approve Date',
            'Dispute Date',
            'Sync Date',
            'Created By',
            'Created Date',
            'Modified By',
            'Modified Date',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 18,
            'C' => 15,
            'D' => 15,
            'E' => 18,
            'F' => 15,
            'G' => 20,
            'H' => 12,
            'I' => 12,
            'J' => 22,
            'K' => 18,
            'L' => 12,
            'M' => 12,
            'N' => 15,
            'O' => 15,
            'P' => 18,
            'Q' => 18,
            'R' => 18,
            'S' => 18,
            'T' => 15,
            'U' => 18,
            'V' => 15,
            'W' => 18,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ]
                ],
            ],
            // Data rows styling
            'A' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
