<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM03;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Original\FACTWM03\FACTWMF013DataTable;
use App\Helpers\Helpers;
use App\Http\Requests\FACTWM\FACTWM03\UploadFileRequest;
use App\Http\Resources\FACTWM\FACTWM03\FACTWMF013\DocumentResource;
use Illuminate\Support\Facades\Storage;
use App\Models\FACTWM03\FACTWM_FOLDER as Folder;
use App\Models\FACTWM03\FACTWM_FILE as File;
use Illuminate\Support\Facades\Auth;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER as Supplier;
use App\Services\FACTWM\DMSService;
use App\Services\FACTWM\SupplierService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;

// DMS
class FACTWMF013 extends Controller
{
    public function __construct(protected DMSService $upload_file_dmsservice, protected SupplierService $supplierService) {}

    public function index(FACTWMF013DataTable $dataTable)
    {
        $vendorData = $this->supplierService->getAllSuppliers(request: request());
        // ambil user supplier yang login
        $supplier = Helpers::getSupplierId();
        return $dataTable->render('modules.FACTWM.FACTWM03.FACTWMF013.FACTWMF013', [
            'vendorData' => $vendorData,
            'supplier' => $supplier
        ]);
        // return view('modules.FACTWM.FACTWM03.FACTWMF013.FACTWMF013', [
        //     'dataTable' => $dataTable->html(),
        // ]);
    }

    public function dataTable(FACTWMF013DataTable $dataTable)
    {
        return $dataTable->ajax();
    }

    public function getData(Request $request)
    {
        // get auth supplier
        $idViewers = Helpers::getSupplierId();

        $supplier = null;
        $supplier_code = null;
        if (!empty($idViewers)) {
            $supplier = Supplier::find($idViewers);
            $supplier_code = $supplier->VSUPPLIER_CODE;
        }

        // filter
        $category = $request->category;
        $type = $request->type;
        $parentID = $request->parentID;
        $keyword = $request->keyword;
        if ($category == 'Folder') {
            $getData = Folder::with(['users'])
                ->where('VFOLDER_TYPE', $type)
                ->when($parentID, function ($query) use ($parentID) {
                    return $query->where('IPARENT_ID', $parentID);
                })
                ->when(
                    $request->type === 'supplier' && !empty($supplier_code),
                    function ($query) use ($supplier_code) {
                        $query->where('VSUPPLIER_CODE', $supplier_code);
                    }
                )
                ->when(!empty($keyword), function ($query) use ($keyword) {
                    return $query->where('VNAME', 'ILIKE', '%' . $keyword . '%');
                })
                ->orderBy('IID', 'desc')
                ->get();
        } else {
            $getData = File::with(['users'])
                ->where('IFOLDER_ID', $parentID)
                ->when(!empty($keyword), function ($query) use ($keyword) {
                    return $query->where('VNAME', 'ILIKE', '%' . $keyword . '%');
                })
                ->get();
        }

        $data = $getData
            ->map(fn($item) => new DocumentResource($item, $supplier_code))
            ->filter(fn($resource) => $resource->toArray(request())['show_folder'] === true)
            ->values(); // reset index

        return response()->json([
            'message' => 'Get data success',
            'data' => $data,
        ]);
    }

    public function upload(Request $request)
    {
        $file =  $request->file('file');
        // $billing_statement = 'file_invoice';
        $file_type = 'file_invoice';

        // upload file
        $this->upload_file_dmsservice->uploadFile($file, $file_type);

        return response()->json([
            'message' => 'Upload file success'
        ]);
    }

    public function uploadOtherDocument(UploadFileRequest $request)
    {
        // upload file
        $upload = $this->upload_file_dmsservice->uploadOtherDocument($request);

        return response()->json([
            'message' => 'Upload file success'
        ]);
    }

    public function getFileProgress(Request $request)
    {
        // contoh: tabel files
        // columns: id, category, size_mb
        $data = File::select(
            'VFILE_TYPE',
            DB::raw('SUM("ISIZE") as total_mb')
        )
            ->whereNull('DDELETE')
            ->groupBy('VFILE_TYPE')
            ->get();

        $totalBytes = (int) $this->checkDisk();

        $result = $data->map(function ($item) use ($totalBytes) {
            $bytes = (float) $item->total_mb;

            $sizeGb = $this->formatBytes($bytes);

            $percent = $totalBytes > 0
                ? round(($bytes / $totalBytes) * 100)
                : 0;

            return [
                'name'    => $this->mapCategoryName($item->VFILE_TYPE),
                'size'    => $sizeGb,
                'percent' => $percent,
                'color'   => $this->mapCategoryColor($item->VFILE_TYPE),
            ];
        });

        return response()->json([
            'message' => 'Get data success',
            'data' => $result
        ]);
    }

    public function getDataChart(Request $request)
    {
        // contoh: tabel files
        // columns: id, category, size_mb
        $data = File::select(
            DB::raw('SUM("ISIZE") as total_bytes')
        )
            ->whereNull('DDELETE')
            ->first(); // 👈 cukup 1 row

        $usedBytes  = (float) ($data->total_bytes ?? 0);
        // $usedBytes = 100 * (1024 ** 2);

        // capacity = 1 GB
        // $totalBytes = 1024 ** 3;
        $totalBytes = (int) $this->checkDisk();

        // jaga-jaga biar gak > 100%
        $usedPercent = $totalBytes > 0
            ? min(round(($usedBytes / $totalBytes) * 100), 100)
            : 0;

        $freePercent = 100 - $usedPercent;

        $result = [
            'total'  => 100, // untuk label tengah donut
            'series' => [
                (int)$usedPercent,
                (int)$freePercent
            ],
            'labels' => [
                'Used',
                'Free'
            ],
            'usedBytes' => $this->formatBytes($usedBytes),
            'totalBytes' => $this->formatBytes($totalBytes),
        ];
        return response()->json([
            'message' => 'Get data success',
            'data' => $result
        ]);
    }

    public function downloadFile($id)
    {
        return $this->upload_file_dmsservice->download($id);
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

    private function mapCategoryName($category)
    {
        return match ($category) {
            'file_invoice'      => 'File Invoice',
            'file_faktur_pajak' => 'File Faktur Pajak',
            'file_po'           => 'File PO',
            'file_rekap_jasa'   => 'Rekap Jasa',
            default             => 'Other Document',
        };
    }

    private function mapCategoryColor($category)
    {
        return match ($category) {
            'file_invoice'          => 'danger',
            'file_faktur_pajak'     => 'blue',
            'file_po'               => 'success',
            'file_rekap_jasa'       => 'info',
            default                 => 'warning',
        };
    }

    public function checkDisk()
    {
        $host = config('filesystems.disks.sftp.host');
        $port = config('filesystems.disks.sftp.port');
        $username = config('filesystems.disks.sftp.username');
        $password = config('filesystems.disks.sftp.password');

        $freeBytes = 0;
        $ssh = new SSH2($host, $port);

        if (!$ssh->login($username, $password)) {
            throw new \RuntimeException('SFTP login failed');
        }

        $output = $ssh->exec("df -B1 / | awk 'NR==2 {print $2\" \"$3\" \"$4}'");

        if (!$output) {
            throw new \RuntimeException('Failed to get disk information');
        }

        $parts = preg_split('/\s+/', trim($output));

        $totalBytes = (int) ($parts[0] ?? 0);
        $usedBytes = (int) ($parts[1] ?? 0);
        $freeBytes = (int) trim($parts[2] ?? 0);

        // $freeFormatted = $this->formatBytes($freeBytes, 2);

        return $freeBytes;
    }
}
