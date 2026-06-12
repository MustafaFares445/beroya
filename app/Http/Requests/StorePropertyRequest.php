<?php

namespace App\Http\Requests;

use App\Support\RealEstate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
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
            'offer_number' => ['required', 'string', 'max:100', Rule::unique('properties', 'offer_number')],
            'office_id' => ['required', 'integer', 'exists:real_estate_offices,id'],
            'main_category_id' => ['required', 'integer', 'exists:property_categories,id'],
            'subcategory_id' => [
                'required',
                'integer',
                Rule::exists('property_subcategories', 'id')->where(
                    fn ($query) => $query->where('property_category_id', $this->input('main_category_id'))
                ),
            ],
            'property_nature' => ['required', 'string', Rule::in(RealEstate::propertyNatureAllowedValues())],
            'title_type' => ['required', 'string', Rule::in(RealEstate::titleTypeValues())],
            'area' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'building' => ['required', 'string', 'max:255'],
            'floor' => ['required', 'string', 'max:50'],
            'direction' => ['required', 'string', 'max:100'],
            'rooms_count' => ['required', 'integer', 'min:0'],
            'area_size' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'integer', 'min:0'],
            'ownership_type' => ['required', 'string', 'max:100'],
            'offer_type' => ['required', 'string', Rule::in(RealEstate::offerTypeValues())],
            'rent_duration' => [
                'nullable',
                'string',
                Rule::in(RealEstate::rentDurationValues()),
                'required_if:offer_type,rent',
            ],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_phone' => ['required', 'string', 'max:30'],
            'status' => ['required', 'string', Rule::in(RealEstate::propertyStatusValues())],
        ];
    }
}
