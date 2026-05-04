<?php

namespace App\Services\FACTWM;

use App\Helpers\Helpers;
use Illuminate\Support\Facades\Storage;
use App\Models\FACTWM03\FACTWM_FOLDER as Folder;
use App\Models\FACTWM03\FACTWM_FILE as File;
use Illuminate\Support\Facades\Auth;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER as Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DMSService
{
    public function uploadOtherDocument($request)
    {

        DB::beginTransaction();

        try {
            $file = $request->file('file');
            $fileType = 'other_document';

            if ($request->type == 'internal') {
                $supplier = Supplier::findOrFail($request->supplier);
                $supplier_code = $supplier->VSUPPLIER_CODE;
                $supplier_name = $supplier->VNAME;
            } else {
                $idViewers = Helpers::getSupplierId();
                $supplier = Supplier::findOrFail($idViewers);
                $supplier_code = $supplier->VSUPPLIER_CODE;
                $supplier_name = $supplier->VNAME;
            }

            $date   = $request->date;

            $date = Carbon::parse($request->date);

            $year  = $date->year;        // 2025
            $month = $date->format('F');       // 12
            $day   = $date->day;         // 29

            $cFileType = ucwords(str_replace('_', ' ', $fileType));

            $folder = "$year/$month/$supplier_code/$day/$fileType";

            $fileExtension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $fileSize = $file->getSize();
            $originalFileName = $file->getClientOriginalName();
            $fileName = $supplier_code . '_' . 'OT' . '_' . date('YmdHis') . '_' . $request->file_name;
            // $fileName = Str::uuid()->toString() . '.' . $fileExtension;
            // create folder (DB)
            $folderId = $this->makeFolder($folder, $fileSize, $supplier);

            // create file (DB)
            $fileModel = File::create([
                'VNAME'          => $fileName,
                'VORIGINAL_NAME' => $originalFileName,
                'ISIZE'          => $fileSize,
                'VEXTENSION'     => $fileExtension,
                'VPATH'          => $folder,
                'VFILE_TYPE'     => $fileType,
                'IFOLDER_ID'     => $folderId,
                'IUSER_ID'       => Auth::user()->IID,
                'VSUPPLIER_CODE' => $supplier->VSUPPLIER_CODE,
                'VSUPPLIER_NAME' => $supplier->VNAME,
            ]);

            // storage
            $newFileName = "{$fileName}.{$fileExtension}";

            Storage::disk(config('filesystems.disk_target'))
                ->makeDirectory($folder);

            $uploaded = Storage::disk(config('filesystems.disk_target'))
                ->putFileAs($folder, $file, $newFileName);

            // upload ke SFTP
            // $uploaded = Storage::disk(config('filesystems.disk_target'))->put(
            //     $folder . '/' . $fileName,
            //     fopen($file->getRealPath(), 'r')
            // );

            if (!$uploaded) {
                throw new \Exception('Upload file failed');
            }

            DB::commit();

            return $fileModel;
        } catch (\League\Flysystem\FilesystemException $e) {

            // error dari Flysystem (SFTP error)
            // Log::error('SFTP Error', [
            //     'message' => $e->getMessage(),
            // ]);

            throw $e;       // lempar object exception nya
        } catch (\Throwable $e) {
            DB::rollBack();

            // optional: hapus file jika sudah terlanjur tersimpan
            // if (Storage::disk(config('filesystems.disk_target'))->exists("$folder/$fileName")) {
            //     Storage::disk(config('filesystems.disk_target'))->delete("$folder/$fileName");
            // }

            $newPath = "{$folder}/{$fileName}.{$fileExtension}";
            if (Storage::disk(config('filesystems.disk_target'))->exists("$newPath")) {
                Storage::disk(config('filesystems.disk_target'))->delete("$newPath");
            }

            throw $e; // atau return response error
        }
    }

    public function uploadFilePO($file, $fileType, $supplier, $bs_no, $name = null)
    {
        try {
            $sourceDisk = 'public';
            $targetDisk = config('filesystems.disk_target');

            $year  = date('Y');
            $month = date('F');
            $day   = date('d');

            $folder = "$year/$month/{$supplier->VSUPPLIER_CODE}/$day/$fileType";

            // ===== FILE INFO =====
            if (!Storage::disk($sourceDisk)->exists($file)) {
                throw new \Exception("Source file not found: {$file}");
            }

            $originalName = basename($file);
            $extension    = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileSize     = Storage::disk($sourceDisk)->size($file);

            // ===== GENERATE FILE NAME =====
            if ($fileType === 'other_document') {
                $storedFileName = "{$supplier->VSUPPLIER_CODE}_{$this->changeFolderType($fileType)}_" . date('YmdHis') . "_{$bs_no}_{$name}";
            } else {
                $storedFileName = "{$supplier->VSUPPLIER_CODE}_{$this->changeFolderType($fileType)}_" . date('YmdHis') . "_{$bs_no}";
            }

            $newPath = "{$folder}/{$storedFileName}.{$extension}";

            // ===== CREATE FOLDER DI SFTP =====
            if (!Storage::disk($targetDisk)->exists($folder)) {
                Storage::disk($targetDisk)->makeDirectory($folder);
            }

            // ===== HAPUS FILE LAMA JIKA ADA (SFTP) =====
            if (Storage::disk($targetDisk)->exists($newPath)) {
                Storage::disk($targetDisk)->delete($newPath);
            }

            // ===== SAVE FOLDER TO DB =====
            $folderId = $this->makeFolder($folder, $fileSize, $supplier);

            // ===== COPY FILE (PUBLIC ➜ SFTP) =====
            $stream = Storage::disk($sourceDisk)->readStream($file);

            if ($stream === false) {
                throw new \Exception('Failed to read source file');
            }

            $uploaded = Storage::disk($targetDisk)->put($newPath, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            if (!$uploaded) {
                throw new \Exception('Upload to SFTP failed');
            }

            // ===== DELETE FILE DI PUBLIC SETELAH SUKSES =====
            // Storage::disk($sourceDisk)->delete($file);

            // ===== SAVE FILE TO DB =====
            File::create([
                'VNAME'          => $storedFileName,
                'VORIGINAL_NAME' => $originalName,
                'ISIZE'          => $fileSize,
                'VEXTENSION'     => $extension,
                'VPATH'          => $folder,
                'VFILE_TYPE'     => $fileType,
                'IFOLDER_ID'     => $folderId,
                'IUSER_ID'       => Auth::user()->IID,
                'VSUPPLIER_CODE' => $supplier->VSUPPLIER_CODE,
                'VSUPPLIER_NAME' => $supplier->VNAME,
            ]);

            return $newPath;
        } catch (\League\Flysystem\FilesystemException $e) {

            // error dari Flysystem (SFTP error)
            // Log::error('SFTP Error', [
            //     'message' => $e->getMessage(),
            // ]);

            throw $e;       // lempar object exception nya
        } catch (\Throwable $e) {

            // rollback file di SFTP
            if (isset($newPath) && Storage::disk(config('filesystems.disk_target'))->exists($newPath)) {
                Storage::disk(config('filesystems.disk_target'))->delete($newPath);
            }

            throw $e;
        }
    }


    private function makeFolder($folder, $fileSize, $supplier)
    {
        $arr_folder = explode('/', $folder);
        $lastFolderId = null;
        $parentID = null;
        $adminParentID = null;
        $lastKey = array_key_last($arr_folder);
        foreach ($arr_folder as $key => $value) {
            // check last index
            $isLast = ($key === $lastKey);
            // update or create folder untuk supplier
            $folder = Folder::updateOrCreate(
                [
                    'VNAME' => $value,
                    'IPARENT_ID' => $parentID,
                    'VFOLDER_TYPE' => $this->getFolderType($key),
                ],
                [
                    'VSUPPLIER_CODE' => (!empty($supplier) && $key == 2) ? $supplier->VSUPPLIER_CODE : null,
                    'VSUPPLIER_NAME' => (!empty($supplier) && $key == 2) ? $supplier->VNAME : null,
                    'IUSER_ID' => Auth::user()->IID,
                ]
            );

            // kalau existing folder → tambah size & total file
            if (!$folder->wasRecentlyCreated) {
                $folder->increment('ISIZE', $fileSize);
                $folder->increment('ITOTAL_FILES', 1);
            } else {
                // kalau folder baru
                $folder->update([
                    'ISIZE' => $fileSize,
                    'ITOTAL_FILES' => 1,
                ]);
            }

            $parentID = $folder->IID;

            if ($isLast) {
                $lastFolderId = $folder->IID;
            }
        }

        return $lastFolderId;
    }

    public function download($id)
    {
        try {
            $file = File::find($id);

            $path = rtrim($file->VPATH, '/');

            // kalau VPATH sudah mengandung nama file
            if (str_ends_with($path, $file->VNAME)) {
                $fullPath = $path;
            } else {
                $fullPath = $path . '/' . $file->VNAME . '.' . $file->VEXTENSION;
            }

            if (!Storage::disk(config('filesystems.disk_target'))->exists($fullPath)) {
                throw new \Exception('Download file failed');
            }

            return Storage::disk(config('filesystems.disk_target'))
                ->download($fullPath, $file->VNAME . '.' . $file->VEXTENSION);
        } catch (\League\Flysystem\FilesystemException $e) {

            // error dari Flysystem (SFTP error)
            // Log::error('SFTP Error', [
            //     'message' => $e->getMessage(),
            // ]);

            throw $e;       // lempar object exception nya
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private function getFolderType($key)
    {
        switch ($key) {
            case 0:
                return 'year';
            case 1:
                return 'month';
            case 2:
                return 'supplier';
            case 3:
                return 'date';
            default:
                return 'file_type';
        }
    }

    private function changeFolderType($key)
    {
        switch ($key) {
            case 'file_invoice':
                return 'IN';
            case 'file_faktur_pajak':
                return 'FP';
            case 'file_rekap_jasa':
                return 'PH';
            default:
                return 'OT';
        }
    }
}
