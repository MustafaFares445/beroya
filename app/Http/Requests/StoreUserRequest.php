<?php

namespace App\Http\Requests;

use App\Models\User;
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
        $usesRealEstatePermissions = $this->usesRealEstatePermissions();
        $permissionLevel = (int) $this->input('permetions_level');
        $provinceRules = ['nullable'];
        $officeRules = ['nullable'];

        if ($usesRealEstatePermissions) {
            if ($permissionLevel === 1) {
                $provinceRules[] = 'prohibited';
                $officeRules[] = 'prohibited';
            }

            if ($permissionLevel === 2) {
                $provinceRules[] = 'required';
                $officeRules[] = 'prohibited';
            }

            if (in_array($permissionLevel, [3, 4], true)) {
                $provinceRules[] = 'prohibited';
                $officeRules[] = 'required';
            }
        } else {
            $provinceRules[] = 'prohibited';
            $officeRules[] = 'prohibited';
        }

        $provinceRules = [...$provinceRules, 'integer', 'exists:provinces,id'];
        $officeRules = [...$officeRules, 'integer', 'exists:real_estate_offices,id'];

        return [
            'user_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'gallery_id' => ['required', 'integer'],
            'real_estate_province_id' => $provinceRules,
            'real_estate_office_id' => $officeRules,
            'real_estate_role' => $usesRealEstatePermissions
                ? [
                    'nullable',
                    'required_if:permetions_level,1',
                    'string',
                    'max:255',
                    Rule::in($permissionLevel === 1 ? ['general_manager'] : RealEstate::realEstateRoleValues()),
                ]
                : ['nullable', 'prohibited'],
            'permetions_level' => ['required', 'integer'],
            'salary' => ['required', 'integer'],
            'phone' => ['required', 'string', 'max:20'],
        ];
    }

    private function usesRealEstatePermissions(): bool
    {
        /** @var User|null $authenticatedUser */
        $authenticatedUser = $this->user();

        return ($authenticatedUser?->isRealEstateUser() ?? false)
            || $this->filled('real_estate_province_id')
            || $this->filled('real_estate_office_id')
            || $this->filled('real_estate_role');
    }
}
