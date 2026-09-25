@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Edit Branch Calendar</h1>
    <p class="mt-1 text-sm text-slate-500">Perbarui kalender kerja branch</p>
</div>

<form method="POST" action="{{ route('scheduling.branch-calendars.update', $calendar->branch_calendar_id) }}" class="space-y-6">
    @csrf
    @method('PUT')
    @include('scheduling.branch-calendars.partials.form')
</form>
@endsection