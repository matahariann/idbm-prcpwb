<?php

namespace App\Http\Controllers\Original\FACTWM\FACTWM01;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\DataTables\Original\FACTWM01\FACTWMF003DataTable;
use App\Enums\ChangeRequestStatus;
use App\Helpers\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\FACTWM01\FACTWM_MSHCHANGE_REQUEST_VENDOR as ChangeRequest;
use App\Models\HITUAM01\HITUAM_MSHUSER;
use App\Services\ExcelExportService;
use App\Services\FACTWM\ChangeRequestService;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class FACTWMF003 extends Controller
{
    public function __construct(private ChangeRequestService $changeRequestService) {}
    /**
     * Display a listing of the resource.
     */
    public function index(FACTWMF003DataTable $dataTable)
    {
        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;

        if ($userSupplier) {
            return $dataTable->render('modules.FACTWM.FACTWM01.FACTWMF003.FACTWMF003');
        }

        return view('modules.FACTWM.FACTWM01.FACTWMF003.FACTWMF003-non-vendor');
    }

    public function requestTable()
    {
        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;
        $supplierCode = $userSupplier ? $userSupplier->VSUPPLIER_CODE : null;

        $query = ChangeRequest::query()->when($supplierCode, function ($query) use ($supplierCode) {
            $query->where('VSUPPLIER_CODE', $supplierCode);
        });

        if (!$supplierCode) {
            $query->where('VSTATUS', ChangeRequestStatus::SUBMIT);
        }

        foreach ((array) request()->input('columns', []) as $column) {
            $columnName = $column['name'] ?? null;
            $columnSearch = trim((string) data_get($column, 'search.value', ''));

            if ($columnSearch === '' || empty($columnName)) {
                continue;
            }

            if ($columnName === 'DDOWNLOAD') {
                if (str_contains($columnSearch, ' to ')) {
                    [$startDate, $endDate] = array_map('trim', explode(' to ', $columnSearch, 2));

                    if ($startDate !== '' && $endDate !== '') {
                        $query->whereBetween('DDOWNLOAD', [
                            $startDate . ' 00:00:00',
                            $endDate . ' 23:59:59',
                        ]);
                    }
                } else {
                    $query->whereDate('DDOWNLOAD', $columnSearch);
                }
            }
        }

        $dataTable = datatables($query);

        return $dataTable->toJson();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $type = $request->request_type;

            match ($type) {
                'add'       => $this->changeRequestService->newMember($request),
                'update'    => $this->changeRequestService->updateMember($request),
                default     => $this->changeRequestService->deleteMember($request->id)
            };
        });

        return Response::success(message: "Request created successfully");
    }

    public function submitRequest()
    {
        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;
        $supplierCode = $userSupplier ? $userSupplier->VSUPPLIER_CODE : null;

        DB::transaction(function () use ($supplierCode) {
            ChangeRequest::query()
                ->where('VSTATUS', '!=', ChangeRequestStatus::CANCEL)
                ->where('VSUPPLIER_CODE', $supplierCode)
                ->update([
                    'VSTATUS' => ChangeRequestStatus::SUBMIT
                ]);
        });

        return Response::success(message: 'Request submitted successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $ids = $request->ids;

        DB::transaction(function () use ($ids) {
            ChangeRequest::query()
                ->whereIn('IID', $ids)
                ->update([
                    'BDOWNLOAD' => true,
                    'DDOWNLOAD' => now()
                ]);
        });

        return Response::success();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChangeRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $request->delete();
            });
            // $request->update([
            //     'VSTATUS' => ChangeRequestStatus::CANCEL
            // ]);

            return Response::success(message: "Request canceled successfully");
        } catch (\Throwable $th) {
            return Response::error(message: "Can't delete, it may have related records");
        }
    }

    public function download()
    {
        $users = HITUAM_MSHUSER::whereIn('IID', [1, 22, 21])->get();

        return datatables($users)
            ->make()
            ->export('xlsx');
    }
}
