<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM01;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FACTWM\InformationService;
use App\DataTables\Original\FACTWM01\FACTWMF005DataTable as InformationTable;
use App\Http\Requests\FACTWM\FACTWM01\InformationRequest;
use Illuminate\Support\Facades\DB;
use App\Models\FACTWM01\FACTWM_MSHINFORMATION as Information;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Response;
use App\Services\FACTWM\SupplierService;

// Controller Master Information
class FACTWMF005 extends Controller
{
    public function __construct(private SupplierService $supplierService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(InformationTable $dataTable)
    {
        return $dataTable->render('modules.FACTWM.FACTWM01.FACTWMF005.FACTWMF005');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $vendorData = $this->supplierService->getAllSuppliers(request: request());
        return view('modules.FACTWM.FACTWM01.FACTWMF005.partials._information-form', [
            'vendorData' => $vendorData,
            'information' => null
        ]);
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
                $uploadFotoAsset = null;

                if ($request->hasFile('VUPDLOAD_FOTO_ASSET')) {
                    $uploadFotoAsset = $request->file('VUPDLOAD_FOTO_ASSET')->store('information/assets', 'public');
                }

                // Handle viewers based on user type
                $viewers = null;
                if ($data['VUSER_TYPE'] == 'supplier') {
                    // Only process VVIEWERS if user type is supplier
                    $viewersInput = $request->input('VVIEWERS', []);

                    if (!empty($viewersInput) && !in_array('all', $viewersInput)) {
                        $viewers = $viewersInput;
                    } elseif (in_array('all', $viewersInput)) {
                        $viewers = $this->supplierService->getAllSuppliers(request: $request)
                            ->pluck('IID')
                            ->toArray();
                    } else {
                        $viewers = [];
                    }
                    $viewers = !empty($viewers) ? $viewers : null;
                }
                // For 'all' or 'internal', viewers remains null

                // Create information record
                $informationData = [
                    'VNOTES' => $data['VNOTES'],
                    'DFROM' => $data['DFROM'],
                    'DTO' => $data['DTO'],
                    'VUSER_TYPE' => $data['VUSER_TYPE'],
                    'VVIEWERS' => $viewers,
                    'VFILE_INFORMATION' => $fileInformation,
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
            $vendorData = $this->supplierService->getAllSuppliers(request: request());
            // Mapping: Convert VVIEWERS jika sama dengan semua vendor
            if ($information && $information->VVIEWERS) {
                $selectedVendors = is_array($information->VVIEWERS)
                    ? $information->VVIEWERS
                    : explode(',', $information->VVIEWERS);

                // Ambil semua ID vendor untuk perbandingan
                $allVendorIds = $vendorData->pluck('IID')->toArray();

                // Cek apakah jumlah selected vendors sama dengan total vendors
                // DAN semua ID vendor ada di selected vendors
                if (count($selectedVendors) === count($allVendorIds)) {
                    // Sort untuk memastikan perbandingan akurat
                    sort($selectedVendors);
                    sort($allVendorIds);

                    // Jika sama persis, replace dengan "all"
                    if ($selectedVendors === $allVendorIds) {
                        $information->VVIEWERS = ['all'];
                    }
                }
            }
            return view('modules.FACTWM.FACTWM01.FACTWMF005.partials._information-form', compact('information', 'vendorData'));
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
                ];

                // Handle viewers based on user type
                $viewers = null;
                if ($data['VUSER_TYPE'] == 'supplier') {
                    // Only process VVIEWERS if user type is supplier
                    $viewersInput = $request->input('VVIEWERS', []);

                    if (!empty($viewersInput) && !in_array('all', $viewersInput)) {
                        $viewers = $viewersInput;
                    } elseif (in_array('all', $viewersInput)) {
                        $viewers = $this->supplierService->getAllSuppliers(request: $request)
                            ->pluck('IID')
                            ->toArray();
                    } else {
                        $viewers = [];
                    }
                    $viewers = !empty($viewers) ? $viewers : null;
                }
                // For 'all' or 'internal', viewers remains null

                $updateData['VVIEWERS'] = $viewers;

                // Handle PDF file update
                if ($request->hasFile('VFILE_INFORMATION')) {
                    if ($information->VFILE_INFORMATION) {
                        Storage::disk('public')->delete($information->VFILE_INFORMATION);
                    }
                    $updateData['VFILE_INFORMATION'] = $request->file('VFILE_INFORMATION')
                        ->store('information/pdf', 'public');
                }

                // Handle asset file update
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

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        DB::transaction(function () use ($ids) {
            $informationItems = Information::whereIn('IID', $ids)->get();
            foreach ($informationItems as $information) {
                if ($information->VFILE_INFORMATION) {
                    Storage::disk('public')->delete($information->VFILE_INFORMATION);
                }
                if ($information->VUPDLOAD_DATA_VENDOR) {
                    Storage::disk('public')->delete($information->VUPDLOAD_DATA_VENDOR);
                }
                if ($information->VUPDLOAD_FOTO_ASSET) {
                    Storage::disk('public')->delete($information->VUPDLOAD_FOTO_ASSET);
                }
                //delete record
                $information->delete();
            }
            return Response::success(message: 'Selected News deleted successfully');
        });
    }
}
