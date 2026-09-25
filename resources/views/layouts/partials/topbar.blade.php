<header class="border-b border-slate-200 bg-white">
    <div class="flex items-center justify-between px-4 py-3 sm:px-5 lg:px-6">
        <div class="min-w-0">
            @if (auth()->check())
                <div class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Welcome back
                </div>
                <div class="mt-0.5 truncate text-base font-semibold tracking-tight text-slate-900">
                    {{ auth()->user()->full_name }}
                </div>
            @else
                <div class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Local dashboard preview
                </div>
                <div class="mt-0.5 truncate text-base font-semibold tracking-tight text-slate-900">
                    Production Monitoring
                </div>
            @endif
        </div>

        @if (auth()->check())
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        Logout
                    </button>
                </form>
            </div>
        @endif
    </div>
</header>