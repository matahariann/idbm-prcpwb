<?php

namespace App\Http\Resources\FACTWM\FACTWM03\FACTWMF013;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\FACTWM03\FACTWM_FOLDER as Folder;

class DocumentResource extends JsonResource
{
    protected ?string $supplierCode;

    public function __construct($resource, ?string $supplierCode = null)
    {
        parent::__construct($resource);
        $this->supplierCode = $supplierCode;
    }

    public function toArray(Request $request): array
    {
        $isFolder = isset($this->VFOLDER_TYPE);
        $fileType = $isFolder ? $this->VFOLDER_TYPE : null;
        $fileName = $fileType == 'file_type' ? ucwords(str_replace('_', ' ', $this->VNAME)) :  $this->VNAME;
        $isFolder = isset($this->VFOLDER_TYPE);
        $isYearOrMonth = in_array($this->VFOLDER_TYPE, ['year', 'month']);
        $countFiles = $isFolder ? $this->ITOTAL_FILES : null;
        if ($this->supplierCode && in_array($this->VFOLDER_TYPE, ['year', 'month'])) {

            $monthIds = $this->VFOLDER_TYPE === 'year'
                ? $this->getDataMonthByYear($this->IID)
                : [(int) $this->IID];

            $countFiles = empty($monthIds)
                ? 0
                : $this->countDataSupplier($monthIds, $this->supplierCode);
        }
        $showFolder = !($isFolder && $countFiles === 0);
        return [
            'id' => $this->IID ?? $this->id,
            'category' => $isFolder ? 'Folder' : 'File',

            'filename' => $fileName,
            'short_filename' => strlen($fileName) > 11
                ? substr($fileName, 0, 10) . '...'
                : $fileName,

            'updated_at' => Carbon::parse($this->DMODI, 'UTC')
                ->setTimezone('Asia/Jakarta')
                ->format('Y-m-d H:i:s'),

            'user_name' => $this->users?->VUSERNAME,
            'user_email' => $this->users?->VEMAIL,
            'user_photo' => $this->users?->VPHOTO,

            // 👇 Folder only
            'type' => $fileType,
            'total_file' => $countFiles,

            // 👇 File only
            'size' => !$isFolder ? $this->formatBytes($this->ISIZE) : null,
            'filesize' => !$isFolder ? $this->ISIZE : null,
            'file' => !$isFolder
                ? (
                    Storage::disk('public')->exists($this->VPATH . '/' . $this->VNAME)
                    ? Storage::url($this->VPATH . '/' . $this->VNAME)
                    : null
                )
                : null,

            'show_folder' => $showFolder,
        ];
    }

    private function formatBytes($bytes, $precision = 0)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    // buat pengambilan data id bulan berdasarkan param tahun
    private function getDataMonthByYear(int $IdYear)
    {
        $monthIds = Folder::where('VFOLDER_TYPE', 'month')
            ->where('IPARENT_ID', $IdYear)
            ->pluck('IID')
            ->toArray();

        return $monthIds;
    }

    // select count data supplier berdasarkan bulan
    private function countDataSupplier(array $monthIds, string $supplierCode): Int
    {
        $countSupplier = Folder::where('VFOLDER_TYPE', 'supplier')
            ->whereIn('IPARENT_ID', $monthIds)
            ->where('VSUPPLIER_CODE', $supplierCode)
            ->sum('ITOTAL_FILES');

        return $countSupplier;
    }
}
