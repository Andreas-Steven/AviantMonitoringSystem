@extends('layouts.app')

@section('content')
<div x-data="productionDashboard" class="space-y-6">
    <x-ui.page-header
        title="Production Monitoring"
        subtitle="Ringkasan output, pencapaian produksi, dan performa mesin."
        :breadcrumbs="[
            ['label' => 'General'],
            ['label' => 'Production Dashboard'],
        ]"
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    @click="refresh()"
                    :disabled="dashboardLoading"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 disabled:cursor-wait disabled:opacity-60"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M20 7v5h-5"/><path d="M4.8 9a8 8 0 0 1 13.5-2L20 12M4 17v-5h5"/><path d="M19.2 15a8 8 0 0 1-13.5 2L4 12"/>
                    </svg>
                    <span x-text="dashboardLoading ? 'Memuat…' : 'Refresh data'"></span>
                </button>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div x-show="dashboardError" x-cloak role="alert" class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="font-semibold">Data dashboard belum tersedia</div>
            <p class="mt-1 text-amber-800" x-text="dashboardError"></p>
        </div>
        <button type="button" @click="refresh()" class="shrink-0 rounded-xl border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-900 transition hover:bg-amber-100">
            Coba lagi
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-ui.stat-card label="Total Machine" value="—" :value-expression="'formatNumber(summary.total_machine)'" hint="Seluruh mesin terdaftar" />
        <x-ui.stat-card label="Running Work Order" value="—" :value-expression="'formatNumber(summary.running_order)'" hint="Work order berstatus RUNNING" />
        <x-ui.stat-card label="Finished Work Order" value="—" :value-expression="'formatNumber(summary.finished_order)'" hint="Work order berstatus FINISHED" />
        <x-ui.stat-card label="Achievement" value="—" :value-expression="'formatPercent(summary.achievement)'" hint="Good quantity dibanding target" />
        <x-ui.stat-card label="Good Qty" value="—" :value-expression="'formatNumber(summary.today_good)'" hint="Pada tanggal data terbaru" />
        <x-ui.stat-card label="Reject Qty" value="—" :value-expression="'formatNumber(summary.today_reject)'" hint="Pada tanggal data terbaru" />
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(300px,0.85fr)]">
        <x-ui.section-card title="Production Trend" subtitle="Pergerakan produksi pada tujuh tanggal terakhir yang tersedia.">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                    <button
                        type="button"
                        @click="trendMetric = 'good_qty'; activeTrendPoint = null"
                        :class="trendMetric === 'good_qty' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                    >Good Qty</button>
                    <button
                        type="button"
                        @click="trendMetric = 'reject_qty'; activeTrendPoint = null"
                        :class="trendMetric === 'reject_qty' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                    >Reject Qty</button>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                    <span x-text="trendMetric === 'good_qty' ? 'Good quantity' : 'Reject quantity'"></span>
                </div>
            </div>

            <div class="relative">
                <svg viewBox="0 0 720 245" class="h-56 w-full overflow-visible sm:h-64" role="img" aria-label="Grafik tren produksi tujuh hari">
                    <defs>
                        <linearGradient id="production-trend-fill" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#2563eb" stop-opacity="0.2" />
                            <stop offset="100%" stop-color="#2563eb" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <template x-for="line in chartGridLines()" :key="line.y">
                        <g>
                            <line x1="54" x2="692" :y1="line.y" :y2="line.y" stroke="#e2e8f0" stroke-dasharray="4 5" />
                            <text x="44" :y="line.y + 4" text-anchor="end" fill="#94a3b8" font-size="10" x-text="formatCompact(line.value)"></text>
                        </g>
                    </template>
                    <path :d="trendAreaPath()" fill="url(#production-trend-fill)"></path>
                    <path :d="trendLinePath()" fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                    <template x-for="point in chartPoints()" :key="point.date">
                        <g @mouseenter="activeTrendPoint = point" @mouseleave="activeTrendPoint = null" @focus="activeTrendPoint = point" tabindex="0">
                            <line :x1="point.x" :x2="point.x" y1="24" y2="190" stroke="#cbd5e1" stroke-dasharray="3 5" opacity="0" class="transition-opacity group-hover:opacity-100" />
                            <circle :cx="point.x" :cy="point.y" r="9" fill="#2563eb" opacity="0.12"></circle>
                            <circle :cx="point.x" :cy="point.y" r="4" fill="white" stroke="#2563eb" stroke-width="2.5">
                                <title x-text="`${formatDate(point.date)} · ${formatNumber(point.value)}`"></title>
                            </circle>
                        </g>
                    </template>
                    <template x-for="point in chartPoints()" :key="`${point.date}-label`">
                        <text :x="point.x" y="224" text-anchor="middle" fill="#64748b" font-size="10" x-text="formatDateShort(point.date)"></text>
                    </template>
                </svg>
                <div x-show="activeTrendPoint" x-cloak class="pointer-events-none absolute right-2 top-2 rounded-xl border border-slate-200 bg-white/95 px-3 py-2 text-xs shadow-lg backdrop-blur">
                    <div class="font-semibold text-slate-900" x-text="formatDate(activeTrendPoint?.date)"></div>
                    <div class="mt-1 text-slate-600" x-text="`${trendMetric === 'good_qty' ? 'Good Qty' : 'Reject Qty'}: ${formatNumber(activeTrendPoint?.value)}`"></div>
                </div>
                <div x-show="!dashboardLoading && !dashboardError && trend.length === 0" x-cloak class="absolute inset-0 grid place-items-center bg-white/80">
                    <p class="text-sm text-slate-500">Belum ada hasil produksi.</p>
                </div>
            </div>
        </x-ui.section-card>

        <x-ui.section-card title="Work Order Status" subtitle="Pilih status untuk memfilter daftar production order.">
            <div class="flex flex-col items-center gap-6 sm:flex-row xl:flex-col 2xl:flex-row">
                <div class="relative h-48 w-48 shrink-0 rounded-full" :style="statusDonutStyle()" role="img" aria-label="Diagram status work order">
                    <div class="absolute inset-5 grid place-items-center rounded-full bg-white text-center shadow-inner">
                        <div>
                            <div class="text-3xl font-semibold tracking-tight text-slate-900" x-text="formatNumber(orderCount)"></div>
                            <div class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">Total WO</div>
                        </div>
                    </div>
                </div>
                <div class="grid w-full gap-2">
                    <template x-for="item in statusBreakdown" :key="item.status">
                        <button
                            type="button"
                            @click="applyStatusFilter(item.status)"
                            :class="orderFilters.status === item.status ? 'ring-2 ring-slate-300 bg-slate-50' : 'hover:bg-slate-50'"
                            class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition"
                        >
                            <span class="flex min-w-0 items-center gap-2.5">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="`background-color:${statusColor(item.status)}`"></span>
                                <span class="truncate text-sm font-medium text-slate-700" x-text="item.status"></span>
                            </span>
                            <span class="text-sm font-semibold tabular-nums text-slate-900" x-text="formatNumber(item.total)"></span>
                        </button>
                    </template>
                    <div x-show="!dashboardLoading && statusBreakdown.length === 0" class="text-sm text-slate-500">Status belum tersedia.</div>
                </div>
            </div>
        </x-ui.section-card>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
        <x-ui.section-card title="Top 10 Machines" subtitle="Peringkat mesin berdasarkan good quantity.">
            <div class="mb-4">
                <input
                    type="search"
                    x-model="machineSearch"
                    placeholder="Cari mesin atau kode…"
                    aria-label="Cari mesin"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
                >
            </div>
            <div class="space-y-3">
                <template x-for="(machine, index) in filteredMachines()" :key="machine.machine_code">
                    <button type="button" @click="openMachineDetails(machine.machine_code)" class="group w-full rounded-xl px-2 py-2 text-left transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <div class="mb-1.5 flex items-center justify-between gap-3">
                            <span class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-700">
                                <span class="w-5 shrink-0 text-xs font-semibold tabular-nums text-slate-400" x-text="String(index + 1).padStart(2, '0')"></span>
                                <span class="truncate group-hover:text-blue-700" x-text="machine.machine_name"></span>
                            </span>
                            <span class="shrink-0 text-xs font-semibold tabular-nums text-slate-900" x-text="formatNumber(machine.good_qty)"></span>
                        </div>
                        <div class="ml-7 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-gradient-to-r from-blue-600 to-cyan-400 transition-all duration-500" :style="`width:${machineBarWidth(machine.good_qty)}%`"></div>
                        </div>
                    </button>
                </template>
                <div x-show="!dashboardLoading && filteredMachines().length === 0" class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                    Tidak ada mesin yang cocok dengan pencarian.
                </div>
            </div>
        </x-ui.section-card>

        <x-ui.section-card title="Machine Performance" subtitle="Tabel top 10; klik baris untuk melihat detail mesin.">
            <x-ui.table-shell>
                <table class="min-w-[620px] w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Machine</th>
                            <th class="px-4 py-3 text-right">
                                <button type="button" @click="sortMachines('good_qty')" class="hover:text-slate-900">Good Qty</button>
                            </th>
                            <th class="px-4 py-3 text-right">Reject Qty</th>
                            <th class="px-4 py-3 text-right">
                                <button type="button" @click="sortMachines('achievement')" class="hover:text-slate-900">Achievement</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-for="machine in filteredMachines()" :key="`${machine.machine_code}-row`">
                            <tr @click="openMachineDetails(machine.machine_code)" tabindex="0" @keydown.enter="openMachineDetails(machine.machine_code)" class="cursor-pointer transition hover:bg-blue-50/60">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900" x-text="machine.machine_name"></div>
                                    <div class="mt-0.5 text-xs text-slate-500" x-text="machine.machine_code"></div>
                                </td>
                                <td class="px-4 py-3 text-right font-medium tabular-nums text-slate-700" x-text="formatNumber(machine.good_qty)"></td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-600" x-text="formatNumber(machine.reject_qty)"></td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900" x-text="formatPercent(machine.achievement)"></td>
                            </tr>
                        </template>
                        <tr x-show="!dashboardLoading && filteredMachines().length === 0">
                            <td colspan="4" class="px-4 py-8">
                                <x-ui.empty-state title="Belum ada data mesin" description="Import dataset untuk menampilkan performa mesin." />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </x-ui.table-shell>
        </x-ui.section-card>
    </div>

    <div id="production-orders" class="scroll-mt-6">
        <x-ui.section-card title="Production Orders" subtitle="Cari, filter, urutkan, dan jelajahi work order dari endpoint production-orders.">
            <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(180px,1.5fr)_minmax(140px,1fr)_minmax(140px,1fr)_minmax(150px,0.8fr)_auto_auto]">
                <input
                    type="search"
                    x-model="orderFilters.search"
                    @input.debounce.350ms="loadOrders(1)"
                    placeholder="Cari WO, produk, mesin, operator…"
                    aria-label="Cari production order"
                    class="min-w-0 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
                >
                <input
                    type="text"
                    x-model="orderFilters.product"
                    placeholder="Product code / nama"
                    aria-label="Filter produk"
                    class="min-w-0 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
                >
                <input
                    type="text"
                    x-model="orderFilters.machine"
                    placeholder="Machine code / nama"
                    aria-label="Filter mesin"
                    class="min-w-0 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
                >
                <select x-model="orderFilters.status" aria-label="Filter status" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua status</option>
                    <option value="RUNNING">RUNNING</option>
                    <option value="FINISHED">FINISHED</option>
                    <option value="OPEN">OPEN</option>
                    <option value="CANCELLED">CANCELLED</option>
                </select>
                <input
                    type="date"
                    x-model="orderFilters.date"
                    aria-label="Filter tanggal rencana"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
                >
                <button type="button" @click="loadOrders(1)" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Terapkan</button>
                <button type="button" @click="clearOrderFilters()" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Reset</button>
            </div>

            <div x-show="orderError" x-cloak role="alert" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" x-text="orderError"></div>
            <x-ui.table-shell>
                <table class="min-w-[980px] w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3"><button type="button" @click="sortOrders('wo_number')" class="hover:text-slate-900">Work Order</button></th>
                            <th class="px-4 py-3"><button type="button" @click="sortOrders('product')" class="hover:text-slate-900">Product</button></th>
                            <th class="px-4 py-3"><button type="button" @click="sortOrders('machine')" class="hover:text-slate-900">Machine</button></th>
                            <th class="px-4 py-3"><button type="button" @click="sortOrders('status')" class="hover:text-slate-900">Status</button></th>
                            <th class="px-4 py-3 text-right"><button type="button" @click="sortOrders('target_qty')" class="hover:text-slate-900">Target</button></th>
                            <th class="px-4 py-3 text-right"><button type="button" @click="sortOrders('good_qty')" class="hover:text-slate-900">Good</button></th>
                            <th class="px-4 py-3 text-right"><button type="button" @click="sortOrders('reject_qty')" class="hover:text-slate-900">Reject</button></th>
                            <th class="px-4 py-3"><button type="button" @click="sortOrders('plan_start')" class="hover:text-slate-900">Plan Start</button></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-for="order in orders" :key="order.wo_number">
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900" x-text="order.wo_number"></td>
                                <td class="px-4 py-3">
                                    <div class="max-w-[240px] truncate font-medium text-slate-800" x-text="order.product_name"></div>
                                    <div class="mt-0.5 text-xs text-slate-500" x-text="order.product_code"></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800" x-text="order.machine_name"></div>
                                    <div class="mt-0.5 text-xs text-slate-500" x-text="order.machine_code"></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset" :class="statusClasses(order.status)" x-text="order.status"></span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-700" x-text="formatNumber(order.target_qty)"></td>
                                <td class="px-4 py-3 text-right tabular-nums font-medium text-emerald-700" x-text="formatNumber(order.good_qty)"></td>
                                <td class="px-4 py-3 text-right tabular-nums text-rose-700" x-text="formatNumber(order.reject_qty)"></td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600" x-text="formatDateTime(order.plan_start)"></td>
                            </tr>
                        </template>
                        <tr x-show="!orderLoading && orders.length === 0">
                            <td colspan="8" class="px-4 py-8">
                                <x-ui.empty-state title="Tidak ada production order" description="Ubah filter atau pastikan dataset sudah diimport." />
                            </td>
                        </tr>
                        <tr x-show="orderLoading">
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">Memuat production orders…</td>
                        </tr>
                    </tbody>
                </table>
            </x-ui.table-shell>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-slate-500">
                    Menampilkan <span class="font-semibold text-slate-700" x-text="formatNumber(orderMeta.from || 0)"></span>–<span class="font-semibold text-slate-700" x-text="formatNumber(orderMeta.to || 0)"></span>
                    dari <span class="font-semibold text-slate-700" x-text="formatNumber(orderMeta.total || 0)"></span> work order
                </div>
                <div class="flex items-center gap-2">
                    <label for="orders-per-page" class="text-xs text-slate-500">Baris</label>
                    <select id="orders-per-page" x-model.number="orderFilters.per_page" @change="loadOrders(1)" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <button type="button" @click="loadOrders(Math.max(1, orderMeta.current_page - 1))" :disabled="orderMeta.current_page <= 1 || orderLoading" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Sebelumnya</button>
                    <span class="min-w-16 text-center text-xs font-medium text-slate-600" x-text="`${orderMeta.current_page || 1} / ${orderMeta.last_page || 1}`"></span>
                    <button type="button" @click="loadOrders(Math.min(orderMeta.last_page, orderMeta.current_page + 1))" :disabled="orderMeta.current_page >= orderMeta.last_page || orderLoading" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Berikutnya</button>
                </div>
            </div>
        </x-ui.section-card>
    </div>

    <div
        x-show="machineDetail || machineDetailLoading || machineDetailError"
        x-cloak
        @keydown.escape.window="closeMachineDetails()"
        @click.self="closeMachineDetails()"
        class="fixed inset-0 z-50 grid place-items-center bg-slate-950/40 p-4 backdrop-blur-sm"
    >
        <section role="dialog" aria-modal="true" aria-labelledby="machine-detail-title" class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-600">Machine detail</div>
                    <h2 id="machine-detail-title" class="mt-1 text-xl font-semibold text-slate-900" x-text="machineDetail?.machine_name || 'Memuat mesin…'"></h2>
                    <div class="mt-1 text-sm text-slate-500" x-text="machineDetail?.machine_code || ''"></div>
                </div>
                <button type="button" @click="closeMachineDetails()" aria-label="Tutup detail" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div x-show="machineDetailLoading" class="py-10 text-center text-sm text-slate-500">Memuat performa mesin…</div>
            <div x-show="machineDetailError" x-cloak role="alert" class="mt-5 rounded-xl bg-rose-50 p-3 text-sm text-rose-700" x-text="machineDetailError"></div>
            <dl x-show="machineDetail" class="mt-6 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs text-slate-500">Total Work Order</dt><dd class="mt-1 text-xl font-semibold text-slate-900" x-text="formatNumber(machineDetail?.total_order)"></dd></div>
                <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs text-slate-500">Achievement</dt><dd class="mt-1 text-xl font-semibold text-blue-700" x-text="formatPercent(machineDetail?.achievement)"></dd></div>
                <div class="rounded-2xl bg-emerald-50 p-4"><dt class="text-xs text-emerald-700">Good Qty</dt><dd class="mt-1 text-xl font-semibold text-emerald-900" x-text="formatNumber(machineDetail?.good_qty)"></dd></div>
                <div class="rounded-2xl bg-rose-50 p-4"><dt class="text-xs text-rose-700">Reject Qty</dt><dd class="mt-1 text-xl font-semibold text-rose-900" x-text="formatNumber(machineDetail?.reject_qty)"></dd></div>
                <div class="col-span-2 rounded-2xl bg-amber-50 p-4"><dt class="text-xs text-amber-700">Downtime</dt><dd class="mt-1 text-xl font-semibold text-amber-900"><span x-text="formatNumber(machineDetail?.downtime_minutes)"></span> menit</dd></div>
            </dl>
        </section>
    </div>
</div>
@endsection
