<?php

namespace App\Domains\Access\Services;

use App\Domains\Access\Models\AppLoginAudit;
use App\Domains\Access\Models\AppUser;

class LoginAuditService
{
    public function log(?AppUser $user, string $email, string $status, ?string $notes = null): void
    {
        AppLoginAudit::create([
            'user_id' => $user?->user_id,
            'email' => $email,
            'google_sub' => null,
            'login_status' => $status,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'logged_at' => now(),
            'notes' => $notes,
        ]);
    }
}