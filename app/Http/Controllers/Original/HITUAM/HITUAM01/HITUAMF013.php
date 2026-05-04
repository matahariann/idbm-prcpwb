<?php

namespace App\Http\Controllers\Original\HITUAM\HITUAM01;

use App\Http\Controllers\Controller;
use App\Http\Requests\HITUAM\HITUAM01\InformationRequest;
use App\DataTables\Original\HITUAM01\HITUAMF013DataTable as InformationTable;
use App\Helpers\Response;
use App\Models\HITUAM01\HITUAM_MSHINFORMATION as Information;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HITUAMF013 extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(InformationTable $dataTable)
    {
        return $dataTable->render('modules.HITUAM.HITUAM01.HITUAMF013.HITUAMF013');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('modules.HITUAM.HITUAM01.HITUAMF013.partials._information-form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InformationRequest $request)
    {
        try {
            $data = $request->validated();

            $information = DB::transaction(function () use ($data, $request) {
                // Upload files
                $fileInformation = $request->file('VFILE_INFORMATION')->store('information/pdf', 'public');
                $uploadDataVendor = $request->file('VUPDLOAD_DATA_VENDOR')->store('information/vendor', 'public');
                $uploadFotoAsset = null;

                // FIXED: Changed from VUPLOAD_FOTO_ASSET to VUPDLOAD_FOTO_ASSET
                if ($request->hasFile('VUPDLOAD_FOTO_ASSET')) {
                    $uploadFotoAsset = $request->file('VUPDLOAD_FOTO_ASSET')->store('information/assets', 'public');
                }

                // Create information record
                $informationData = [
                    'VNOTES' => $data['VNOTES'],
                    'DFROM' => $data['DFROM'],
                    'DTO' => $data['DTO'],
                    'VUSER_TYPE' => $data['VUSER_TYPE'],
                    'VCATEGORY' => $data['VCATEGORY'],
                    'VFILE_INFORMATION' => $fileInformation,
                    'VUPDLOAD_DATA_VENDOR' => $uploadDataVendor,
                    'VUPDLOAD_FOTO_ASSET' => $uploadFotoAsset,
                ];

                return Information::create($informationData);
            });

            return Response::success(
                data: $information,
                message: 'Information created successfully',
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error(
                message: 'Validation failed',
            );
        } catch (\Exception $e) {
            return Response::error(
                message: 'Failed to create information: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Information $information)
    {
        try {
            return Response::success(data: $information);
        } catch (\Exception $e) {
            return Response::error(
                message: 'Failed to retrieve information: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Information $information)
    {
        try {
            return view('modules.HITUAM.HITUAM01.HITUAMF013.partials._information-form', compact('information'));
        } catch (\Exception $e) {
            return redirect()->route('information.index')
                ->with('error', 'Failed to retrieve information');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InformationRequest $request, Information $information)
    {
        try {
            $data = $request->validated();

            $information = DB::transaction(function () use ($data, $request, $information) {

                $updateData = [
                    'VNOTES' => $data['VNOTES'],
                    'DFROM' => $data['DFROM'],
                    'DTO' => $data['DTO'],
                    'VUSER_TYPE' => $data['VUSER_TYPE'],
                    'VCATEGORY' => $data['VCATEGORY'],
                ];

                // Handle PDF file update
                if ($request->hasFile('VFILE_INFORMATION')) {
                    if ($information->VFILE_INFORMATION) {
                        Storage::disk('public')->delete($information->VFILE_INFORMATION);
                    }
                    $updateData['VFILE_INFORMATION'] = $request->file('VFILE_INFORMATION')
                        ->store('information/pdf', 'public');
                }

                // Handle vendor file update
                if ($request->hasFile('VUPDLOAD_DATA_VENDOR')) {
                    if ($information->VUPDLOAD_DATA_VENDOR) {
                        Storage::disk('public')->delete($information->VUPDLOAD_DATA_VENDOR);
                    }
                    $updateData['VUPDLOAD_DATA_VENDOR'] = $request->file('VUPDLOAD_DATA_VENDOR')
                        ->store('information/vendor', 'public');
                }

                // Handle asset file update - FIXED: VUPDLOAD_FOTO_ASSET
                if ($request->hasFile('VUPDLOAD_FOTO_ASSET')) {
                    if ($information->VUPDLOAD_FOTO_ASSET) {
                        Storage::disk('public')->delete($information->VUPDLOAD_FOTO_ASSET);
                    }
                    $updateData['VUPDLOAD_FOTO_ASSET'] = $request->file('VUPDLOAD_FOTO_ASSET')
                        ->store('information/assets', 'public');
                }

                // Update information record
                $information->update($updateData);

                return $information;
            });

            return Response::success(
                data: $information,
                message: 'Information updated successfully',
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::error(
                message: 'Validation failed',
            );
        } catch (\Exception $e) {
            return Response::error(
                message: 'Failed to update information: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Information $information)
    {
        try {
            DB::transaction(function () use ($information) {
                // Delete files from storage
                if ($information->VFILE_INFORMATION) {
                    Storage::disk('public')->delete($information->VFILE_INFORMATION);
                }
                if ($information->VUPDLOAD_DATA_VENDOR) {
                    Storage::disk('public')->delete($information->VUPDLOAD_DATA_VENDOR);
                }
                if ($information->VUPDLOAD_FOTO_ASSET) {
                    Storage::disk('public')->delete($information->VUPDLOAD_FOTO_ASSET);
                }

                // Delete from database
                $information->delete();
            });

            return Response::success(message: 'Information deleted successfully');
        } catch (\Throwable $th) {
            return Response::error(
                message: "Can't delete information, it may have related records",

            );
        }
    }
}
