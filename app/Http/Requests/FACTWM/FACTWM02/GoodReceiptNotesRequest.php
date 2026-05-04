<?php

namespace App\Http\Requests\FACTWM\FACTWM02;

use Illuminate\Foundation\Http\FormRequest;

class GoodReceiptNotesRequest extends FormRequest
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
      'grNoteId' => 'required|integer|exists:FACTWM_TRHGR_NOTES,IID',
      'grNumber' => 'required|string',
      'description' => 'required|string|min:1',
      'file' => 'nullable|file|mimes:pdf,doc,docx,xlsx,jpg,png|max:5120'
    ];

    return $rules;
  }
}
