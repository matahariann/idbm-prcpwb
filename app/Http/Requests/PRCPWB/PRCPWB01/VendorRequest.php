<?php

namespace App\Http\Requests\PRCPWB\PRCPWB01;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorRequest extends FormRequest
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
            'vendor_no'   => [
                'required',
                Rule::unique('PRCPWB_MSHVENDORS', 'VVENDORNO')
                    ->whereNull('DDELETE'),
            ],
            'vendor_name' => ['required'],
            'contact'     => ['nullable'],
            'address'     => ['nullable'],
            'import'      => ['nullable'],
        ];

        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            $vendorId = $this->route('vendor')->IID;

            $rules['vendor_no']   = [
                'nullable',
                Rule::unique('PRCPWB_MSHVENDORS', 'VVENDORNO')
                    ->whereNull('DDELETE')
                    ->ignore($vendorId, 'IID'),
            ];
            $rules['vendor_name'] = ['nullable'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'vendor_no.required'   => 'Vendor No is required.',
            'vendor_no.unique'     => 'Vendor No has already been taken.',
            'vendor_name.required' => 'Vendor Name is required.',
            'import.required'      => 'Import status is required.',
        ];
    }
}
