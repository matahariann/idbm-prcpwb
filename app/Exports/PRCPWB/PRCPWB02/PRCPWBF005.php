<?php

namespace App\Exports\PRCPWB\PRCPWB02;

use App\Models\PRCPWB02\PRCPWB_TRHDAILY_REQUEST as DailyRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class PRCPWBF005 implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function query()
    {
        $query = DailyRequest::query()
            ->leftJoin('PRCPWB_MSHVENDORS', 
                'PRCPWB_TRHDAILYREQUESTS.VVENDORNO', 
                '=', 
                'PRCPWB_MSHVENDORS.VVENDORNO' 
            );
        $isSelectAll = $this->params['selectAll'] ?? false;
        $keyword     = $this->params['keyword'] ?? '';
        $excludedIds = $this->params['excludedIds'] ?? [];
        $ids         = $this->params['ids'] ?? [];

        if ($isSelectAll) {
            if (!empty($keyword)) {
                $query->whereAny(['PRCPWB_TRHDAILYREQUESTS.VVENDORNO', 'VVENDORNAME', 'PRCPWB_TRHDAILYREQUESTS.VPARTNO', 'PRCPWB_TRHDAILYREQUESTS.VPARTDESCRIPTION'], 'ILIKE', "%{$keyword}%");
            }
            if (!empty($excludedIds)) {
                $query->whereNotIn('PRCPWB_TRHDAILYREQUESTS.IID', $excludedIds);
            }
        } else {
            $query->whereIn('PRCPWB_TRHDAILYREQUESTS.IID', $ids);
        }

        return $query->select([
            'PRCPWB_TRHDAILYREQUESTS.VVENDORNO', 
            'PRCPWB_MSHVENDORS.VVENDORNAME', 
            'PRCPWB_TRHDAILYREQUESTS.VPARTNO', 
            'PRCPWB_TRHDAILYREQUESTS.VPARTDESCRIPTION', 
            'PRCPWB_TRHDAILYREQUESTS.DWANTEDRECEIPTDATE', 
            'PRCPWB_TRHDAILYREQUESTS.DPROPOSEDWANTEDRECEIPTDATE', 
            'PRCPWB_TRHDAILYREQUESTS.VTIME', 
            'PRCPWB_TRHDAILYREQUESTS.IQUANTITY', 
            'PRCPWB_TRHDAILYREQUESTS.IQUANTITYCONFIRMATION', 
            'PRCPWB_TRHDAILYREQUESTS.IQUANTITYACTUAL', 
            'PRCPWB_TRHDAILYREQUESTS.VSTATUS', 
            'PRCPWB_TRHDAILYREQUESTS.VDELIVERYNOTENO', 
            'PRCPWB_TRHDAILYREQUESTS.VPONO', 
            'PRCPWB_TRHDAILYREQUESTS.VDAILYREQNO', 
            'PRCPWB_TRHDAILYREQUESTS.VPRODUCTFAMILY', 
            'PRCPWB_TRHDAILYREQUESTS.IREVNO', 
            'PRCPWB_TRHDAILYREQUESTS.VFORECAST', 
            'PRCPWB_TRHDAILYREQUESTS.IMSPERIOD', 
            'PRCPWB_TRHDAILYREQUESTS.IMSYEAR', 
            'PRCPWB_TRHDAILYREQUESTS.VUNITMEAS', 
            'PRCPWB_TRHDAILYREQUESTS.VDEDICATEDLOCATION', 
            'PRCPWB_TRHDAILYREQUESTS.VPROCCONTACT', 
            'PRCPWB_TRHDAILYREQUESTS.VCREA', 
            'PRCPWB_TRHDAILYREQUESTS.DCREA', 
            'PRCPWB_TRHDAILYREQUESTS.VMODI', 
            'PRCPWB_TRHDAILYREQUESTS.DMODI', 
            'PRCPWB_TRHDAILYREQUESTS.VDELETE', 
            'PRCPWB_TRHDAILYREQUESTS.DDELETE'
        ])
        ->selectSub(function ($query) {
            $query->selectRaw('COALESCE(SUM(y."IQUANTITY"), 0)')
                ->from('PRCPWB_TRHDAILYREQUESTS as y')
                ->whereColumn('y.VVENDORNO', 'PRCPWB_TRHDAILYREQUESTS.VVENDORNO')
                ->whereColumn('y.VPARTNO', 'PRCPWB_TRHDAILYREQUESTS.VPARTNO')
                ->where('y.VSTATUS', '!=', 'Received')
                ->whereRaw("DATE_TRUNC('month', y.\"DWANTEDRECEIPTDATE\") = DATE_TRUNC('month', \"PRCPWB_TRHDAILYREQUESTS\".\"DWANTEDRECEIPTDATE\")")
                ->whereRaw("y.\"DWANTEDRECEIPTDATE\" BETWEEN DATE_TRUNC('month', CURRENT_DATE) AND \"PRCPWB_TRHDAILYREQUESTS\".\"DWANTEDRECEIPTDATE\" - INTERVAL '1 day'");
        }, 'BAL');
    }

    public function headings(): array
    {
        return [
            'Vendor ID',
            'Vendor Name',
            'Wanted Receipt Date',
            'Time',
            'Part Number',
            'Part Description',
            'BAL',
            'UOM',
            'QTY DR',
            'QTY SJ',
            'QTY ACT',
            'Status',
            'PO Number',
            'DR Number',
            'SJ Number',
            'Pord Family',
            'Actual Receipt Date'
        ];
    }

    public function map($vendor): array
    {
        return [
            $vendor->VVENDORNO,
            $vendor->VVENDORNAME,
            $vendor->DWANTEDRECEIPTDATE ? Carbon::parse($vendor->DWANTEDRECEIPTDATE)->format('d M Y') : '',
            $vendor->VTIME ? Carbon::parse($vendor->VTIME)->format('H:i') : '',
            $vendor->VPARTNO,
            $vendor->VPARTDESCRIPTION,
            $vendor->BAL,
            $vendor->VUNITMEAS,
            $vendor->IQUANTITY,
            $vendor->IQUANTITYCONFIRMATION,
            $vendor->IQUANTITYACTUAL,
            $vendor->VSTATUS instanceof \BackedEnum ? $vendor->VSTATUS->value : $vendor->VSTATUS,
            $vendor->VPONO,
            $vendor->VDAILYREQNO,
            $vendor->VDELIVERYNOTENO,
            $vendor->VPRODUCTFAMILY,
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