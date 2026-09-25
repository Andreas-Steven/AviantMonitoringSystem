<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

        @media (max-width: 1023px) {
            .app-shell {
                flex-direction: column !important;
            }

            .app-sidebar,
            html.sidebar-collapsed .app-sidebar {
                width: 100% !important;
                min-width: 0 !important;
                min-height: 0 !important;
                max-height: 46vh;
                border-right: 0;
                border-bottom: 1px solid #e2e8f0;
            }

            .app-sidebar > div:nth-child(2) {
                max-height: calc(46vh - 76px);
            }

            html.sidebar-collapsed .sidebar-brand-text {
                display: block !important;
            }

            html.sidebar-collapsed .sidebar-group-label,
            html.sidebar-collapsed .sidebar-item-label,
            html.sidebar-collapsed .sidebar-group-toggle-symbol {
                display: inline !important;
            }

            html.sidebar-collapsed .sidebar-group-button,
            html.sidebar-collapsed .sidebar-item-link {
                justify-content: flex-start !important;
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            html.sidebar-collapsed .sidebar-brand-wrap {
                justify-content: space-between !important;
            }
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
        class="app-shell min-h-screen flex flex-col lg:flex-row bg-slate-50"
        style="display:flex; min-height:100vh;"
    >
        @include('layouts.partials.sidebar')

        <div class="app-main" style="flex:1; min-width:0; display:flex; flex-direction:column; min-height:100vh;">
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
