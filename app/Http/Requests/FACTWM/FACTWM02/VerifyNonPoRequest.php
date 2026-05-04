<?php

namespace App\Http\Requests\FACTWM\FACTWM02;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyNonPoRequest extends FormRequest
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
        $rules = [
            'invoice_number' => [
                'required'
                // Rule::unique('FACTWM_TRHVERIFY_NON_PO', 'VINV_NO_SUPPLIER')
            ],
            'invoice_date' => ['required'],
            'invoice_pdf' => ['nullable'],
            'tax_code' => ['required'],
            'tax_date' => ['nullable'],
            'tax_number_supplier' => ['nullable'],
            'tax_pdf' => ['nullable'],
            'dpp_pph' => ['nullable'],
            'dpp_lain' => ['nullable'],
            'pph' => ['nullable'],
            'ppn' => ['nullable'],
            'object' => ['nullable'],
            'tarrif' => ['nullable'],
            'nilai' => ['nullable'],
            'net_amount' => ['nullable'],
            'grand_total' => ['nullable'],
            'npwp_supplier' => ['nullable'],
            'npwp_supplier' => ['nullable'],
            'details' => ['required'],
            'otherFiles' => ['nullable'],
        ];


        // if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
        //     $id = $this->route('nonPo')->IID;

        //     $rules['invoice_number'] = ['required', Rule::unique('FACTWM_TRHVERIFY_NON_PO', 'VINV_NO_SUPPLIER')->whereNull('DDELETE')->ignore($id, 'IID')];
        // }

        return $rules;
    }
}
