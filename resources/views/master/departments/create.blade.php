@extends('layouts.app')
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Add Department</h1>
    <p class="mt-1 text-sm text-slate-500">Tambah master department baru</p>
</div>
<form method="POST" action="{{ route('master.departments.store') }}" class="space-y-6">@csrf @include('master.departments.partials.form')</form>
@endsection
