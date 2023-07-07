<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
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
            'draft' => ['required', 'boolean'],
            'data' => ['required', 'array'],
            'date_range' => ['required', 'array'],
            'date_range.*' => ['date'],
            'data.*.nurses_total' => ['required', 'numeric'],
            'data.*.date' => ['required'],
            'data.*.date_formatted' => ['required'],
        ];
    }

    public function messages()
    {
        return [
            'draft.required' => 'Pedido inválido',
            'draft.boolean' => 'Pedido inválido',
            'date_range.required' => 'Tem que introduzir o intervalo de datas',
            'date_range.array' => 'Tem que introduzir o intervalo de datas',
            'date_range.*.date' => 'Intervalo de datas inválido',
            'data.*.nurses_total.required' => 'Pedido inválido',
            'data.*.nurses_total.numeric' => 'Pedido inválido',
            'data.*.date' => 'Pedido inválido',
            'data.*.date_formatted' => 'Pedido inválido',
        ];
    }
}
