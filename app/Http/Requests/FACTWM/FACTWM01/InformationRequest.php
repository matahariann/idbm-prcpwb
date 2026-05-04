<?php

namespace App\Http\Requests\FACTWM\FACTWM01;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InformationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'VNOTES' => 'required|string',
            'DFROM' => 'required|date',
            'DTO' => 'required|date|after_or_equal:DFROM',
            'VUSER_TYPE' => 'required|in:all,internal,supplier',
            'VFILE_INFORMATION' => $isUpdate
                ? 'nullable|file|mimes:pdf|max:10240'
                : 'required|file|mimes:pdf|max:10240',
            'VUPDLOAD_FOTO_ASSET' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ];

        // VVIEWERS only required when VUSER_TYPE is 'supplier'
        if ($this->input('VUSER_TYPE') === 'supplier') {
            $rules['VVIEWERS'] = 'required|array|min:1';
            $rules['VVIEWERS.*'] = 'string';
        } else {
            $rules['VVIEWERS'] = 'nullable|array';
            $rules['VVIEWERS.*'] = 'string';
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'VNOTES.required' => 'Notes field is required',
            'VNOTES.string' => 'Notes must be a string',
            // 'VNOTES.max' => 'Notes may not be greater than 1000 characters',
            'DFROM.required' => 'From date is required',
            'DFROM.date' => 'From date must be a valid date',
            'DTO.required' => 'To date is required',
            'DTO.date' => 'To date must be a valid date',
            'DTO.after_or_equal' => 'To date must be after or equal to from date',
            'VUSER_TYPE.required' => 'User type is required',
            'VUSER_TYPE.in' => 'User type must be all, internal or supplier',
            'VVIEWERS.required' => 'Please select at least one supplier',
            'VVIEWERS.array' => 'Viewers must be an array',
            'VVIEWERS.min' => 'Please select at least one supplier',
            'VFILE_INFORMATION.required' => 'PDF information file is required',
            'VFILE_INFORMATION.file' => 'PDF information must be a file',
            'VFILE_INFORMATION.mimes' => 'PDF information must be a PDF file',
            'VFILE_INFORMATION.max' => 'PDF information may not be greater than 10MB',
            'VUPDLOAD_FOTO_ASSET.image' => 'Asset file must be an image file',
            'VUPDLOAD_FOTO_ASSET.mimes' => 'Asset file must be an image file (jpeg, jpg, png, gif, webp)',
            'VUPDLOAD_FOTO_ASSET.max' => 'Asset file may not be greater than 5MB',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // If VUSER_TYPE is not 'supplier', remove VVIEWERS to avoid validation issues
        if ($this->input('VUSER_TYPE') !== 'supplier') {
            $this->request->remove('VVIEWERS');
        }
    }
}
