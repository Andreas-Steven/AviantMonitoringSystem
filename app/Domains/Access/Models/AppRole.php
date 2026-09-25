<?php

namespace App\Domains\Access\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AppRole extends Model
{
    protected $table = 'app_roles';
    protected $primaryKey = 'app_role_id';

    protected $fillable = [
        'role_code',
        'role_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            AppPermission::class,
            'app_role_permissions',
            'app_role_id',
            'permission_id'
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            AppUser::class,
            'app_user_roles',
            'app_role_id',
            'user_id'
        );
    }
}