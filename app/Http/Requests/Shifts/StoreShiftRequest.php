<?php

namespace App\Http\Requests\Shifts;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
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
            'description' => 'required',
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'nurses_qty' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tem de inserir a abreviatura do turno',
            'description.required' => 'Tem de inserir a descrição do turno',
            'start.required' => 'Tem de inserir o início do turno',
            'start.date_format' => 'Hora inválida de início do turno',
            'end.required' => 'Tem de inserir o fim do turno',
            'end.required.date_format' => 'Hora inválida de fim do turno',
            'nurses_qty.required' => 'Tem de inserir a quantidade de enfermeiros',
            'nurses_qty.numeric' => 'Quantidade de enfermeiros inválida',
            'nurses_qty.min' => 'Quantidade de enfermeiros inválida',
        ];
    }
}
