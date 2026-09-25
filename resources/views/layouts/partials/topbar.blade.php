<header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-center justify-between px-4 py-3 sm:px-5 lg:px-6">
        <div class="min-w-0">
            @if (auth()->check())
                <div class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                    Welcome back
                </div>
                <div class="mt-0.5 truncate text-base font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                    {{ auth()->user()->full_name }}
                </div>
            @else
                <div class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                    Local dashboard preview
                </div>
                <div class="mt-0.5 truncate text-base font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                    Production Monitoring
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                @click="toggleDark()"
                :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white"
            >
                <svg x-show="!darkMode" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/>
                </svg>
                <svg x-show="darkMode" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m4.9 19.1 1.4-1.4"/><path d="m17.7 6.3 1.4-1.4"/>
                </svg>
            </button>

            @if (auth()->check())
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        Logout
                    </button>
                </form>
            @endif
        </div>
    </div>
</header>
