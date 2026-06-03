<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:50'],
            'client_phone' => ['required', 'string', 'max:20'],
            'car_market' => ['required', 'string', 'max:20'],
            'car_model' => ['required', 'string', 'max:20'],
            'year' => ['required', 'string', 'max:10'],
            'price_low' => ['required', 'integer', 'min:0'],
            'price_high' => ['required', 'integer', 'min:0'],
            'order_state' => ['required', 'string', 'max:30'],
            'order_notes' => ['required', 'string'],
            'user_name' => ['required', 'string', 'max:200'],
            'gallery_name' => ['required', 'string', 'max:100'],
        ];
    }
}
