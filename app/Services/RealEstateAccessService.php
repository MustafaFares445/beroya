<?php

namespace App\Services;

use App\Models\RealEstateOffice;
use App\Models\RealEstateOfficePhone;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RealEstateAccessService
{
    public function canManageProvinces(User $user): bool
    {
        return $this->level($user) === 1;
    }

    public function canCreateOffice(User $user, int $provinceId): bool
    {
        return $this->level($user) === 1
            || ($this->level($user) === 2 && $this->provinceId($user) === $provinceId);
    }

    public function canUpdateOffice(User $user, RealEstateOffice $office, int $provinceId): bool
    {
        return match ($this->level($user)) {
            1 => true,
            2 => $this->provinceId($user) === (int) $office->province_id
                && $this->provinceId($user) === $provinceId,
            3 => (int) $user->real_estate_office_id === (int) $office->id
                && (int) $office->province_id === $provinceId,
            default => false,
        };
    }

    public function canDeleteOffice(User $user, RealEstateOffice $office): bool
    {
        return $this->level($user) === 1
            || ($this->level($user) === 2 && $this->provinceId($user) === (int) $office->province_id);
    }

    public function canCreateOfficePhone(User $user, int $officeId): bool
    {
        if ($this->level($user) === 1) {
            return true;
        }

        return $this->level($user) === 2
            && $this->provinceId($user) === $this->officeProvinceId($officeId);
    }

    public function canUpdateOfficePhone(User $user, RealEstateOfficePhone $phone, int $officeId): bool
    {
        $currentOfficeId = (int) $phone->real_estate_office_id;

        return match ($this->level($user)) {
            1 => true,
            2 => $this->provinceId($user) === $this->officeProvinceId($currentOfficeId)
                && $this->provinceId($user) === $this->officeProvinceId($officeId),
            3 => (int) $user->real_estate_office_id === $currentOfficeId
                && (int) $user->real_estate_office_id === $officeId,
            default => false,
        };
    }

    public function canDeleteOfficePhone(User $user, RealEstateOfficePhone $phone): bool
    {
        return $this->level($user) === 1
            || ($this->level($user) === 2
                && $this->provinceId($user) === $this->officeProvinceId((int) $phone->real_estate_office_id));
    }

    public function canManageProperties(User $user): bool
    {
        return in_array($this->level($user), [1, 2, 3], true)
            || ($this->level($user) === 4 && $user->real_estate_office_id !== null);
    }

    public function canReviewPropertySubmissions(User $user): bool
    {
        return in_array($this->level($user), [1, 2, 3], true);
    }

    public function canListUsers(User $user): bool
    {
        return match ($this->level($user)) {
            1 => true,
            2 => $this->provinceId($user) !== null,
            3 => $user->real_estate_office_id !== null,
            default => false,
        };
    }

    /**
     * @return Builder<User>
     */
    public function visibleUsersQuery(User $user): Builder
    {
        $query = User::query()->where(function (Builder $query): void {
            $query->whereNotNull('real_estate_province_id')
                ->orWhereNotNull('real_estate_office_id')
                ->orWhereNotNull('real_estate_role');
        });
        $provinceId = $this->provinceId($user);

        return match ($this->level($user)) {
            1 => $query,
            2 => $query
                ->whereIn('permetions_level', [3, 4])
                ->where(function (Builder $query) use ($provinceId): void {
                    $query->where('real_estate_province_id', $provinceId)
                        ->orWhereHas('realEstateOffice', function (Builder $officeQuery) use ($provinceId): void {
                            $officeQuery->where('province_id', $provinceId);
                        });
                }),
            3 => $query
                ->where('permetions_level', 4)
                ->where('real_estate_office_id', $user->real_estate_office_id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function canViewUser(User $actor, User $target): bool
    {
        if (! $actor->isRealEstateUser() || ! $target->isRealEstateUser()) {
            return false;
        }

        return match ($this->level($actor)) {
            1 => true,
            2 => in_array($this->level($target), [3, 4], true)
                && $this->provinceId($actor) === $this->provinceId($target),
            3 => $this->level($target) === 4
                && (int) $actor->real_estate_office_id === (int) $target->real_estate_office_id,
            default => false,
        };
    }

    public function canCreateUser(User $actor, int $targetLevel, ?int $provinceId, ?int $officeId): bool
    {
        if (! $actor->isRealEstateUser()) {
            return false;
        }

        return match ($this->level($actor)) {
            1 => true,
            2 => in_array($targetLevel, [3, 4], true)
                && $this->provinceId($actor) === $this->targetProvinceId($provinceId, $officeId),
            3 => $targetLevel === 4
                && $actor->real_estate_office_id !== null
                && (int) $actor->real_estate_office_id === $officeId,
            default => false,
        };
    }

    public function canUpdateUser(
        User $actor,
        User $target,
        int $targetLevel,
        ?int $officeId
    ): bool {
        return $this->canViewUser($actor, $target)
            && $this->canCreateUser($actor, $targetLevel, null, $officeId);
    }

    public function canDeleteUser(User $actor, User $target): bool
    {
        return $this->canViewUser($actor, $target);
    }

    public function provinceId(User $user): ?int
    {
        if ($user->real_estate_province_id !== null) {
            return (int) $user->real_estate_province_id;
        }

        if ($user->real_estate_office_id === null) {
            return null;
        }

        return $this->officeProvinceId((int) $user->real_estate_office_id);
    }

    private function targetProvinceId(?int $provinceId, ?int $officeId): ?int
    {
        if ($provinceId !== null) {
            return $provinceId;
        }

        return $officeId !== null ? $this->officeProvinceId($officeId) : null;
    }

    private function officeProvinceId(int $officeId): ?int
    {
        $provinceId = RealEstateOffice::query()->whereKey($officeId)->value('province_id');

        return $provinceId !== null ? (int) $provinceId : null;
    }

    private function level(User $user): int
    {
        return (int) $user->permetions_level;
    }
}
