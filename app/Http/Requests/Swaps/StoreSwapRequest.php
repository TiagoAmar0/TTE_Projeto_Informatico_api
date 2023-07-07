<?php

namespace App\Http\Requests\Swaps;

use Illuminate\Foundation\Http\FormRequest;

class StoreSwapRequest extends FormRequest
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
            'user_shift.id' => ['required', 'exists:App\Models\ShiftUser,id'],
            'swaps' => ['required','array'],
            'swaps.*.user_id' => ['required', 'exists:App\Models\User,id'],
            'swaps.*.date' => ['required', 'date'],
            'swaps.*.shift_user_id' => ['required', 'exists:App\Models\ShiftUser,id'],
            'swaps.*.rest' => ['required', 'boolean']
        ];
    }

    public function messages()
    {
        return [
            'user_shift.id.required' => 'Pedido inválido',
            'user_shift.id.exists' => 'Pedido inválido',
            'swaps.required' => 'Pedido inválido',
            'swaps.array' => 'Pedido inválido',
            'swaps.*.user_id.required' => 'Pedido inválido',
            'swaps.*.user_id.exists' => 'Pedido inválido',
            'swaps.*.date.required' => 'Pedido inválido',
            'swaps.*.date.date' => 'Pedido inválido',
            'swaps.*.shift_user_id.required' => 'Pedido inválido',
            'swaps.*.shift_user_id.exists' => 'Pedido inválido',
            'swaps.*.rest.required' => 'Pedido inválido',
            'swaps.*.rest.boolean' => 'Pedido inválido'
        ];
    }
}
