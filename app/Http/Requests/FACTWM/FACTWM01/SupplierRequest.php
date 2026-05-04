<?php

namespace App\Http\Requests\FACTWM\FACTWM01;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
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
            'supplier_code' => [
                'required',
                Rule::unique('FACTWM_MSHSUPPLIERS', 'VSUPPLIER_CODE')
                    ->whereNull('DDELETE')
            ],
            'supplier_name' => ['required'],
            'npwp' => ['nullable', 'required_without:nik', 'digits:16'],
            'nik'  => ['nullable', 'required_without:npwp', 'digits:16'],
            'pkp' => ['required'],

        ];

        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            $vendorId = $this->route('vendor')->IID;

            $rules['supplier_code'] = ['nullable', Rule::unique('FACTWM_MSHSUPPLIERS', 'VSUPPLIER_CODE')->whereNull('DDELETE')->ignore($vendorId, 'IID')];
            $rules['supplier_name'] = ['nullable'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'supplier_code.required' => 'Supplier Code is required.',
            'supplier_code.unique'   => 'Supplier Code has already been taken.',
            'supplier_name.required' => 'Supplier Name is required.',
            'npwp.required_without'  => 'The NPWP field is required when NIK is not provided.',
            'nik.required_without'   => 'The NIK field is required when NPWP is not provided.',
            'npwp.digits'            => 'NPWP must be exactly 16 digits.',
            'nik.digits'             => 'NIK must be exactly 16 digits.',
            'pkp.required'           => 'PKP status is required.',
        ];
    }
}
