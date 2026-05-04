<?php

namespace App\Http\Requests\FACTWM\FACTWM03;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
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
        $type = $this->input('type');

        return [
            'supplier' => $type == 'internal' ? 'required' : 'nullable',
            'date' => 'required',
            'file' =>  'required|file|mimes:pdf|max:10240',
        ];
    }
}
