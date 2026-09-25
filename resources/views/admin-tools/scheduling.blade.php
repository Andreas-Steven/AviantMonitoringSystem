@extends('layouts.app')

@section('content')
<div class="space-y-5">

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-900">
            Scheduling Tools
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Tools teknis untuk setup shift, policy, calendar, work pattern, roster, dan payroll period.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <a href="{{ route('scheduling.employee-scheduling-coverage.index') }}" class="rounded-2xl border border-blue-200 bg-blue-50 p-5 hover:bg-blue-100">
            <h3 class="text-base font-semibold text-blue-900">Scheduling Coverage</h3>
            <p class="mt-1 text-sm text-blue-700">Cek karyawan yang belum lengkap setup scheduling.</p>
        </a>

        <a href="{{ route('scheduling.shifts.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Shifts</h3>
            <p class="mt-1 text-sm text-gray-500">Kelola master jam kerja.</p>
        </a>

        <a href="{{ route('scheduling.employee-shift-assignments.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Shift Assignments</h3>
            <p class="mt-1 text-sm text-gray-500">Kelola assignment shift karyawan.</p>
        </a>

        <a href="{{ route('scheduling.employee-shift-rosters.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Shift Rosters</h3>
            <p class="mt-1 text-sm text-gray-500">Kelola roster harian karyawan.</p>
        </a>

        <a href="{{ route('scheduling.attendance-policies.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Attendance Policies</h3>
            <p class="mt-1 text-sm text-gray-500">Aturan keterlambatan, overtime, dan missing tap.</p>
        </a>

        <a href="{{ route('scheduling.branch-policy-assignments.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Branch Policy Assignments</h3>
            <p class="mt-1 text-sm text-gray-500">Hubungkan policy ke branch.</p>
        </a>

        <a href="{{ route('scheduling.branch-calendars.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Branch Calendars</h3>
            <p class="mt-1 text-sm text-gray-500">Kelola kalender kerja branch.</p>
        </a>

        <a href="{{ route('scheduling.holiday-events.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Holiday Calendar</h3>
            <p class="mt-1 text-sm text-gray-500">Kelola holiday dan scope branch.</p>
        </a>

        <a href="{{ route('scheduling.work-patterns.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Work Patterns</h3>
            <p class="mt-1 text-sm text-gray-500">Kelola pola kerja dan obligation.</p>
        </a>

        <a href="{{ route('scheduling.work-pattern-rules.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Work Pattern Rules</h3>
            <p class="mt-1 text-sm text-gray-500">Kelola aturan detail work pattern.</p>
        </a>

        <a href="{{ route('scheduling.employee-work-pattern-assignments.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Work Pattern Assignments</h3>
            <p class="mt-1 text-sm text-gray-500">Hubungkan work pattern ke karyawan.</p>
        </a>

        <a href="{{ route('scheduling.payroll-periods.index') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50">
            <h3 class="text-base font-semibold text-gray-900">Payroll Periods</h3>
            <p class="mt-1 text-sm text-gray-500">Kelola periode payroll dan calendar generation.</p>
        </a>
    </div>

</div>
@endsection