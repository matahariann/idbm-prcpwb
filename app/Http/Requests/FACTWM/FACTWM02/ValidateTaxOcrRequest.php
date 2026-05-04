<?php

namespace App\Http\Requests\FACTWM\FACTWM02;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidateTaxOcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'params' => ['required'],
            'tax_invoice' => ['required'],
            'tax_file' => ['required', 'file', 'max:2048'],
            'render_dpi' => ['nullable', 'integer', 'min:140'],
        ];
    }

    public function messages(): array
    {
        return [
            'tax_file.uploaded' => 'Tax file must not exceed 2 MB.',
            'tax_file.max' => 'Tax file must not exceed 2 MB.',
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
