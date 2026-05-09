<?php

namespace App\Exports\PRCPWB\PRCPWB01;

use App\Models\PRCPWB01\PRCPWB_MSHVENDOR as Vendor;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class PRCPWBF002 implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function query()
    {
        $query       = Vendor::query();
        $isSelectAll = $this->params['selectAll'] ?? false;
        $keyword     = $this->params['keyword'] ?? '';
        $excludedIds = $this->params['excludedIds'] ?? [];
        $ids         = $this->params['ids'] ?? [];

        if ($isSelectAll) {
            if (!empty($keyword)) {
                $query->whereAny(['VVENDORNO', 'VVENDORNAME'], 'ILIKE', "%{$keyword}%");
            }
            if (!empty($excludedIds)) {
                $query->whereNotIn('IID', $excludedIds);
            }
        } else {
            $query->whereIn('IID', $ids);
        }

        return $query->select(['VVENDORNO', 'VVENDORNAME', 'VCONTACT', 'VADDRESS', 'VIMPORT', 'DCREA', 'DMODI']);
    }

    public function headings(): array
    {
        return [
            'Vendor No',
            'Vendor Name',
            'Contact',
            'Address',
            'Import',
            'Created Date',
            'Modified Date'
        ];
    }

    public function map($vendor): array
    {
        return [
            $vendor->VVENDORNO,
            $vendor->VVENDORNAME,
            $vendor->VCONTACT,
            $vendor->VADDRESS,
            $vendor->VIMPORT?->name ?? '-',
            $vendor->DCREA ? Carbon::parse($vendor->DCREA)->format('d M Y H:i') : '',
            $vendor->DMODI ? Carbon::parse($vendor->DMODI)->format('d M Y H:i') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
            ],
        ];
    }
}