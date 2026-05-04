<?php

namespace App\Http\Requests\FACTWM\FACTWM02;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidateInvoiceOcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'params' => ['required'],
            'invoice_number' => ['required'],
            'invoice_file' => ['required', 'file', 'max:2048'],
            'render_dpi' => ['nullable', 'integer', 'min:140'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_file.uploaded' => 'Invoice file must not exceed 2 MB.',
            'invoice_file.max' => 'Invoice file must not exceed 2 MB.',
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
