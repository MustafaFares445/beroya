<?php

namespace App\Http\Requests;

use App\Support\RealEstate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPropertyRequest extends FormRequest
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
            'province_id' => ['nullable', 'integer', 'min:1'],
            'office_id' => ['nullable', 'integer', 'min:1'],
            'main_category_id' => ['nullable', 'integer', 'min:1'],
            'subcategory_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in(RealEstate::propertyStatusValues())],
            'title_type' => ['nullable', 'string', Rule::in(RealEstate::titleTypeValues())],
            'offer_type' => ['nullable', 'string', Rule::in(RealEstate::offerTypeValues())],
            'ownership_type' => ['nullable', 'string', 'max:100'],
            'property_nature' => ['nullable', 'string', Rule::in(RealEstate::propertyNatureAllowedValues())],
            'rent_duration' => ['nullable', 'string', Rule::in(RealEstate::rentDurationValues())],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
