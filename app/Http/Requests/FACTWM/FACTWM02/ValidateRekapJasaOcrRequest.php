<?php

namespace App\Http\Requests\FACTWM\FACTWM02;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidateRekapJasaOcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'params' => ['required'],
            'rekap_jasa_file' => ['required', 'file', 'max:2048'],
            'render_dpi' => ['nullable', 'integer', 'min:140'],
        ];
    }

    public function messages(): array
    {
        return [
            'rekap_jasa_file.uploaded' => 'Rekap Jasa file must not exceed 2 MB.',
            'rekap_jasa_file.max' => 'Rekap Jasa file must not exceed 2 MB.',
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
