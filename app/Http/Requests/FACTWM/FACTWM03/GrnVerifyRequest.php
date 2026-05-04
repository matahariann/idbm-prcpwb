<?php

namespace App\Http\Requests\FACTWM\FACTWM03;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GrnVerifyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'Receipt' => 'nullable|array',
            'Receipt.*.RefTye' => 'required|string',
            'Receipt.*.ReceiptSequence' => 'nullable|integer',
            'Receipt.*.ReceiptNo' => 'nullable|integer',
            'Receipt.*.ReceiptReference' => 'required|string',
            'Receipt.*.ArrivalDate' => 'required|date',
            'Receipt.*.DeliveryDate' => 'nullable|date',
            'Receipt.*.ApprovedDate' => 'nullable|date',
            'Receipt.*.SupplierId' => 'required|string',
            'Receipt.*.NoteId' => 'nullable|integer',
            'Receipt.*.DeliveryNo' => 'required|string',
            'Receipt.*.Name' => 'required|string',
            'Receipt.*.OrderNo' => 'required|string',
            'Receipt.*.LineNo' => 'required|string',
            'Receipt.*.ReleaseNo' => 'required|string',
            'Receipt.*.SourceRef4' => 'nullable',
            'Receipt.*.Contract' => 'nullable|string',
            'Receipt.*.PartNo' => 'nullable|string',
            'Receipt.*.PartDescription' => 'nullable|string',
            'Receipt.*.Uom' => 'required|string',
            'Receipt.*.QtyArrived' => 'required|integer',
            'Receipt.*.ReturnReference' => 'nullable|string',

            'PurchaseOrder' => 'nullable|array',
            'PurchaseOrder.*.OrderNo' => 'required|string',
            'PurchaseOrder.*.LineNo' => 'required|string',
            'PurchaseOrder.*.Objstate' => 'nullable|string',
            'PurchaseOrder.*.ReleaseNo' => 'required|string',
            'PurchaseOrder.*.PartNo' => 'nullable|string',
            'PurchaseOrder.*.Description' => 'nullable|string',
            'PurchaseOrder.*.VendorNo' => 'required|string',
            'PurchaseOrder.*.FbuyUnitPrice' => 'required|numeric',
            'PurchaseOrder.*.CurrencyCode' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'Receipt.required' => 'Receipt data is required',
            'Receipt.array' => 'Receipt must be an array',
            'PurchaseOrder.required' => 'Purchase Order data is required',
            'PurchaseOrder.array' => 'Purchase Order must be an array',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'Receipt.*.RefTye' => 'Reference Type',
            'Receipt.*.ReceiptSequence' => 'Receipt Sequence',
            'Receipt.*.ReceiptNo' => 'Receipt Number',
            'Receipt.*.ReceiptReference' => 'Receipt Reference',
            'Receipt.*.ArrivalDate' => 'Arrival Date',
            'Receipt.*.DeliveryDate' => 'Delivery Date',
            'Receipt.*.ApprovedDate' => 'Approved Date',
            'Receipt.*.SupplierId' => 'Supplier ID',
            'Receipt.*.NoteId' => 'Note ID',
            'Receipt.*.DeliveryNo' => 'Delivery Number',
            'Receipt.*.Name' => 'Name',
            'Receipt.*.OrderNo' => 'Order Number',
            'Receipt.*.LineNo' => 'Line Number',
            'Receipt.*.ReleaseNo' => 'Release Number',
            'Receipt.*.SourceRef4' => 'Source Reference 4',
            'Receipt.*.Contract' => 'Contract',
            'Receipt.*.PartNo' => 'Part Number',
            'Receipt.*.PartDescription' => 'Part Description',
            'Receipt.*.Uom' => 'Unit of Measure',
            'Receipt.*.QtyArrived' => 'Quantity Arrived',

            'PurchaseOrder.*.OrderNo' => 'Order Number',
            'PurchaseOrder.*.LineNo' => 'Line Number',
            'PurchaseOrder.*.Objstate' => 'Object State',
            'PurchaseOrder.*.ReleaseNo' => 'Release Number',
            'PurchaseOrder.*.PartNo' => 'Part Number',
            'PurchaseOrder.*.Description' => 'Description',
            'PurchaseOrder.*.VendorNo' => 'Vendor Number',
            'PurchaseOrder.*.FbuyUnitPrice' => 'Foreign Buy Unit Price',
            'PurchaseOrder.*.CurrencyCode' => 'Currency Code',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
