<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'password' => 'required|confirmed|min:8',
            'token' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'password.required' => 'Tem de preencher a nova password',
            'password.confirmed' => 'A confirmação da nova password está incorreta',
            'password.min' => 'A nova password tem de ter 8 caracteres',
            'token.required' => 'Pedido inválido'
        ];
    }
}
