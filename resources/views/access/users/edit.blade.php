@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Edit App User"
        :subtitle="sprintf('%s · %s', $user->full_name, $user->email)"
        :breadcrumbs="[
            ['label' => 'Access'],
            ['label' => 'Users', 'url' => route('access.users.index')],
            ['label' => 'Edit'],
        ]"
    />

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('access.users.update', $user->user_id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">

                <x-ui.section-card title="User Identity" subtitle="Informasi utama user internal.">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ui.field label="Full Name" :error="$errors->first('full_name')">
                            <input
                                type="text"
                                name="full_name"
                                value="{{ old('full_name', $user->full_name) }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                            >
                        </x-ui.field>

                        <x-ui.field label="Email" :error="$errors->first('email')">
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                            >
                        </x-ui.field>

                        <div class="space-y-3">
                            <x-ui.employee-combobox
                                name="employee_id"
                                label="Linked Employee"
                                :employees="$employees"
                                :selected="old('employee_id', $user->employee_id)"
                                :error="$errors->first('employee_id')"
                                placeholder="Cari employee untuk dihubungkan..."
                            />

                            @if($user->employee)
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">
                                    <div class="font-medium text-slate-900">
                                        Current Link: {{ $user->employee->emp_code ?? '-' }} · {{ $user->employee->full_name ?? '-' }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        Biometric: {{ $user->employee->biometric_code ?? '-' }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    @checked(old('is_active', $user->is_active))
                                    class="rounded border-slate-300"
                                >
                                Active
                            </label>

                            <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="must_change_password"
                                    value="1"
                                    @checked(old('must_change_password', $user->must_change_password))
                                    class="rounded border-slate-300"
                                >
                                Must change password
                            </label>
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card title="Reset Password (Admin Flow)" subtitle="Opsional. Isi hanya jika admin ingin mengganti password user dari sini.">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ui.field label="New Password" :error="$errors->first('password')">
                            <input
                                type="password"
                                name="password"
                                value=""
                                autocomplete="new-password"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                            >
                        </x-ui.field>

                        <x-ui.field label="Confirm Password" :error="$errors->first('password_confirmation')">
                            <input
                                type="password"
                                name="password_confirmation"
                                value=""
                                autocomplete="new-password"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                            >
                        </x-ui.field>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        Biarkan kosong jika tidak ingin mengganti password. Jika password diubah, sebaiknya centang <span class="font-medium text-slate-900">Must change password</span> bila Anda ingin user membuat password baru saat login berikutnya.
                    </div>
                </x-ui.section-card>

                <x-ui.section-card title="Roles" subtitle="Role menentukan permission efektif yang dimiliki user.">
                    <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        Pastikan minimal satu role tetap terpilih untuk user operasional. Menghapus semua role bisa membuat user kehilangan akses menu dan fitur.
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach($roles as $role)
                            @php
                                $tone = match($role->role_code) {
                                    'SUPER_ADMIN' => 'danger',
                                    'HR_ADMIN', 'PAYROLL_OFFICER' => 'warning',
                                    'BRANCH_ADMIN', 'SUPERVISOR' => 'info',
                                    default => 'neutral',
                                };
                            @endphp

                            <label class="inline-flex items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="role_ids[]"
                                    value="{{ $role->app_role_id }}"
                                    @checked(in_array($role->app_role_id, old('role_ids', $user->roles->pluck('app_role_id')->all()), true))
                                    class="mt-0.5 rounded border-slate-300"
                                >
                                <span>
                                    <span class="flex flex-wrap items-center gap-2">
                                        <x-ui.status-badge :label="$role->role_name" :tone="$tone" />
                                    </span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $role->role_code }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </x-ui.section-card>
            </div>

            <div class="space-y-6">
                <x-ui.section-card title="Branch Access" subtitle="Branch yang bisa diakses user ini.">
                    <div class="space-y-3">
                        @foreach($branches as $branch)
                            <label class="inline-flex items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="branch_ids[]"
                                    value="{{ $branch->branch_id }}"
                                    @checked(in_array($branch->branch_id, old('branch_ids', $user->branchAccesses->pluck('branch_id')->all()), true))
                                    class="mt-0.5 rounded border-slate-300"
                                >
                                <span>
                                    <span class="block font-medium text-slate-900">{{ $branch->branch_name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $branch->branch_code }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        Jika tidak ada branch yang dipilih, interpretasinya mengikuti logic aplikasi yang aktif saat ini. Untuk user scoped, pastikan branch access dipilih secara eksplisit.
                    </div>
                </x-ui.section-card>

                <div class="flex justify-end gap-3">
                    @if(Route::has('access.users.show'))
                        <x-ui.button
                            type="button"
                            variant="ghost"
                            onclick="window.location='{{ route('access.users.show', $user->user_id) }}'"
                        >
                            Cancel
                        </x-ui.button>
                    @else
                        <x-ui.button
                            type="button"
                            variant="ghost"
                            onclick="window.location='{{ route('access.users.index') }}'"
                        >
                            Cancel
                        </x-ui.button>
                    @endif

                    <x-ui.button type="submit">Save User</x-ui.button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection