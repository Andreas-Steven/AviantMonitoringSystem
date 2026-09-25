@php
    $user = auth()->user();

    $canSeeAdminTools =
        $user?->hasPermission('shift.manage')
        || $user?->hasPermission('attendance_raw.import')
        || $user?->hasPermission('summary.recalculate')
        || $user?->hasPermission('audit.view');

    $canSeeSystemAccess =
        $user?->hasPermission('user.view')
        || $user?->hasPermission('role.view');

    $navGroups = array_values(array_filter([
        [
            'key' => 'general',
            'label' => 'General',
            'items' => [
                [
                    'label' => 'Production Dashboard',
                    'route' => 'production.dashboard',
                    'patterns' => ['production.dashboard'],
                    'icon' => 'bar-chart-3',
                ],
                [
                    'label' => 'Dashboard',
                    'route' => 'dashboard',
                    'patterns' => ['dashboard'],
                    'icon' => 'layout-dashboard',
                ],
            ],
        ],

        [
            'key' => 'operations',
            'label' => 'Operations',
            'items' => array_values(array_filter([
                Route::has('scheduling.workspace') ? [
                    'label' => 'Scheduling Setup',
                    'route' => 'scheduling.workspace',
                    'patterns' => ['scheduling.workspace'],
                    'icon' => 'target',
                ] : null,

                Route::has('attendance.workspace') ? [
                    'label' => 'Attendance Operations',
                    'route' => 'attendance.workspace',
                    'patterns' => ['attendance.workspace'],
                    'icon' => 'list-checks',
                ] : null,

                Route::has('summary.workspace') ? [
                    'label' => 'Payroll & Summary',
                    'route' => 'summary.workspace',
                    'patterns' => ['summary.workspace'],
                    'icon' => 'wallet',
                ] : null,
            ])),
        ],

        [
            'key' => 'people',
            'label' => 'People & Organization',
            'items' => array_values(array_filter([
                Route::has('master.employees.index') ? [
                    'label' => 'Employees',
                    'route' => 'master.employees.index',
                    'patterns' => ['master.employees.*'],
                    'icon' => 'users',
                ] : null,

                Route::has('master.branches.index') ? [
                    'label' => 'Branches',
                    'route' => 'master.branches.index',
                    'patterns' => ['master.branches.*'],
                    'icon' => 'building-2',
                ] : null,

                Route::has('master.employee-assignments.index') ? [
                    'label' => 'Employee Placement',
                    'route' => 'master.employee-assignments.index',
                    'patterns' => ['master.employee-assignments.*'],
                    'icon' => 'user-check',
                ] : null,

                Route::has('master.departments.index') ? [
                    'label' => 'Departments',
                    'route' => 'master.departments.index',
                    'patterns' => ['master.departments.*'],
                    'icon' => 'network',
                ] : null,

                Route::has('master.position-roles.index') ? [
                    'label' => 'Position Roles',
                    'route' => 'master.position-roles.index',
                    'patterns' => ['master.position-roles.*'],
                    'icon' => 'badge-check',
                ] : null,

                Route::has('master.grades.index') ? [
                    'label' => 'Grades',
                    'route' => 'master.grades.index',
                    'patterns' => ['master.grades.*'],
                    'icon' => 'layers',
                ] : null,

                Route::has('master.employment-types.index') ? [
                    'label' => 'Employment Types',
                    'route' => 'master.employment-types.index',
                    'patterns' => ['master.employment-types.*'],
                    'icon' => 'briefcase',
                ] : null,
            ])),
        ],

        [
            'key' => 'review',
            'label' => 'Review & Approval',
            'items' => array_values(array_filter([
                Route::has('review.attendance-cases.index') ? [
                    'label' => 'Review Cases',
                    'route' => 'review.attendance-cases.index',
                    'patterns' => ['review.attendance-cases.*'],
                    'icon' => 'alert-triangle',
                ] : null,

                Route::has('requests.leave-requests.index') ? [
                    'label' => 'Leave Requests',
                    'route' => 'requests.leave-requests.index',
                    'patterns' => ['requests.leave-requests.*'],
                    'icon' => 'file-text',
                ] : null,

                Route::has('requests.overtime-requests.index') ? [
                    'label' => 'Overtime Requests',
                    'route' => 'requests.overtime-requests.index',
                    'patterns' => ['requests.overtime-requests.*'],
                    'icon' => 'timer',
                ] : null,

                Route::has('requests.leave-balances.index') ? [
                    'label' => 'Leave Balances',
                    'route' => 'requests.leave-balances.index',
                    'patterns' => ['requests.leave-balances.*'],
                    'icon' => 'wallet-cards',
                ] : null,
            ])),
        ],

        $canSeeAdminTools ? [
            'key' => 'admin_tools',
            'label' => 'Admin Tools',
            'items' => array_values(array_filter([
                Route::has('admin-tools.scheduling') ? [
                    'label' => 'Scheduling Tools',
                    'route' => 'admin-tools.scheduling',
                    'patterns' => [
                        'admin-tools.scheduling',
                        'scheduling.*',
                    ],
                    'icon' => 'settings',
                ] : null,

                Route::has('admin-tools.attendance') ? [
                    'label' => 'Attendance Tools',
                    'route' => 'admin-tools.attendance',
                    'patterns' => [
                        'admin-tools.attendance',
                        'attendance.*',
                    ],
                    'icon' => 'activity',
                ] : null,

                Route::has('admin-tools.payroll') ? [
                    'label' => 'Payroll Tools',
                    'route' => 'admin-tools.payroll',
                    'patterns' => [
                        'admin-tools.payroll',
                        'summary.*',
                        'payroll.*',
                    ],
                    'icon' => 'coins',
                ] : null,

                Route::has('audit.system-change-logs.index') ? [
                    'label' => 'Audit Logs',
                    'route' => 'audit.system-change-logs.index',
                    'patterns' => [
                        'audit.*',
                    ],
                    'icon' => 'history',
                ] : null,
            ])),
        ] : null,

        $canSeeSystemAccess ? [
            'key' => 'access',
            'label' => 'System Access',
            'items' => array_values(array_filter([
                Route::has('access.users.index') && $user?->hasPermission('user.view') ? [
                    'label' => 'Users',
                    'route' => 'access.users.index',
                    'patterns' => ['access.users.*'],
                    'icon' => 'user',
                ] : null,

                Route::has('access.roles.index') && $user?->hasPermission('role.view') ? [
                    'label' => 'Roles',
                    'route' => 'access.roles.index',
                    'patterns' => ['access.roles.*'],
                    'icon' => 'shield',
                ] : null,

                Route::has('access.permissions.index') && $user?->hasPermission('role.view') ? [
                    'label' => 'Permissions',
                    'route' => 'access.permissions.index',
                    'patterns' => ['access.permissions.*'],
                    'icon' => 'key',
                ] : null,
            ])),
        ] : null,
    ]));
@endphp

@php
    $iconMap = [
        'grid' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
        'layout-dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>',
        'building' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 10h.01"/><path d="M15 10h.01"/><path d="M9 14h.01"/><path d="M15 14h.01"/><path d="M11 21v-4h2v4"/></svg>',
        'building-2' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"/><path d="M6 12H4a2 2 0 0 0-2 2v8"/><path d="M18 9h2a2 2 0 0 1 2 2v11"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'user' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="12" cy="7" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
        'user-check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/></svg>',
        'id-card' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8" cy="12" r="2.5"/><path d="M14 10h4"/><path d="M14 14h4"/></svg>',
        'network' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><rect x="9" y="2" width="6" height="6" rx="1.5"/><rect x="3" y="16" width="6" height="6" rx="1.5"/><rect x="15" y="16" width="6" height="6" rx="1.5"/><path d="M12 8v4"/><path d="M6 16v-2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v2"/></svg>',
        'badge-check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M12 3 14.1 6.1 18 5.5 17.4 9.4 20.5 11.5 17.4 13.6 18 17.5 14.1 16.9 12 20 9.9 16.9 6 17.5 6.6 13.6 3.5 11.5 6.6 9.4 6 5.5 9.9 6.1 12 3Z"/><path d="m9 12 2 2 4-4"/></svg>',
        'briefcase' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 12h18"/></svg>',
        'clock' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>',
        'timer' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M10 2h4"/><path d="M12 14l3-3"/><circle cx="12" cy="14" r="8"/><path d="M19 5l-2 2"/></svg>',
        'calendar-check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M12 3l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V6l7-3z"/></svg>',
        'shield-check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M12 3l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V6l7-3z"/><path d="m9 12 2 2 4-4"/></svg>',
        'git-branch' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="18" r="2"/><circle cx="18" cy="6" r="2"/><path d="M8 6h8"/><path d="M18 8v8"/><path d="M6 8v8a4 4 0 0 0 4 4h6"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>',
        'repeat' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="m17 2 4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>',
        'list-checks' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/></svg>',
        'layers' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/></svg>',
        'table' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/><path d="M9 20V10"/><path d="M15 20V10"/></svg>',
        'wallet' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M20 7H4a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z"/><path d="M16 7V5a2 2 0 0 0-2-2H4"/><circle cx="17.5" cy="13.5" r=".5"/></svg>',
        'wallet-cards' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/><path d="M15 15h2"/></svg>',
        'database' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.66 3.58 3 8 3s8-1.34 8-3V5"/><path d="M4 11v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"/></svg>',
        'upload' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M20 16.5v2a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5v-2"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 8.92 4.6 1.65 1.65 0 0 0 9.93 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.2.49.76.81 1.3.82H21a2 2 0 1 1 0 4h-.09c-.54.01-1.1.33-1.51.82z"/></svg>',
        'refresh' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>',
        'clipboard-list' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M9 5h6"/><path d="M9 9h6"/><path d="M9 13h6"/><path d="M9 17h6"/><path d="M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>',
        'bar-chart-3' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>',
        'coins' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.66 3.13 3 7 3s7-1.34 7-3V6"/><path d="M5 12v6c0 1.66 3.13 3 7 3s7-1.34 7-3v-6"/></svg>',
        'key' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="7.5" cy="15.5" r="4.5"/><path d="M21 2l-9.6 9.6"/><path d="M15 5l4 4"/><path d="M18 2l4 4"/></svg>',
        'target' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/></svg>',
        'alert-triangle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M10.3 4.3 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
        'file-text' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h8"/><path d="M8 9h2"/></svg>',
        'receipt' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M5 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1V2z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>',
        'history' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/><path d="M12 7v5l3 2"/></svg>',
        'activity' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M22 12h-4l-3 8-6-16-3 8H2"/></svg>',
    ];
@endphp

<aside
    x-data="{
        expandedGroups: JSON.parse(localStorage.getItem('expandedSidebarGroups') || '{}'),
        isGroupExpanded(key) {
            if (this.expandedGroups[key] === undefined) return true;
            return this.expandedGroups[key];
        },
        toggleGroup(key) {
            this.expandedGroups[key] = !this.isGroupExpanded(key);
            localStorage.setItem('expandedSidebarGroups', JSON.stringify(this.expandedGroups));
        }
    }"
    class="app-sidebar border-r border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900"
    style="width:280px; min-width:280px; display:flex; flex-direction:column; min-height:100vh;"
>
    <div class="border-b border-slate-200 px-4 py-4 dark:border-slate-800">
        <div class="sidebar-brand-wrap flex items-center justify-between gap-3">
            <div class="sidebar-brand-text min-w-0">
                <div class="truncate text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                    {{ config('app.name') }}
                </div>
                <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    Production &amp; Workforce
                </div>
            </div>

            <button
                type="button"
                @click="toggleSidebar()"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white"
                :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            >
                <span x-text="sidebarCollapsed ? '›' : '‹'" class="text-lg leading-none"></span>
            </button>
        </div>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto px-3 py-4">
        <nav class="space-y-4">
            @foreach ($navGroups as $group)
                @continue(empty($group['items']))

                <div class="rounded-2xl">
                    @php
                        $groupHasActive = false;

                        foreach ($group['items'] as $groupItem) {
                            foreach ($groupItem['patterns'] as $pattern) {
                                if (request()->routeIs($pattern)) {
                                    $groupHasActive = true;
                                    break 2;
                                }
                            }
                        }
                    @endphp

                    <button
                        type="button"
                        @click="if (!sidebarCollapsed) toggleGroup('{{ $group['key'] }}')"
                        class="sidebar-group-button flex w-full items-center justify-between rounded-2xl px-2 py-2 text-left hover:bg-slate-50 dark:hover:bg-slate-800"
                        :title="sidebarCollapsed ? '{{ $group['label'] }}' : ''"
                    >
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full {{ $groupHasActive ? 'bg-slate-900 dark:bg-blue-400' : 'bg-slate-300 dark:bg-slate-600' }}"></span>

                            <span class="sidebar-group-label text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
                                {{ $group['label'] }}
                            </span>
                        </div>

                        <span
                            class="sidebar-group-toggle-symbol text-xs font-semibold text-slate-400 dark:text-slate-500"
                            x-text="isGroupExpanded('{{ $group['key'] }}') ? '−' : '+'"
                        ></span>
                    </button>

                    <div
                        class="sidebar-group-items mt-1 space-y-1.5"
                        x-show="sidebarCollapsed || isGroupExpanded('{{ $group['key'] }}')"
                    >
                        @foreach ($group['items'] as $item)
                            @php
                                $isActive = false;

                                foreach ($item['patterns'] as $pattern) {
                                    if (request()->routeIs($pattern)) {
                                        $isActive = true;
                                        break;
                                    }
                                }
                            @endphp

                            <a
                                href="{{ route($item['route']) }}"
                                class="sidebar-item-link {{ $isActive
                                    ? 'bg-slate-900 text-white shadow-sm dark:bg-blue-600'
                                    : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white'
                                }} flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium"
                                :title="sidebarCollapsed ? '{{ $item['label'] }}' : ''"
                            >
                                <span class="shrink-0">{!! $iconMap[$item['icon']] ?? $iconMap['grid'] !!}</span>

                                <span class="sidebar-item-label truncate">
                                    {{ $item['label'] }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
    </div>
</aside>