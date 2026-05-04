<?php

namespace App\Exports\FACTWM\FACTWM03;

use App\Models\FACTWM02\FACTWM_TRHGR_NOTES;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FACTWMF010 implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function __construct(
        private ?Request $request = null
    ) {}

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = FACTWM_TRHGR_NOTES::with(['details'])
            ->when(
                Auth::user()?->supplierUser?->VSUPPLIER_CODE,
                fn($q, $supplierCode) =>
                $q->where('VVENDOR_CODE', $supplierCode)
            )
            ->whereNull('DDELETE');

        $this->applyFilters($query);

        $query->orderBy('VGR_NUMBER', 'desc');

        $grNotes = $query->get();

        $rows = collect();

        foreach ($grNotes as $grNote) {
            // Hitung aging GRN (dari DGR sampai sekarang)
            $agingGrn = null;
            if ($grNote->DGR) {
                $grDate = Carbon::parse($grNote->DGR);
                $agingGrn = $grDate->diffInDays(Carbon::now());
            }

            // Hitung total amount before PPH dari detail items
            $amountBeforePph = 0;
            if ($grNote->details && $grNote->details->count() > 0) {
                foreach ($grNote->details as $detail) {
                    $amountBeforePph += floatval($detail->VAMOUNT ?? 0);
                }
            }

            // Jika ada details (child)
            if ($grNote->details && $grNote->details->count() > 0) {
                foreach ($grNote->details as $detail) {
                    $row = [];

                    // Kolom Parent - tampil di semua row untuk parent yang sama
                    $row[] = $grNote->VSTATUS ?? '-';
                    $row[] = $grNote->VREF_TYPE ?? '-';
                    $row[] = $grNote->VRETURN_REF ?? '-';
                    $row[] = $grNote->VGR_NUMBER ?? '-';
                    $row[] = $grNote->DGR ? Carbon::parse($grNote->DGR)->format('d M Y') : '-';
                    $row[] = $grNote->VDELIVERY_NUMBER ?? '-';
                    $row[] = $grNote->VPO_NUMBER ?? '-';
                    $row[] = $detail->VCURRENCY ?? '-'; // Currency dari detail
                    $row[] = $grNote->VVENDOR_CODE ?? '-';
                    $row[] = $grNote->VVENDOR_NAME ?? '-';

                    // Kolom Child/Detail
                    $row[] = $detail->VMATERIAL_CODE ?? '-';
                    $row[] = $detail->VDESCRIPTION ?? '-';
                    $row[] = floatval($detail->IQTY ?? 0);
                    $row[] = floatval($detail->VPRICE ?? 0);
                    $row[] = $detail->VCURRENCY ?? '-';

                    // Subtotal
                    $subtotal = floatval($detail->VAMOUNT ?? 0);
                    $row[] = $subtotal;

                    // DPP Nilai Lain (placeholder, sesuaikan dengan logic bisnis)
                    $row[] = round((11 / 12) * floatval($detail->VAMOUNT ?? 0), 2);

                    // PPN (placeholder, sesuaikan dengan logic bisnis - biasanya 12% dari subtotal)
                    $ppn = round(((11 / 12) * floatval($detail->VAMOUNT ?? 0)) * 0.12, 2); // Contoh 12% PPN
                    $row[] = $ppn;

                    // Kolom Parent lanjutan - tampil di semua row
                    $row[] = $amountBeforePph;
                    $row[] = $agingGrn !== null ? round($agingGrn, 0) . ' days' : '-';

                    $rows->push($row);
                }
            } else {
                // Jika tidak ada details, tampilkan parent saja dengan kolom child kosong
                $row = [
                    $grNote->VSTATUS ?? '-',
                    $grNote->VREF_TYPE ?? '-',
                    $grNote->VRETURN_REF ?? '-',
                    $grNote->VGR_NUMBER ?? '-',
                    $grNote->DGR ? Carbon::parse($grNote->DGR)->format('d M Y') : '-',
                    $grNote->VDELIVERY_NUMBER ?? '-',
                    $grNote->VPO_NUMBER ?? '-',
                    '-',
                    $grNote->VVENDOR_CODE ?? '-',
                    $grNote->VVENDOR_NAME ?? '-',
                    '-', // Part No
                    '-', // Description
                    0,   // Qty
                    0,   // Price
                    '-', // Currency
                    0,   // Subtotal
                    0,   // DPP Nilai Lain
                    0,   // PPN
                    $amountBeforePph,
                    $agingGrn !== null ? $agingGrn . ' days' : '-',
                ];
                $rows->push($row);
            }
        }

        return $rows;
    }

    private function applyFilters($query): void
    {
        if (! $this->request) {
            return;
        }

        $globalSearch = trim((string) $this->request->input('search.value', ''));
        if ($globalSearch !== '') {
            $query->where(function ($builder) use ($globalSearch) {
                foreach ($this->globalSearchableColumns() as $column) {
                    $builder->orWhere($column, 'like', "%{$globalSearch}%");
                }
            });
        }

        foreach ((array) $this->request->input('columns', []) as $column) {
            $columnName = $column['data'] ?? null;
            $columnAlias = $column['name'] ?? null;
            $searchValue = trim((string) data_get($column, 'search.value', ''));

            if ($searchValue === '') {
                continue;
            }

            if ($columnAlias === 'status_grn_raw' || $columnName === 'status_grn_raw') {
                $query->where('VSTATUS', trim($searchValue, '^$'));
                continue;
            }

            if ($columnName === 'DGR') {
                if (str_contains($searchValue, ' to ')) {
                    [$startDate, $endDate] = explode(' to ', $searchValue, 2);
                    $query->whereBetween('DGR', [
                        Carbon::parse(trim($startDate))->startOfDay(),
                        Carbon::parse(trim($endDate))->endOfDay(),
                    ]);
                } else {
                    $query->whereDate('DGR', Carbon::parse($searchValue));
                }
                continue;
            }

            if (in_array($columnName, $this->columnSearchableColumns(), true)) {
                $query->where($columnName, 'like', "%{$searchValue}%");
            }
        }
    }

    private function globalSearchableColumns(): array
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
        ];
    }

    private function columnSearchableColumns(): array
    {
        return [
            'VREF_TYPE',
            'VRETURN_REF',
            'VGR_NUMBER',
            'VDELIVERY_NUMBER',
            'VPO_NUMBER',
            'VVENDOR_CODE',
            'VVENDOR_NAME',
        ];
    }

    public function headings(): array
    {
        return [
            // Header Row 1 - Group Headers
            ['GRN Identity', '', '', '', '', '', '', 'Suppl Identity', '', '', 'Detail Barang', '', '', '', '', '', '', '', 'Amount & Aging', ''],
            // Header Row 2 - Column Headers
            [
                'Status GRN',
                'Reference Type',
                'Return Reference',
                'GRN No',
                'GRN Date',
                'Delivery No',
                'PO Number',
                'Curr',
                'Vendor Code',
                'Vendor Name',
                'Part No',
                'Desc',
                'Qty',
                'Price',
                'Curr',
                'Subtotal',
                'DPP Nilai Lain',
                'PPN',
                'Amount Before Pph',
                'Aging GRN'
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge cells untuk header grup
        $sheet->mergeCells('A1:G1'); // GRN Identity
        $sheet->mergeCells('H1:J1'); // Suppl Identity
        $sheet->mergeCells('K1:R1'); // Detail Barang
        $sheet->mergeCells('S1:T1'); // Amount & Aging

        // Style untuk header grup (row 1) - Parent (Ungu)
        $sheet->getStyle('A1:J1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '9B59B6']
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Style untuk header grup (row 1) - Child (Hijau)
        $sheet->getStyle('K1:R1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '27AE60']
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Style untuk header grup (row 1) - Parent lanjutan (Ungu)
        $sheet->getStyle('S1:T1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '9B59B6']
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Style untuk header kolom (row 2) - Parent (Ungu muda)
        $sheet->getStyle('A2:J2')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D7BDE2']
            ],
            'font' => [
                'bold' => true,
                'size' => 10
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Style untuk header kolom (row 2) - Child (Hijau muda)
        $sheet->getStyle('K2:R2')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'A9DFBF']
            ],
            'font' => [
                'bold' => true,
                'size' => 10
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Style untuk header kolom (row 2) - Parent lanjutan (Ungu muda)
        $sheet->getStyle('S2:T2')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D7BDE2']
            ],
            'font' => [
                'bold' => true,
                'size' => 10
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Set row height untuk header
        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(30);

        // Style untuk data rows
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A3:T' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Text alignment untuk kolom angka (rata kanan)
        $sheet->getStyle('M3:M' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('N3:N' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('P3:S' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Format kolom Qty sebagai number tanpa desimal
        $sheet->getStyle('M3:M' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);

        // Format kolom angka lainnya dengan 2 desimal
        $sheet->getStyle('N3:N' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle('P3:S' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Status GRN
            'B' => 16,  // Reference Type
            'C' => 18,  // Return Reference
            'D' => 15,  // GRN No
            'E' => 14,  // GRN Date
            'F' => 15,  // Delivery No
            'G' => 15,  // PO Number
            'H' => 15,  // Vendor Code
            'I' => 25,  // Vendor Name
            'J' => 25,  // Vendor Name
            'K' => 18,  // Part No
            'L' => 35,  // Desc
            'M' => 12,  // Qty
            'N' => 15,  // Price
            'O' => 8,   // Curr
            'P' => 16,  // Subtotal
            'Q' => 16,  // DPP Nilai Lain
            'R' => 16,  // PPN
            'S' => 20,  // Amount Before Pph
            'T' => 18,  // Aging GRN
        ];
    }
}
