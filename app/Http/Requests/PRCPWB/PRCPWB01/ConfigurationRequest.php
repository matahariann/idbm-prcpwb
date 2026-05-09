<?php

namespace App\Http\Requests\PRCPWB\PRCPWB01;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ConfigurationRequest extends FormRequest
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
            'variable' => [
                'required',
                function ($attribute, $value, $fail) {
                    $exists = DB::connection('prcpwb')
                        ->table('PRCPWB_MSHCONFIGURATIONS')
                        ->where('VVARIABLE', $value)
                        ->whereNull('DDELETE')
                        ->exists();

                    if ($exists) {
                        $fail('The variable has already been taken.');
                    }
                },
            ],
            'value' => 'required',
        ];

        if ($this->method() === 'PUT' || $this->method() === 'PATCH') {
            $configurationId = $this->route('configuration')->IID;

            $rules['variable'] = [
                'required',
                function ($attribute, $value, $fail) use ($configurationId) {  // ← pastikan ada 'use ($configurationId)'
                    $exists = DB::connection('prcpwb')
                        ->table('PRCPWB_MSHCONFIGURATIONS')
                        ->where('VVARIABLE', $value)
                        ->whereNull('DDELETE')
                        ->where('IID', '!=', $configurationId)
                        ->exists();

                    if ($exists) {
                        $fail('The variable has already been taken.');
                    }
                },
            ];
        }

        return $rules;
    }
}
