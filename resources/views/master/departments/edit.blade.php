@extends('layouts.app')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Edit Department</h1>
    <p class="mt-1 text-sm text-slate-500">Perbarui master department</p>
</div>
<form method="POST" action="{{ route('master.departments.update', $department->dept_id) }}" class="space-y-6">@csrf @method('PUT') @include('master.departments.partials.form')</form>
@endsection
