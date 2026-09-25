@extends('layouts.guest')

@section('content')
<div class="flex min-h-screen items-center justify-center px-6">
    <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight">Sign in</h1>
            <p class="mt-2 text-sm text-slate-500">Masuk ke aplikasi absensi internal</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none ring-0 transition focus:border-slate-400"
                    required
                >
                @error('email')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none ring-0 transition focus:border-slate-400"
                    required
                >
            </div>

            <button
                type="submit"
                class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white hover:bg-slate-800"
            >
                Login
            </button>
        </form>
    </div>
</div>
@endsection