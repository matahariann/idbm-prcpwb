<?php

namespace App\Exports\Templates\FACTWM\FACTWM01;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class FACTWMF002 implements FromArray, WithHeadings, ShouldAutoSize, WithEvents
{

    public function array(): array
    {
        return [
            [
                'V001',
                '01.234.567.8-901.000',
                '3201234567890001',
                'PKP',
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Vendor Code',
            'NPWP',
            'NIK',
            'Status PKP',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set dropdown untuk kolom Status PKP (kolom D) dari baris 2 sampai 1000
                // for ($row = 2; $row <= 1000; $row++) {
                $validation = $sheet->getCell('D2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Input Error');
                $validation->setError('Pilih PKP atau Non-PKP');
                $validation->setPromptTitle('Status PKP');
                $validation->setPrompt('Pilih status PKP vendor');
                $validation->setFormula1('"PKP,Non-PKP"');
                // }
            },
        ];
    }
}
