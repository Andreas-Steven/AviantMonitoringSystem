@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Add Attendance Exception"
        subtitle="Tambahkan exception untuk koreksi atau override hasil absensi."
        :breadcrumbs="[
            ['label' => 'Attendance'],
            ['label' => 'Attendance Exceptions'],
            ['label' => 'Create'],
        ]"
    />

    @if(!empty($prefill['emp_id']) || !empty($prefill['work_date']))
        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
            Form sudah diprefill dari context sebelumnya. Kamu masih bisa mengubah employee, tanggal, atau field lainnya sebelum disimpan.
        </div>
    @endif

    @if(!empty($returnTo))
        <div class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-800">
            Exception ini sedang dibuat dari context investigasi review case. Setelah disimpan, kamu akan dikembalikan ke halaman review case.
        </div>
    @endif

    <form method="POST" action="{{ route('attendance.attendance-exceptions.store') }}" class="space-y-6">
        @csrf

        @if(!empty($returnTo))
            <input type="hidden" name="return_to" value="{{ $returnTo }}">
        @endif

        @include('attendance.attendance-exceptions._form', [
            'prefill' => $prefill ?? [],
        ])

        <div class="flex items-center justify-end gap-3">
            <x-ui.button
                type="button"
                variant="ghost"
                onclick="window.location='{{ $returnTo ?: route('attendance.attendance-exceptions.index') }}'"
            >
                Cancel
            </x-ui.button>

            <x-ui.button type="submit" variant="primary">
                Save Exception
            </x-ui.button>
        </div>
    </form>
</div>
@endsection