<?php

namespace App\Domains\Access\Models;

use App\Domains\Master\Models\Branch;
use App\Domains\Master\Models\Employee;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AppUser extends Authenticatable
{
    protected $table = 'app_users';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'employee_id',
        'email',
        'password_hash',
        'google_sub',
        'full_name',
        'avatar_url',
        'is_active',
        'must_change_password',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'must_change_password' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'emp_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            AppRole::class,
            'app_user_roles',
            'user_id',
            'app_role_id'
        );
    }

    public function branchAccesses(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,
            'app_user_branch_access',
            'user_id',
            'branch_id'
        );
    }

    public function loginAudits(): HasMany
    {
        return $this->hasMany(
            AppLoginAudit::class,
            'user_id',
            'user_id'
        );
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function hasRole(string $roleCode): bool
    {
        return $this->roles()
            ->where('app_roles.role_code', $roleCode)
            ->where('app_roles.is_active', true)
            ->exists();
    }

    public function hasAnyRole(array $roleCodes): bool
    {
        return $this->roles()
            ->whereIn('app_roles.role_code', $roleCodes)
            ->where('app_roles.is_active', true)
            ->exists();
    }

    public function hasPermission(string $permissionCode): bool
    {
        return $this->roles()
            ->where('app_roles.is_active', true)
            ->whereHas('permissions', function ($query) use ($permissionCode): void {
                $query->where('app_permissions.permission_code', $permissionCode)
                    ->where('app_permissions.is_active', true);
            })
            ->exists();
    }

    public function hasAnyPermission(array $permissionCodes): bool
    {
        return $this->roles()
            ->where('app_roles.is_active', true)
            ->whereHas('permissions', function ($query) use ($permissionCodes): void {
                $query->whereIn('app_permissions.permission_code', $permissionCodes)
                    ->where('app_permissions.is_active', true);
            })
            ->exists();
    }

    public function hasBranchAccess(int $branchId): bool
    {
        if ($this->hasRole('SUPER_ADMIN')) {
            return true;
        }

        return $this->branchAccesses()
            ->where('branches.branch_id', $branchId)
            ->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('SUPER_ADMIN');
    }
}