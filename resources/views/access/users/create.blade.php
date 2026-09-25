@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Create App User"
        subtitle="Buat user internal baru beserta role, branch access, dan password awal."
        :breadcrumbs="[
            ['label' => 'Access'],
            ['label' => 'Users', 'url' => route('access.users.index')],
            ['label' => 'Create'],
        ]"
    />

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('access.users.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">

                <x-ui.section-card title="User Identity" subtitle="Informasi utama user internal.">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ui.field label="Full Name" :error="$errors->first('full_name')">
                            <input
                                type="text"
                                name="full_name"
                                value="{{ old('full_name') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                            >
                        </x-ui.field>

                        <x-ui.field label="Email" :error="$errors->first('email')">
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm"
                            >
                        </x-ui.field>

                        <x-ui.employee-combobox
                            name="employee_id"
                            label="Linked Employee"
                            :employees="$employees"
                            :selected="old('employee_id')"
                            :error="$errors->first('employee_id')"
                            placeholder="Cari employee untuk dihubungkan..."
                        />

                        <div class="space-y-3">
                            <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    @checked(old('is_active', true))
                                    class="rounded border-slate-300"
                                >
                                Active
                            </label>

                            <label class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-4 py-2.5 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="must_change_password"
                                    value="1"
                                    @checked(old('must_change_password', true))
                                    class="rounded border-slate-300"
                                >
                                Must change password
                            </label>
                        </div>
                    </div>
                </x-ui.section-card>

                <x-ui.section-card title="Initial Password" subtitle="Password awal yang diberikan ke user baru.">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ui.field label="Password" :error="$errors->first('password')">
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
                        Password awal wajib diisi saat membuat user baru. Disarankan tetap centang
                        <span class="font-medium text-slate-900">Must change password</span>
                        agar user mengganti password pada login pertama.
                    </div>
                </x-ui.section-card>

                <x-ui.section-card title="Roles" subtitle="Role menentukan permission efektif yang dimiliki user.">
                    <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        Minimal pilih satu role agar user dapat mengakses modul yang sesuai.
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
                                    @checked(in_array((int) $role->app_role_id, collect(old('role_ids', []))->map(fn ($id) => (int) $id)->all(), true))
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
                <x-ui.section-card title="Branch Access" subtitle="Branch yang dapat diakses user ini.">
                    <div class="space-y-3">
                        @foreach($branches as $branch)
                            <label class="inline-flex items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="branch_ids[]"
                                    value="{{ $branch->branch_id }}"
                                    @checked(in_array((int) $branch->branch_id, collect(old('branch_ids', []))->map(fn ($id) => (int) $id)->all(), true))
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
                        Jika tidak ada branch yang dipilih, interpretasinya mengikuti logic aplikasi yang aktif saat ini.
                        Untuk user scoped, pilih branch access secara eksplisit.
                    </div>
                </x-ui.section-card>

                <div class="flex justify-end gap-3">
                    <x-ui.button
                        type="button"
                        variant="ghost"
                        onclick="window.location='{{ route('access.users.index') }}'"
                    >
                        Cancel
                    </x-ui.button>

                    <x-ui.button type="submit">Create User</x-ui.button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection