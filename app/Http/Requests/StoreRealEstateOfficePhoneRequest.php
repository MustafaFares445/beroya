<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRealEstateOfficePhoneRequest extends FormRequest
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
            'real_estate_office_id' => ['required', 'integer', 'exists:real_estate_offices,id'],
            'phone' => ['required', 'string', 'max:30'],
        ];
    }
}
