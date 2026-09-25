<?php

namespace App\Domains\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domains\Access\Models\AppUser;

class AppLoginAudit extends Model
{
    protected $table = 'app_login_audits';
    protected $primaryKey = 'login_audit_id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id', 'user_id');
    }
}