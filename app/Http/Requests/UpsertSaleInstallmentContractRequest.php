<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpsertSaleInstallmentContractRequest extends FormRequest
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
            'installment_count' => ['required', 'integer', 'min:1', 'max:120'],
            'installment_amount' => ['required', 'integer', 'min:0'],
            'installment_start_date' => ['required', 'date'],
            'installment_end_date' => ['required', 'date', 'after_or_equal:installment_start_date'],
            'installment_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
