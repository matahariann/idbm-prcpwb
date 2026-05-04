<?php

namespace App\Http\Requests\FACTWM\FACTWM02;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyPoRequest extends FormRequest
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
        $hasDraftId = !empty($this->input('draft_id'));

        $rules = [
            'draft_id' => ['nullable'],
            'invoice_number' => [
                'required',
                // Rule::unique('FACTWM_TRHVERIFY_PO', 'VINVOICE_NUMBER')
            ],
            'invoice_date' => ['required'],
            'tax_invoice' => ['nullable'],
            'npwp_supplier' => ['required'],
            'tax_invoice_date' => ['nullable'],
            'invoice_file' => [$hasDraftId ? 'nullable' : 'required'],
            'tax_file' => ['nullable'],
            'dpp-pph' => ['nullable'],
            'gr_number' => ['required'],
            'pph' => ['nullable'],
            'total' => ['required'],
            'ppn' => ['required'],
            'dpp' => ['required'],
            'net-amount' => ['required'],
            'object' => ['nullable'],
            'dpp-pph' => ['nullable'],
            'tarrif' => ['nullable'],
            'value' => ['nullable'],
            'otherFiles' => ['nullable'],
            'rekap-jasa-pph' => ['nullable'],
        ];

        return $rules;
    }
}
