@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- Page Header --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    Work & Scheduling Setup
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Setup struktur kerja karyawan agar absensi dan payroll dapat berjalan dengan benar.
                </p>
            </div>

            <a href="{{ route('scheduling.employee-scheduling-coverage.index') }}"
               class="inline-flex w-fit items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">
                Open Coverage
            </a>
        </div>
    </div>

    {{-- Quick Alert --}}
    @if(($coverage['percentage'] ?? 0) < 100)
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <div class="font-medium">
                Scheduling setup needs attention
            </div>
            <div class="mt-1 text-red-600">
                Ada karyawan yang belum lengkap shift atau work pattern-nya. Cek coverage sebelum lanjut ke proses absensi.
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            <div class="font-medium">
                Scheduling setup looks ready
            </div>
            <div class="mt-1 text-emerald-600">
                Setup utama sudah terlihat lengkap. Tetap cek calendar dan policy sebelum periode payroll diproses.
            </div>
        </div>
    @endif

    {{-- Setup Flow --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">
                    Recommended Setup Flow
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Check coverage → fix assignment → review policy & calendar.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">1 Coverage</span>
                <span class="text-gray-300">→</span>
                <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">2 Assignment</span>
                <span class="text-gray-300">→</span>
                <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">3 Calendar</span>
            </div>
        </div>
    </div>

    {{-- Health Check --}}
    <section class="space-y-3">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Status & Health Check
            </h2>
            <p class="text-sm text-gray-500">
                Area utama untuk mengetahui apakah setup sudah siap digunakan oleh attendance engine.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            {{-- Scheduling Coverage --}}
            <div class="rounded-2xl border-2 border-blue-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-semibold text-gray-900">
                                Scheduling Coverage
                            </h3>
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                Start here
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-gray-500">
                            Cek apakah semua karyawan sudah memiliki shift dan work pattern.
                        </p>

                        <p class="mt-2 text-xs text-gray-400">
                            {{ $coverage['covered_employee'] ?? 0 }} dari {{ $coverage['total_employee'] ?? 0 }} karyawan aktif sudah lengkap setup-nya.
                        </p>

                        @if(($coverage['percentage'] ?? 0) < 100)
                            <p class="mt-2 text-xs font-medium text-red-600">
                                Beberapa karyawan belum bisa dihitung absensinya.
                            </p>
                        @else
                            <p class="mt-2 text-xs font-medium text-emerald-600">
                                Semua karyawan siap diproses.
                            </p>
                        @endif
                    </div>

                    <div class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">
                        {{ $coverage['percentage'] ?? 0 }}%
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="text-xl font-semibold text-gray-900">
                            <a href="{{ route('master.employees.index', ['missing_shift' => 1]) }}"
                            class="text-lg font-semibold text-gray-900 hover:underline">
                                {{ $coverage['missing_shift'] ?? 0 }}
                            </a>
                        </div>
                        <div class="text-xs text-gray-500">
                            Missing shift
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="text-xl font-semibold text-gray-900">
                            <a href="{{ route('master.employees.index', ['missing_work_pattern' => 1]) }}"
                            class="text-lg font-semibold text-gray-900 hover:underline">
                                {{ $coverage['missing_work_pattern'] ?? 0 }}
                            </a>
                        </div>
                        <div class="text-xs text-gray-500">
                            Missing work pattern
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('scheduling.employee-scheduling-coverage.index') }}"
                       class="inline-flex items-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                        Open Coverage
                    </a>
                </div>
            </div>

            {{-- Branch Setup --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">
                        Branch Setup
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Pastikan setiap branch memiliki policy dan calendar yang valid.
                    </p>

                    <p class="mt-2 text-xs text-gray-400">
                        Branch tanpa policy tidak bisa menjalankan attendance calculation.
                    </p>

                    @if(!empty($branch_setup['active_period_code']))
                        <p class="mt-1 text-xs text-gray-400">
                            Calendar dicek berdasarkan payroll period: {{ $branch_setup['active_period_code'] }}.
                        </p>
                    @else
                        <p class="mt-1 text-xs text-red-500">
                            Belum ada payroll period aktif untuk pengecekan calendar.
                        </p>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="text-xl font-semibold text-gray-900">
                            {{ $branch_setup['missing_policy'] ?? 0 }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Tanpa policy
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="text-xl font-semibold text-gray-900">
                            {{ $branch_setup['missing_calendar'] ?? 0 }}
                        </div>
                        <div class="text-xs text-gray-500">
                            Tanpa calendar
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('scheduling.branch-policy-assignments.index') }}"
                       class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">
                        Manage Policy
                    </a>

                    <a href="{{ route('scheduling.branch-calendars.index') }}"
                       class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50">
                        View Calendar
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Core Setup --}}
    <section class="space-y-3">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Core Setup
            </h2>
            <p class="text-sm text-gray-500">
                Tool utama untuk membangun struktur kerja sebelum absensi diproses.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

            {{-- Shift & Roster --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">
                    Shift & Roster
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Kelola jam kerja dan jadwal harian karyawan.
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    Digunakan saat assign jadwal kerja.
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('scheduling.shifts.index') }}"
                       class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Manage Shifts
                    </a>

                    <a href="{{ route('scheduling.employee-shift-assignments.index') }}"
                       class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Manage Assignments
                    </a>

                    <a href="{{ route('scheduling.employee-shift-rosters.index') }}"
                       class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Manage Rosters
                    </a>
                </div>
            </div>

            {{-- Work Pattern --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">
                    Work Pattern
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Aturan kerja periodik dan obligation.
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    Digunakan untuk HEK dan perhitungan kehadiran.
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('scheduling.work-patterns.index') }}"
                       class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Manage Patterns
                    </a>

                    <a href="{{ route('scheduling.work-pattern-rules.index') }}"
                       class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Manage Rules
                    </a>

                    <a href="{{ route('scheduling.employee-work-pattern-assignments.index') }}"
                       class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Manage Assignments
                    </a>
                </div>
            </div>

            {{-- Attendance Policy --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">
                    Attendance Policy
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Aturan keterlambatan, overtime, dan missing tap.
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    Digunakan oleh attendance engine saat menghitung harian.
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('scheduling.attendance-policies.index') }}"
                       class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Manage Policies
                    </a>

                    <a href="{{ route('scheduling.branch-policy-assignments.index') }}"
                       class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Manage Branch Policy
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Calendar --}}
    <section class="space-y-3">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Calendar & Period
            </h2>
            <p class="text-sm text-gray-500">
                Pastikan kalender kerja dan payroll period siap sebelum absensi dihitung.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">
                    Calendar & Holiday
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Buka branch calendar workspace dan kelola holiday event.
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    Pastikan calendar sesuai payroll period aktif.
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('scheduling.branch-calendars.index') }}"
                       class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        View Calendar
                    </a>

                    <a href="{{ route('scheduling.holiday-events.index') }}"
                       class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Manage Holidays
                    </a>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-semibold text-gray-900">
                    Payroll Periods
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Kelola periode payroll. Period baru akan menyiapkan branch calendar otomatis.
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    Gunakan ini sebelum proses absensi periode berjalan.
                </p>

                <div class="mt-4">
                    <a href="{{ route('scheduling.payroll-periods.index') }}"
                       class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Manage Periods
                    </a>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection