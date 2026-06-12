<?php

namespace App\Http\Requests;

use App\Models\Property;
use App\Support\RealEstate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePropertySubmissionRequest extends FormRequest
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
            'offer_number' => ['required', 'string', 'max:100', Rule::unique('property_submissions', 'offer_number')],
            'province_id' => ['required_without:office_id', 'integer', 'exists:provinces,id'],
            'office_id' => ['nullable', 'integer', 'exists:real_estate_offices,id'],
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
            'submission_note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $offerNumber = trim((string) $this->input('offer_number'));

                if ($offerNumber === '') {
                    return;
                }

                if (Property::query()->where('offer_number', $offerNumber)->exists()) {
                    $validator->errors()->add('offer_number', 'The offer number has already been taken.');
                }
            },
        ];
    }
}
