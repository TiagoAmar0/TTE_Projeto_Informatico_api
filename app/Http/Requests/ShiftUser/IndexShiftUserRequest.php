<?php

namespace App\Http\Requests\ShiftUser;

use Illuminate\Foundation\Http\FormRequest;

class IndexShiftUserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'date' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'date.required' => 'Tem de inserir a data'
        ];
    }
}
