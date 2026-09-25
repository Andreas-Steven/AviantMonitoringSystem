@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Employees"
        subtitle="Kelola data master karyawan dan lakukan pencarian terarah."
        :breadcrumbs="[
            ['label' => 'Master'],
            ['label' => 'Employees'],
        ]"
    >
        <x-slot:actions>
            @if(auth()->user()->hasPermission('employee.manage'))
                <x-ui.button
                    variant="primary"
                    onclick="window.location='{{ route('master.employees.create') }}'"
                >
                    Add Employee
                </x-ui.button>
            @endif
            @if(auth()->user()?->hasPermission('employee.manage') && Route::has('master.employees.import.create'))
                <x-ui.button
                    type="button"
                    onclick="window.location='{{ route('master.employees.import.create') }}'"
                >
                    Import Employees
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.page-section
        title="Employee Directory"
        subtitle="Cari karyawan berdasarkan kode, nama, atau data dasar lainnya."
    >
        <form method="GET" action="{{ route('master.employees.index') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_220px_auto]">
            <x-ui.field label="Search">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari emp code / nama / biometric code"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
            </x-ui.field>

            <x-ui.field label="Status">
                <select
                    name="active"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All</option>
                    <option value="1" @selected((string) request('active') === '1')>Active</option>
                    <option value="0" @selected((string) request('active') === '0')>Inactive</option>
                </select>
            </x-ui.field>

            <x-ui.field label="Employment Type">
                <select
                    name="employment_type_id"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                    <option value="">All</option>
                    @foreach(($employmentTypes ?? []) as $employmentType)
                        <option
                            value="{{ $employmentType->employment_type_id }}"
                            @selected((string) request('employment_type_id') === (string) $employmentType->employment_type_id)
                        >
                            {{ $employmentType->employment_type_name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="flex items-end gap-3">
                <x-ui.button type="submit">
                    Search
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="ghost"
                    onclick="window.location='{{ route('master.employees.index') }}'"
                >
                    Reset
                </x-ui.button>
            </div>
        </form>
    </x-ui.page-section>

    <x-ui.table-shell>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Employee
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Biometric
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Employment Type
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Join Date
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Status
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Action
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($employees as $employee)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-900">
                                {{ $employee->full_name }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $employee->emp_code }}
                            </div>
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $employee->biometric_code ?: '—' }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $employee->employmentType->employment_type_name ?? '—' }}
                        </td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ optional($employee->join_date)->format('Y-m-d') ?? '—' }}
                        </td>

                        <td class="px-5 py-4">
                            @if($employee->active)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                                    Inactive
                                </span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-ui.button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onclick="window.location='{{ route('master.employees.show', $employee->emp_id) }}'"
                                >
                                    View
                                </x-ui.button>

                                @if(auth()->user()->hasPermission('employee.manage'))
                                    <x-ui.button
                                        type="button"
                                        size="sm"
                                        onclick="window.location='{{ route('master.employees.edit', $employee->emp_id) }}'"
                                    >
                                        Edit
                                    </x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14">
                            <x-ui.empty-state
                                title="No employees found"
                                description="Belum ada data employee untuk filter yang sedang dipakai."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($employees->hasPages())
            <div class="border-t border-slate-200 bg-white px-5 py-4">
                {{ $employees->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.table-shell>
</div>
@endsection