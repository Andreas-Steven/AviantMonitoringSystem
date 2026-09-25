<?php

namespace App\Domains\Access\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AppPermission extends Model
{
    protected $table = 'app_permissions';
    protected $primaryKey = 'permission_id';

    protected $fillable = [
        'permission_code',
        'permission_name',
        'module_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            AppRole::class,
            'app_role_permissions',
            'permission_id',
            'app_role_id'
        );
    }
}