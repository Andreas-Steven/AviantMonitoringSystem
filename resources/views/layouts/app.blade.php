<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    <script>
        (function () {
            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
            window.__sidebarCollapsed = collapsed;
        })();
    </script>

    <style>
        [x-cloak] { display: none !important; }

        html.sidebar-collapsed .app-sidebar {
            width: 88px !important;
            min-width: 88px !important;
        }

        html.sidebar-collapsed .sidebar-brand-text,
        html.sidebar-collapsed .sidebar-group-label,
        html.sidebar-collapsed .sidebar-item-label,
        html.sidebar-collapsed .sidebar-group-toggle-symbol {
            display: none !important;
        }

        html.sidebar-collapsed .sidebar-group-button,
        html.sidebar-collapsed .sidebar-item-link {
            justify-content: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        html.sidebar-collapsed .sidebar-brand-wrap {
            justify-content: center !important;
        }

        html.sidebar-collapsed .sidebar-group-items {
            display: block !important;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div
        x-data="{
            sidebarCollapsed: window.__sidebarCollapsed,
            toggleSidebar() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed ? 'true' : 'false');
                document.documentElement.classList.toggle('sidebar-collapsed', this.sidebarCollapsed);
            }
        }"
        class="min-h-screen bg-slate-50"
        style="display:flex; min-height:100vh;"
    >
        @include('layouts.partials.sidebar')

        <div style="flex:1; min-width:0; display:flex; flex-direction:column; min-height:100vh;">
            @include('layouts.partials.topbar')

            <main style="flex:1;">
                <div class="mx-auto w-full max-w-[1720px] px-4 py-5 sm:px-5 lg:px-6 lg:py-6">
                    @include('layouts.partials.flash-message')
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>