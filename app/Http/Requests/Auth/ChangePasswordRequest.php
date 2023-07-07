<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'current_password' => 'required|current_password',
            'new_password' => 'required|min:8|confirmed'
        ];
    }

    public function messages()
    {
        return [
          'current_password.required' => 'Tem de preencher a password atual',
          'current_password.current_password' => 'A password atual não está correta',
          'new_password.required' => 'Tem de preencher a nova password',
          'new_password.min' => 'A nova password tem de ter 8 caracteres',
          'new_password.confirmed' => 'A confirmação da nova password está incorreta',
        ];
    }
}
