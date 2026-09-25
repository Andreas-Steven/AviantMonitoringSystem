@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Add Branch</h1>
    <p class="mt-1 text-sm text-slate-500">Tambah data cabang baru</p>
</div>

<form method="POST" action="{{ route('master.branches.store') }}" class="space-y-6">
    @csrf
    @include('master.branches.partials.form')
</form>
@endsection