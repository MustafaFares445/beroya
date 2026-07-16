<?php

namespace App\Support;

use App\Models\RealEstateOffice;
use App\Models\User;

final class RealEstate
{
    /**
     * @return array<int, string>
     */
    public static function provinceNames(): array
    {
        return array_values((array) config('real_estate.provinces', []));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function taxonomy(): array
    {
        return (array) config('real_estate.taxonomy', []);
    }

    /**
     * @return array<int, array{value: string, label: string, aliases?: array<int, string>}>
     */
    public static function propertyNatureOptions(): array
    {
        return array_values((array) config('real_estate.property_natures', []));
    }

    /**
     * @return array<int, string>
     */
    public static function propertyNatureValues(): array
    {
        return self::optionValues(self::propertyNatureOptions());
    }

    /**
     * @return array<int, string>
     */
    public static function propertyNatureAllowedValues(): array
    {
        $values = [];

        foreach (self::propertyNatureOptions() as $option) {
            $values[] = (string) ($option['value'] ?? '');

            foreach (($option['aliases'] ?? []) as $alias) {
                $values[] = (string) $alias;
            }
        }

        return array_values(array_unique(array_filter($values, static fn (string $value): bool => $value !== '')));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function titleTypeOptions(): array
    {
        return array_values((array) config('real_estate.title_types', []));
    }

    /**
     * @return array<int, string>
     */
    public static function titleTypeValues(): array
    {
        return self::optionValues(self::titleTypeOptions());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function offerTypeOptions(): array
    {
        return array_values((array) config('real_estate.offer_types', []));
    }

    /**
     * @return array<int, string>
     */
    public static function offerTypeValues(): array
    {
        return self::optionValues(self::offerTypeOptions());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function rentDurationOptions(): array
    {
        return array_values((array) config('real_estate.rent_durations', []));
    }

    /**
     * @return array<int, string>
     */
    public static function rentDurationValues(): array
    {
        return self::optionValues(self::rentDurationOptions());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function propertyStatusOptions(): array
    {
        return array_values((array) config('real_estate.property_statuses', []));
    }

    /**
     * @return array<int, string>
     */
    public static function propertyStatusValues(): array
    {
        return self::optionValues(self::propertyStatusOptions());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function realEstateRoleOptions(): array
    {
        return array_values(array_map(
            static fn (array $role): array => [
                'value' => (string) ($role['value'] ?? ''),
                'label' => (string) ($role['label'] ?? ''),
            ],
            (array) config('real_estate.real_estate_roles', [])
        ));
    }

    /**
     * @return array<int, string>
     */
    public static function realEstateRoleValues(): array
    {
        return self::optionValues(self::realEstateRoleOptions());
    }

    public static function roleLabel(?string $role, ?int $permissionLevel = null): ?string
    {
        if ($role === null || trim($role) === '') {
            return self::roleLabelFromPermissionLevel($permissionLevel);
        }

        $definition = (array) config('real_estate.real_estate_roles.'.$role, []);
        if (isset($definition['label']) && is_string($definition['label']) && $definition['label'] !== '') {
            return $definition['label'];
        }

        if (in_array($role, ['مدير محافظة', 'مدير مكتب', 'موظف مكتب'], true)) {
            return $role;
        }

        if (in_array($role, ['reviewer', 'office_manager'], true)) {
            return 'مدير مكتب';
        }

        if (in_array($role, ['province_manager'], true)) {
            return 'مدير محافظة';
        }

        return self::roleLabelFromPermissionLevel($permissionLevel) ?? 'موظف مكتب';
    }

    private static function roleLabelFromPermissionLevel(?int $permissionLevel): ?string
    {
        $role = match ($permissionLevel) {
            1 => 'general_manager',
            2 => 'province_manager',
            3 => 'office_manager',
            4 => 'office_employee',
            default => null,
        };

        if ($role === null) {
            return null;
        }

        $label = config('real_estate.real_estate_roles.'.$role.'.label');

        return is_string($label) && $label !== '' ? $label : null;
    }

    public static function canCreateLookupData(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2, 3], true);
    }

    public static function canManageLookupData(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2], true);
    }

    public static function canManageProperties(User $user): bool
    {
        return in_array((int) $user->permetions_level, [1, 2, 3], true)
            || (int) ($user->real_estate_office_id ?? 0) > 0;
    }

    public static function canReviewPropertySubmissions(User $user): bool
    {
        if (in_array((int) $user->permetions_level, [1, 2], true)) {
            return true;
        }

        return in_array(
            self::roleLabel($user->real_estate_role, $user->permetions_level),
            ['مدير محافظة', 'مدير مكتب'],
            true
        );
    }

    /**
     * @param  array<int, array{value: string, label: string}>  $options
     * @return array<int, string>
     */
    private static function optionValues(array $options): array
    {
        return array_values(array_filter(array_map(
            static fn (array $option): string => (string) ($option['value'] ?? ''),
            $options
        ), static fn (string $value): bool => $value !== ''));
    }

    public static function normalizePropertyNature(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        foreach (self::propertyNatureOptions() as $option) {
            if (($option['value'] ?? null) === $value) {
                return $value;
            }

            foreach (($option['aliases'] ?? []) as $alias) {
                if ($alias === $value) {
                    return (string) $option['value'];
                }
            }
        }

        return $value;
    }

    /**
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public static function optionGroups(): array
    {
        return [
            'property_natures' => self::propertyNatureOptions(),
            'title_types' => self::titleTypeOptions(),
            'offer_types' => self::offerTypeOptions(),
            'rent_durations' => self::rentDurationOptions(),
            'statuses' => self::propertyStatusOptions(),
            'real_estate_roles' => self::realEstateRoleOptions(),
        ];
    }

    public static function firstActiveOfficeIdInProvince(int $provinceId): int
    {
        return (int) RealEstateOffice::query()
            ->where('province_id', $provinceId)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');
    }
}
