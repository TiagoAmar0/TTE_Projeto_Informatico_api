<?php

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
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
            'name' => ['required', 'unique:services,name,'.$this->id],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tem de inserir o nome do serviço',
            'name.unique' => 'Já existe um serviço com este nome',
        ];
    }
}
