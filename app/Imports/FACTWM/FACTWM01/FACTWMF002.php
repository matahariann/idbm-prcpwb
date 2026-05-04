<?php

namespace App\Imports\FACTWM\FACTWM01;

use App\Models\FACTWM01\FACTWM_MSHSUPPLIER as Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;

class FACTWMF002 implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    protected $logs = [];

    protected $totalError = 0;

    protected $updatedBy = null;

    protected $columns = ['vendor_code', 'npwp', 'nik', 'status_pkp'];

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $collection)
    {
        if ($collection === null || $collection->isEmpty()) {
            throw new SpreadsheetException('The file is empty', 422);
        }

        $currentRowIndex = $this->startRow();
        $this->updatedBy = "'" . Auth::user()->VUSERNAME . "'";
        $vendorCode = [];

        foreach ($collection as $row) {
            $row['row'] = $currentRowIndex;
            $row = $row->toArray();

            foreach ($this->columns as $col) {
                // Trim the column values if they are not empty, otherwise set them to null
                $row[$col] = ! empty($row[$col]) ? trim($row[$col]) : null;
            }

            $this->setLog($row);
            $this->required($row, ['vendor_code', 'status_pkp']);

            if (in_array($row['vendor_code'], $vendorCode)) {
                $this->setLog($row, 'Vendor code is duplicated');
            }

            // Validasi Status PKP
            if (!empty($row['status_pkp'])) {
                $validStatuses = ['PKP', 'Non-PKP'];
                if (!in_array($row['status_pkp'], $validStatuses)) {
                    $this->setLog($row, 'Status PKP must be either PKP or Non-PKP');
                }
            }

            // Validasi: Harus salah satu terisi, tidak boleh keduanya kosong atau keduanya terisi
            $hasNpwp = !empty($row['npwp']);
            $hasNik = !empty($row['nik']);

            if (!$hasNpwp && !$hasNik) {
                // Keduanya kosong
                $this->setLog($row, 'Either NPWP or NIK must be filled, not both empty');
            } elseif ($hasNpwp && $hasNik) {
                // Keduanya terisi
                $this->setLog($row, 'Only one of NPWP or NIK must be filled, not both');
            }

            $vendorCode[] = $row['vendor_code'];

            $currentRowIndex++;
        }

        $this->databaseValidation($vendorCode);

        if ($this->totalError == 0) {
            $this->store();
        }
    }

    private function store()
    {
        $cleaned = collect($this->logs)->values()->map(function ($item) {

            return Arr::except($item, ['error_count', 'errors', 'row']);
        })->toArray();

        $this->update($cleaned);
    }

    private function databaseValidation($vendorCode)
    {
        $existing = Supplier::whereIn('VSUPPLIER_CODE', $vendorCode)->pluck('VSUPPLIER_CODE')->toArray();

        foreach ($vendorCode as $code) {
            $row = collect($this->logs)->firstWhere('vendor_code', $code);
            if (!in_array($row['vendor_code'], $existing)) {
                $this->setLog($row, "Vendor code doesn't exist");
            }
        }
    }

    private function required($row, $columns)
    {
        $emptyColumn = array_filter($columns, fn($column) => ! isset($row[$column]) || empty($row[$column]));

        if (count($emptyColumn) > 0) {
            foreach ($emptyColumn as $col) {
                $this->setLog($row, "Column {$col} is required");
            }
        }
    }

    private function setLog($row, $error = null)
    {
        $unique = $row['row'];
        if (! isset($this->logs[$unique])) {
            $this->logs[$unique] = array_merge(Arr::only($row, $this->columns), [
                'row' => $unique,
                'error_count' => 0,
                'errors' => [],
            ]);
        } else {
            if ($error !== null) {
                $this->totalError++;
                $this->logs[$unique]['error_count']++;
                $this->logs[$unique]['errors'][] = $error;
            }
        }
    }

    public function getResult()
    {
        $correctLogs = collect($this->logs)->where('error_count', '=', 0)->count();
        $incorrectLogs = collect($this->logs)->where('error_count', '>', 0)->count();
        $totalError = $this->totalError;
        $errorLogs = collect($this->logs)->where('error_count', '>', 0);

        return (object) [
            'totalLogs' => count($this->logs),
            'totalCorrectLogs' => $correctLogs,
            'totalErrorLogs' => $incorrectLogs,
            'totalError' => $totalError,
            'logs' => array_values($this->logs),
            'errorLogs' => $errorLogs,
        ];
    }

    private function update(array $data)
    {
        $npwpCase = "";
        $nikCase  = "";
        $bpkpCase = "";
        $codes = [];

        foreach ($data as $row) {
            $code = $row['vendor_code'];
            $codes[] = "'" . $code . "'";

            $npwp = $row['npwp'] ?? 'NULL';
            $nik  = $row['nik'] ?? 'NULL';

            // Konversi PKP/Non-PKP ke boolean
            // PKP = true, Non-PKP = false
            $bpkp = isset($row['status_pkp']) && $row['status_pkp'] === 'PKP' ? 'TRUE' : 'FALSE';

            $npwpCase .= "WHEN '{$code}' THEN " . ($npwp === 'NULL' ? 'NULL' : "'{$npwp}'") . " ";
            $nikCase  .= "WHEN '{$code}' THEN " . ($nik === 'NULL' ? 'NULL' : "'{$nik}'") . " ";
            $bpkpCase .= "WHEN '{$code}' THEN {$bpkp} ";
        }

        $codesList = implode(",", $codes);

        $sql = '
        UPDATE "FACTWM_MSHSUPPLIERS"
        SET
            "VNPWP" = CASE "VSUPPLIER_CODE"
                ' . $npwpCase . '
            END,
            "VNIK" = CASE "VSUPPLIER_CODE"
                ' . $nikCase . '
            END,
            "BPKP" = CASE "VSUPPLIER_CODE"
                ' . $bpkpCase . '
            END,
            "VMODI" = ' . $this->updatedBy . ',
            "DMODI" = NOW()
        WHERE "VSUPPLIER_CODE" IN (' . $codesList . ')
    ';

        DB::statement($sql);
    }
}
