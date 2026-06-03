<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_comiss' => ['nullable', 'integer', 'min:0'],
            'owner_comiss' => ['nullable', 'integer', 'min:0'],
            'owner_comiss_payed' => ['nullable', 'integer', 'min:0'],
            'buyer_comiss' => ['nullable', 'integer', 'min:0'],
            'buyer_comiss_payed' => ['nullable', 'integer', 'min:0'],
            'employee_name' => ['nullable', 'string', 'max:255'],
            'user_note' => ['nullable', 'string'],
        ];
    }
}
