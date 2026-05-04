<?php

namespace App\Services\FACTWM;

use App\Models\FACTWM02\FACTWM_TRDGR_NOTE_DETAILS;
use App\Models\FACTWM02\FACTWM_TRHGR_NOTES;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class GoodReceiptNotesService
{
    public function sync()
    {
        $url = null;
        $dummy = json_decode(File::get(base_path('dummy/sync-good-receipt-notes.json')), true);

        if ($url) {
        } else {
            return $dummy;
        }
    }

    public function saveReceipts(array $receipts, array $po): void
    {
        foreach ($receipts as $index => $receiptData) {
            $poData = $po[$index] ?? null;
            if (! $poData) {
                continue;
            }

            $grNote = FACTWM_TRHGR_NOTES::firstOrNew([
                'VGR_NUMBER' => $receiptData['ReceiptReference'],
                'VVENDOR_CODE' => $receiptData['SupplierId'],
                'VREF_TYPE' => $receiptData['RefTye'],
            ]);

            if (! $grNote->exists) { // kondisi jika data belum ada, maka simpan data baru
                $grNote->fill($this->mapReceiptToHeader($receiptData));
                $grNote->save();
            } else { // kondisi jika data sudah ada sebelumnya tetapi dengan VSTATUS DISPUTED
                if ($grNote->VSTATUS === 'DISPUTED' || str_starts_with($grNote->VSTATUS, 'DISPUTED-')) { // maka create baru
                    $grNote = FACTWM_TRHGR_NOTES::create($this->mapReceiptToHeader($receiptData));
                }
            }

            $detail = FACTWM_TRDGR_NOTE_DETAILS::firstOrNew([
                'VGR_NUMBER' => $receiptData['ReceiptReference'],
                'VMATERIAL_CODE' => $poData['PartNo'],
                'IRECEIPT_NO' => $receiptData['ReceiptNo'],
            ]);

            $detail->fill($this->mapPurchaseOrderToDetail($poData, $receiptData));
            $detail->IID_GR_NOTE = $grNote->IID;
            $detail->save();
        }
    }

    public function mapReceiptToHeader(array $data): array
    {
        return [
            'VREF_TYPE' => $data['RefTye'],
            'VRECEIPT_SEQUENCE' => $data['ReceiptSequence'],
            'IRECEIPT_NO' => $data['ReceiptNo'],
            'VGR_NUMBER' => $data['ReceiptReference'],
            'DGR' => $data['ArrivalDate'],
            'DDELIVERY_DATE' => $data['DeliveryDate'],
            'DAPPROVAL_DATE' => $data['ApprovedDate'],
            'VVENDOR_CODE' => $data['SupplierId'],
            'VNOTEID' => $data['NoteId'],
            'VDELIVERY_NUMBER' => $data['DeliveryNo'],
            'VVENDOR_NAME' => $data['Name'],
            'VPO_NUMBER' => $data['OrderNo'],
            'VSOURCEREF4' => $data['SourceRef4'],
            'VCONTRACTNO' => $data['Contract'],
            'VRETURN_REF' => $data['ReturnReference'],
            'VSTATUS' => 'NEW',
            'DSYNC' => now(),
            'VCREA' => Auth::user()->username ?? 'SYSTEM',
            'DCREA' => now(),
        ];
    }

    public function savePurchaseOrders(array $purchaseOrders, array $receipts): void
    {
        foreach ($purchaseOrders as $poData) {

            $receipt = collect($receipts)->first(
                fn($r) => $r['OrderNo'] === $poData['OrderNo']
            );

            if (! $receipt) {
                continue;
            }

            $detail = FACTWM_TRDGR_NOTE_DETAILS::firstOrNew([
                'VGR_NUMBER' => $receipt['ReceiptReference'],
                'VMATERIAL_CODE' => $poData['PartNo'],
                'IRECEIPT_NO' => $receipt['ReceiptNo'],
            ]);

            $detail->fill(
                $this->mapPurchaseOrderToDetail($poData, $receipt)
            );

            $detail->save();
        }
    }

    protected function mapPurchaseOrderToDetail(array $po, array $receipt): array
    {
        $qty = $receipt['QtyArrived'] ?? 0;
        $price = $po['FbuyUnitPrice'];

        return [
            'VORDER_NO' => $po['OrderNo'],
            'VLINE_NO' => $po['LineNo'],
            'VRELEASE_NO' => $po['ReleaseNo'],
            'VGR_NUMBER' => $receipt['ReceiptReference'],
            'VMATERIAL_CODE' => $po['PartNo'],
            'VDESCRIPTION' => $po['Description'],
            'IQTY' => $qty,
            'UOM' => $receipt['Uom'] ?? null,
            'VPRICE' => $price,
            'VAMOUNT' => $qty * $price,
            'VOBJ_STATE' => $po['Objstate'],
            'VCURRENCY' => $po['CurrencyCode'],
            'DGR' => $receipt['ArrivalDate'],
            'DSYNC' => now(),
            'VCREA' => Auth::user()->username ?? 'SYSTEM',
            'DCREA' => now(),
            'VRECEIPT_SEQUENCE' => $receipt['ReceiptSequence'],
            'IRECEIPT_NO' => $receipt['ReceiptNo'],
        ];
    }

    public function mapResponse(array $validated): array
    {
        return [
            'Return' => $this->mapReturnData($validated['Receipt']),
            'PurchaseOrder' => $this->mapPurchaseOrderResponse(
                $validated['PurchaseOrder']
            ),
        ];
    }

    protected function mapReturnData(array $receipts): array
    {
        return array_map(function ($r) {
            return [
                'RefTye' => $r['RefTye'],
                'ReceiptSequence' => $r['ReceiptSequence'],
                'ReceiptNo' => $r['ReceiptNo'],
                'ReceiptReference' => $r['ReceiptReference'],
                'ArrivalDate' => $r['ArrivalDate'],
                'DeliveryDate' => $r['DeliveryDate'],
                'ApprovedDate' => $r['ApprovedDate'],
                'SupplierId' => $r['SupplierId'],
                'NoteId' => $r['NoteId'],
                'DeliveryNo' => $r['DeliveryNo'],
                'Name' => $r['Name'],
                'OrderNo' => $r['OrderNo'],
                'LineNo' => $r['LineNo'],
                'ReleaseNo' => $r['ReleaseNo'],
                'SourceRef4' => $r['SourceRef4'],
                'Contract' => $r['Contract'],
                'PartNo' => $r['PartNo'],
                'PartDescription' => $r['PartDescription'],
                'BuyUnitMeas' => $r['Uom'],
                'PurchQty' => $r['QtyArrived'],
                'ShipmentId' => 13,
            ];
        }, $receipts);
    }

    protected function mapPurchaseOrderResponse(array $purchaseOrders): array
    {
        return array_map(function ($po) {
            return [
                'OrderNo' => $po['OrderNo'],
                'LineNo' => $po['LineNo'],
                'Objstate' => $po['Objstate'],
                'ReleaseNo' => $po['ReleaseNo'],
                'PartNo' => $po['PartNo'],
                'Description' => $po['Description'],
                'VendorNo' => $po['VendorNo'],
                'FbuyUnitPrice' => $po['FbuyUnitPrice'],
                'CurrencyCode' => $po['CurrencyCode'],
            ];
        }, $purchaseOrders);
    }
}
