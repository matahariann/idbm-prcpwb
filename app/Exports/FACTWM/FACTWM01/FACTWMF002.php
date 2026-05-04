<?php

namespace App\Exports\FACTWM\FACTWM01;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER as Supplier;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FACTWMF002 implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $selected;
    protected array $rows = [];

    public function __construct($selected = [])
    {
        $this->selected = $selected;
    }

    public function collection()
    {
        $query = Supplier::with('methods');

        if (!empty($this->selected)) {
            $query->whereIn('IID', $this->selected);
        }

        return $query->get();
    }

    public function map($supplier): array
    {
        if (!isset($this->rows[$supplier->IID])) {

            // Parent row
            $this->rows[$supplier->IID] = [
                [
                    'type' => 'parent',
                    'data' => [
                        $supplier->VSUPPLIER_CODE,
                        $supplier->VNAME,
                        $supplier->VNPWP ?? '-',
                        $supplier->VNIK ?? '-',
                        $supplier->BPKP ? 'PKP' : 'Non-PKP',
                        '',
                        $supplier->VADDRESS,
                    ]
                ]
            ];

            // Child rows
            foreach ($supplier->methods as $i => $method) {
                $this->rows[$supplier->IID][] = [
                    'type' => 'child',
                    'data' => [
                        $i + 1,
                        $method->VUSERNAME ?? '-',
                        $method->VNAME,
                        $method->VDESCRIPTION ?? '-',
                        $method->VMETHOD_ID,
                        $method->BMETHOD_DEFAULT ? 'Default' : '',
                        '',
                    ]
                ];
            }
        }

        return []; // tidak dipakai langsung
    }


    public function headings(): array
    {
        return [
            'VENDOR CODE',
            'VENDOR NAME',
            'NPWP',
            'NIK',
            'STATUS PKP',
            'TERM OF PAYMENT',
            'ADDRESS',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Apply bold font to the header row
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        // Add background color to header
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Add borders to header
        $sheet->getStyle('A1:G1')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,  // Vendor Code / NO
            'B' => 30,  // Vendor Name / USERNAME
            'C' => 25,  // NPWP / NAME
            'D' => 20,  // NIK / POSITION
            'E' => 15,  // Status PKP / TYPE
            'F' => 20,  // Term of Payment / COMM
            'G' => 40,  // Address
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $row = 2;

                foreach ($this->rows as $group) {
                    foreach ($group as $item) {

                        $sheet->fromArray($item['data'], null, "A{$row}");

                        if ($item['type'] === 'parent') {
                            $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);
                            $sheet->getStyle("A{$row}:G{$row}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setARGB('FFF0F0F0');
                        } else {
                            $sheet->getStyle("A{$row}:G{$row}")->getFont()->setItalic(true);
                            $sheet->getStyle("A{$row}")->getAlignment()->setIndent(2);
                        }

                        $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()
                            ->setBorderStyle(Border::BORDER_THIN);

                        $row++;
                    }
                }
            },
        ];
    }
}
