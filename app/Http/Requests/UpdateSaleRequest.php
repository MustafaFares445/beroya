<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
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
            'car_id' => ['required', 'integer', 'min:1'],
            'car_brand' => ['nullable', 'string', 'max:255'],
            'car_model' => ['nullable', 'string', 'max:255'],
            'car_number' => ['nullable', 'string', 'max:255'],
            'car_name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'employee_name' => ['nullable', 'string', 'max:255'],
            'user_comiss' => ['nullable', 'integer', 'min:0'],
            'user_note' => ['nullable', 'string'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_phone' => ['nullable', 'string', 'max:50'],
            'owner_comiss' => ['nullable', 'integer', 'min:0'],
            'owner_comiss_payed' => ['nullable', 'integer', 'min:0'],
            'buyer_name' => ['nullable', 'string', 'max:30'],
            'buyer_phone' => ['nullable', 'string'],
            'buyer_comiss' => ['nullable', 'integer', 'min:0'],
            'buyer_comiss_payed' => ['nullable', 'integer', 'min:0'],
            'contract_type' => ['nullable', Rule::in(['cash', 'installment'])],
            'installment_count' => ['nullable', 'integer', 'min:1', 'max:120'],
            'installment_amount' => ['nullable', 'integer', 'min:0'],
            'installment_start_date' => ['nullable', 'date'],
            'installment_end_date' => ['nullable', 'date'],
            'installment_note' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'date'],
            'user_id' => ['required', 'integer', 'min:1'],
            'owner_id_image' => ['nullable', 'file', 'max:51200'],
            'buyer_id_image' => ['nullable', 'file', 'max:51200'],
            'contract_image' => ['nullable', 'file', 'max:51200'],
        ];
    }
}
