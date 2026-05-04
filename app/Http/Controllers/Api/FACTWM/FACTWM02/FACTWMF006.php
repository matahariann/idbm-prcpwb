<?php

namespace App\Http\Controllers\Api\FACTWM\FACTWM02;

use App\Helpers\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\FACTWM\FACTWM03\GrnVerifyRequest;
use App\Services\FACTWM\GoodReceiptNotesService;
use Illuminate\Support\Facades\DB;

class FACTWMF006 extends Controller
{
    public function __construct(private GoodReceiptNotesService $goodReceiptNotesService) {}

    public function store(GrnVerifyRequest $request)
    {
        try {
            $validated = $request->validated();

            return DB::transaction(function () use ($validated) {

                $this->goodReceiptNotesService->saveReceipts($validated['Receipt'], $validated['PurchaseOrder']);
                // $this->goodReceiptNotesService->savePurchaseOrders(
                //     $validated['PurchaseOrder'],
                //     $validated['Receipt']
                // );

                return response()->json(
                    $this->goodReceiptNotesService->mapResponse($validated),
                    200
                );
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    public function update()
    {
        return Response::success(message: 'This is GRN update request');
    }

    public function destroy()
    {
        return Response::success(message: 'This is GRN delete request');
    }
}
