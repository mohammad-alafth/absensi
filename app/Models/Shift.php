<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [

        'name',

        'start_time',

        'end_time',

        'color',

        'work_hours',

        'grace_minutes',

        'category',

        'is_overnight',

        'allowed_roles'
    ];

    protected $casts = [
        'allowed_roles' => 'array',
    ];
    public function employeeShifts()
    {
        return $this->hasMany(EmployeeShift::class);
    }

    /**
     * Role variants that may represent the same employee role.
     *
     * The database is not uniform: a PIPP shift can be scoped as `pipp`,
     * `pj_pipp`, or `pj_*`. Treat the PJ prefix as an alias so both the
     * employee role and its PJ variant resolve to the same shift policy.
     *
     * @return array<int, string>
     */
    public static function roleVariants(?string $role): array
    {
        $role = strtolower(trim((string) $role));

        if ($role === '') {
            return [];
        }

        $variants = [$role];

        if (str_starts_with($role, 'pj_')) {
            $variants[] = substr($role, 3);
        } else {
            $variants[] = 'pj_' . $role;
        }

        return array_values(array_unique($variants));
    }

    /**
     * Limit the query to shifts whose allowed_roles match any of the roles,
     * including the `pj_` alias and wildcard entries such as `pj_*` or `*`.
     *
     * @param  array<int, string|null>  $roles
     */
    public function scopeAllowedForRoles(Builder $query, array $roles): Builder
    {
        $variants = [];

        foreach ($roles as $role) {
            $variants = array_merge($variants, self::roleVariants($role));
        }

        $variants = array_values(array_unique($variants));

        return $query->where(function (Builder $query) use ($variants) {
            foreach ($variants as $variant) {
                $query->orWhereJsonContains('allowed_roles', $variant);

                if (str_starts_with($variant, 'pj_')) {
                    $query->orWhereJsonContains('allowed_roles', 'pj_*');
                }
            }

            $query->orWhereJsonContains('allowed_roles', '*');
        });
    }

    /**
     * Determine whether this shift is allowed for the given role.
     *
     * `null` and empty `allowed_roles` keep the existing unrestricted
     * behavior. Malformed policy data fails closed. Matching supports exact
     * roles, the `pj_` alias, and wildcards such as `pj_*` or `*`.
     */
    public function allowsRole(?string $role): bool
    {
        $rawAllowedRoles = $this->getRawOriginal('allowed_roles');

        if ($rawAllowedRoles === null) {
            return true;
        }

        $allowedRoles = is_array($rawAllowedRoles)
            ? $rawAllowedRoles
            : json_decode((string) $rawAllowedRoles, true);

        if (!is_array($allowedRoles) || !array_is_list($allowedRoles)) {
            return false;
        }

        if ($allowedRoles === []) {
            return true;
        }

        foreach ($allowedRoles as $allowedRole) {
            if (!is_string($allowedRole) || trim($allowedRole) === '') {
                return false;
            }
        }

        $variants = self::roleVariants($role);

        if ($variants === []) {
            return false;
        }

        foreach ($allowedRoles as $allowedRole) {
            $allowedRole = strtolower(trim($allowedRole));

            if ($allowedRole === '*') {
                return true;
            }

            if (str_ends_with($allowedRole, '*')) {
                $prefix = substr($allowedRole, 0, -1);

                foreach ($variants as $variant) {
                    if (str_starts_with($variant, $prefix)) {
                        return true;
                    }
                }
            }

            foreach ($variants as $variant) {
                if ($allowedRole === $variant) {
                    return true;
                }
            }
        }

        return false;
    }
}
