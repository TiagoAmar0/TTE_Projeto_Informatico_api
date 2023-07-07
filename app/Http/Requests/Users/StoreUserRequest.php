<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'name' => 'required',
            'email' => 'required|email|unique:App\Models\User,email',
            'type' => ['required', Rule::in(['nurse', 'lead-nurse', 'admin'])]
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tem de preencher o nome',
            'email.required' => 'em de preencher o e-mail',
            'email.email' => 'O e-mail é inválido',
            'email.unique' => 'Já existe um utilizador com este e-mail associado',
            'type.required' => 'Tem de inserir o tipo de utilizador',
            'type.in' => 'Tipo de utilizador inválido'
        ];
    }
}
