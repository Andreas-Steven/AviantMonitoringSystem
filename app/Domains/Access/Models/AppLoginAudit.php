<?php

namespace App\Domains\Access\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppLoginAudit extends Model
{
    protected $table = 'app_login_audits';
    protected $primaryKey = 'login_audit_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'google_sub',
        'login_status',
        'ip_address',
        'user_agent',
        'logged_at',
        'notes',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id', 'user_id');
    }
}