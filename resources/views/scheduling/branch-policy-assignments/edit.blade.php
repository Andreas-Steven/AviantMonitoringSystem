@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Edit Branch Policy Assignment</h1>
    <p class="mt-1 text-sm text-slate-500">Perbarui assignment policy ke branch</p>
</div>

<form method="POST" action="{{ route('scheduling.branch-policy-assignments.update', $assignment->branch_policy_assignment_id) }}" class="space-y-6">
    @csrf
    @method('PUT')
    @include('scheduling.branch-policy-assignments.partials.form')
</form>
@endsection