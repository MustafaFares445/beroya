<?php

namespace App\Http\Requests;

use App\Support\RealEstate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'user_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'gallery_id' => ['required', 'integer'],
            'real_estate_province_id' => ['nullable', 'required_if:permetions_level,2', 'integer', 'exists:provinces,id'],
            'real_estate_office_id' => ['nullable', 'required_if:permetions_level,3,4', 'integer', 'exists:real_estate_offices,id'],
            'real_estate_role' => ['nullable', 'string', 'max:255', Rule::in(RealEstate::realEstateRoleValues())],
            'permetions_level' => ['required', 'integer'],
            'salary' => ['required', 'integer'],
            'phone' => ['required', 'string', 'max:20'],
        ];
    }
}
