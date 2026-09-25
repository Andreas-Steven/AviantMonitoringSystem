<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Domains\Access\Models\AppUser;
use App\Domains\Access\Services\LoginAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        protected LoginAuditService $loginAuditService
    ) {
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        foreach ([
            'app_users',
            'app_roles',
            'app_permissions',
            'app_user_roles',
            'app_role_permissions',
            'app_user_branch_access',
            'app_login_audits',
            'branches',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return back()
                    ->withErrors(['email' => 'Schema autentikasi modul lama belum tersedia pada database ini.'])
                    ->withInput($request->only('email'));
            }
        }

        $user = AppUser::with(['roles.permissions', 'branchAccesses'])
            ->where('email', $request->string('email'))
            ->first();

        if (! $user) {
            $this->loginAuditService->log(null, $request->email, 'FAILED', 'User not found.');

            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->withInput($request->only('email'));
        }

        if (! Hash::check($request->password, $user->password_hash)) {
            $this->loginAuditService->log($user, $request->email, 'FAILED', 'Invalid password.');

            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->withInput($request->only('email'));
        }

        if (! $user->is_active) {
            $this->loginAuditService->log($user, $request->email, 'BLOCKED', 'Inactive user.');

            return back()
                ->withErrors(['email' => 'User tidak aktif.'])
                ->withInput($request->only('email'));
        }

        Auth::login($user, false);
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
        ]);

        $this->loginAuditService->log($user, $request->email, 'SUCCESS', 'Login success.');

        return redirect()->route('production.dashboard');
    }

    public function destroy(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}