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
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 disabled:cursor-wait disabled:opacity-60 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M20 7v5h-5"/><path d="M4.8 9a8 8 0 0 1 13.5-2L20 12M4 17v-5h5"/><path d="M19.2 15a8 8 0 0 1-13.5 2L4 12"/>
                    </svg>
                    <span x-text="dashboardLoading ? 'Memuat…' : 'Refresh data'"></span>
                </button>
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    <div x-show="dashboardError" x-cloak role="alert" class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between dark:border-amber-900/60 dark:bg-amber-950/50 dark:text-amber-200">
        <div>
            <div class="font-semibold">Data dashboard belum tersedia</div>
            <p class="mt-1 text-amber-800 dark:text-amber-300" x-text="dashboardError"></p>
        </div>
        <button type="button" @click="refresh()" class="shrink-0 rounded-xl border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-900 transition hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-900/40 dark:text-amber-200 dark:hover:bg-amber-900/70">
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
                <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-slate-700 dark:bg-slate-800">
                    <button
                        type="button"
                        @click="trendMetric = 'good_qty'"
                        :class="trendMetric === 'good_qty' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-slate-100' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                    >Good Qty</button>
                    <button
                        type="button"
                        @click="trendMetric = 'reject_qty'"
                        :class="trendMetric === 'reject_qty' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-slate-100' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                    >Reject Qty</button>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span class="h-2.5 w-2.5 rounded-full" :class="trendMetric === 'good_qty' ? 'bg-blue-600 dark:bg-blue-400' : 'bg-red-600 dark:bg-red-400'"></span>
                    <span x-text="trendMetric === 'good_qty' ? 'Good quantity' : 'Reject quantity'"></span>
                </div>
            </div>

            <div class="relative h-56 sm:h-64">
                <canvas x-ref="trendChart" role="img" aria-label="Grafik tren produksi tujuh hari"></canvas>
                <div x-show="!dashboardLoading && !dashboardError && trend.length === 0" x-cloak class="absolute inset-0 grid place-items-center bg-white/80 dark:bg-slate-900/80">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada hasil produksi.</p>
                </div>
            </div>
        </x-ui.section-card>

        <x-ui.section-card title="Work Order Status" subtitle="Pilih status untuk memfilter daftar production order.">
            <div class="flex flex-col items-center gap-6 sm:flex-row xl:flex-col 2xl:flex-row">
                <div class="relative h-48 w-48 shrink-0">
                    <canvas x-ref="statusChart" class="relative z-10" role="img" aria-label="Diagram status work order"></canvas>
                    <div class="pointer-events-none absolute inset-5 z-0 grid place-items-center rounded-full bg-white text-center shadow-inner dark:bg-slate-900">
                        <div x-show="!activeStatus">
                            <div class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100" x-text="formatNumber(orderCount)"></div>
                            <div class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Total WO</div>
                        </div>
                        <div x-show="activeStatus" x-cloak class="px-2">
                            <div class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100" x-text="formatNumber(activeStatus?.total)"></div>
                            <div class="mt-1 text-xs font-semibold uppercase tracking-wide" :style="`color:${statusColor(activeStatus?.status)}`" x-text="activeStatus?.status"></div>
                            <div class="text-xs tabular-nums text-slate-500 dark:text-slate-400" x-text="formatPercent(orderCount ? (Number(activeStatus?.total || 0) / orderCount) * 100 : 0)"></div>
                        </div>
                    </div>
                </div>
                <div class="grid w-full gap-2">
                    <template x-for="item in statusBreakdown" :key="item.status">
                        <button
                            type="button"
                            @click="applyStatusFilter(item.status)"
                            @mouseenter="highlightStatus(item.status)"
                            @mouseleave="clearStatusHighlight()"
                            :class="(orderFilters.status === item.status || activeStatus?.status === item.status) ? 'ring-2 ring-slate-300 bg-slate-50 dark:ring-slate-600 dark:bg-slate-800' : 'hover:bg-slate-50 dark:hover:bg-slate-800'"
                            class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition"
                        >
                            <span class="flex min-w-0 items-center gap-2.5">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="`background-color:${statusColor(item.status)}`"></span>
                                <span class="truncate text-sm font-medium text-slate-700 dark:text-slate-300" x-text="item.status"></span>
                            </span>
                            <span class="text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100" x-text="formatNumber(item.total)"></span>
                        </button>
                    </template>
                    <div x-show="!dashboardLoading && statusBreakdown.length === 0" class="text-sm text-slate-500 dark:text-slate-400">Status belum tersedia.</div>
                </div>
            </div>
        </x-ui.section-card>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
        <x-ui.section-card title="Top 10 Machines" subtitle="Peringkat mesin berdasarkan good quantity." class="flex h-full flex-col" body-class="flex flex-1 flex-col">
            <div class="mb-4">
                <input
                    type="search"
                    x-model="machineSearch"
                    placeholder="Cari mesin atau kode…"
                    aria-label="Cari mesin"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:ring-blue-500/20"
                >
            </div>
            <div class="relative min-h-72 flex-1">
                <canvas x-ref="machineChart" role="img" aria-label="Grafik batang top 10 mesin berdasarkan good quantity"></canvas>
                <div x-show="!dashboardLoading && filteredMachines().length === 0" x-cloak class="absolute inset-0 grid place-items-center bg-white/80 dark:bg-slate-900/80">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Tidak ada mesin yang cocok dengan pencarian.</p>
                </div>
            </div>
        </x-ui.section-card>

        <x-ui.section-card title="Machine Performance" subtitle="Tabel top 10; klik baris untuk melihat detail mesin.">
            <x-ui.table-shell>
                <table class="min-w-[620px] w-full divide-y divide-slate-100 text-left text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Machine</th>
                            <th class="px-4 py-3 text-right">
                                <button type="button" @click="sortMachines('good_qty')" class="hover:text-slate-900 dark:hover:text-slate-200">Good Qty</button>
                            </th>
                            <th class="px-4 py-3 text-right">Reject Qty</th>
                            <th class="px-4 py-3 text-right">
                                <button type="button" @click="sortMachines('achievement')" class="hover:text-slate-900 dark:hover:text-slate-200">Achievement</button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        <template x-for="machine in filteredMachines()" :key="`${machine.machine_code}-row`">
                            <tr @click="openMachineDetails(machine.machine_code)" tabindex="0" @keydown.enter="openMachineDetails(machine.machine_code)" class="cursor-pointer transition hover:bg-blue-50/60 dark:hover:bg-slate-800/60">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900 dark:text-slate-100" x-text="machine.machine_name"></div>
                                    <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400" x-text="machine.machine_code"></div>
                                </td>
                                <td class="px-4 py-3 text-right font-medium tabular-nums text-slate-700 dark:text-slate-300" x-text="formatNumber(machine.good_qty)"></td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-600 dark:text-slate-400" x-text="formatNumber(machine.reject_qty)"></td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900 dark:text-slate-100" x-text="formatPercent(machine.achievement)"></td>
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
            <div x-show="orderError" x-cloak role="alert" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/50 dark:text-rose-300" x-text="orderError"></div>
            <x-ui.table-shell>
                <table class="min-w-[980px] w-full divide-y divide-slate-100 text-left text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3"><button type="button" @click="sortOrders('wo_number')" class="hover:text-slate-900 dark:hover:text-slate-200">Work Order</button></th>
                            <th class="px-4 py-3"><button type="button" @click="sortOrders('product')" class="hover:text-slate-900 dark:hover:text-slate-200">Product</button></th>
                            <th class="px-4 py-3"><button type="button" @click="sortOrders('machine')" class="hover:text-slate-900 dark:hover:text-slate-200">Machine</button></th>
                            <th class="px-4 py-3"><button type="button" @click="sortOrders('status')" class="hover:text-slate-900 dark:hover:text-slate-200">Status</button></th>
                            <th class="px-4 py-3 text-right"><button type="button" @click="sortOrders('target_qty')" class="hover:text-slate-900 dark:hover:text-slate-200">Target</button></th>
                            <th class="px-4 py-3 text-right"><button type="button" @click="sortOrders('good_qty')" class="hover:text-slate-900 dark:hover:text-slate-200">Good</button></th>
                            <th class="px-4 py-3 text-right"><button type="button" @click="sortOrders('reject_qty')" class="hover:text-slate-900 dark:hover:text-slate-200">Reject</button></th>
                            <th class="px-4 py-3"><button type="button" @click="sortOrders('plan_start')" class="hover:text-slate-900 dark:hover:text-slate-200">Plan Start</button></th>
                        </tr>
                        <tr class="border-t border-slate-200 dark:border-slate-700">
                            <th class="px-2 pb-3 pt-1 font-normal normal-case tracking-normal">
                                <input
                                    type="search"
                                    x-model="orderFilters.search"
                                    @input.debounce.350ms="loadOrders(1)"
                                    placeholder="Cari WO…"
                                    aria-label="Cari work order"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-normal text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:ring-blue-500/20"
                                >
                            </th>
                            <th class="px-2 pb-3 pt-1 font-normal normal-case tracking-normal">
                                <input
                                    type="text"
                                    x-model="orderFilters.product"
                                    @input.debounce.350ms="loadOrders(1)"
                                    placeholder="Cari produk…"
                                    aria-label="Filter produk"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-normal text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:ring-blue-500/20"
                                >
                            </th>
                            <th class="px-2 pb-3 pt-1 font-normal normal-case tracking-normal">
                                <input
                                    type="text"
                                    x-model="orderFilters.machine"
                                    @input.debounce.350ms="loadOrders(1)"
                                    placeholder="Cari mesin…"
                                    aria-label="Filter mesin"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-normal text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:ring-blue-500/20"
                                >
                            </th>
                            <th class="px-2 pb-3 pt-1 font-normal normal-case tracking-normal">
                                <select x-model="orderFilters.status" @change="loadOrders(1)" aria-label="Filter status" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-normal text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:ring-blue-500/20">
                                    <option value="">Semua</option>
                                    <option value="RUNNING">RUNNING</option>
                                    <option value="FINISHED">FINISHED</option>
                                    <option value="OPEN">OPEN</option>
                                    <option value="CANCELLED">CANCELLED</option>
                                </select>
                            </th>
                            <th class="px-2 pb-3 pt-1"></th>
                            <th class="px-2 pb-3 pt-1"></th>
                            <th class="px-2 pb-3 pt-1"></th>
                            <th class="px-2 pb-3 pt-1 font-normal normal-case tracking-normal">
                                <div class="flex items-center gap-1.5">
                                    <input
                                        type="date"
                                        x-model="orderFilters.date"
                                        @change="loadOrders(1)"
                                        aria-label="Filter tanggal rencana"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-normal text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:ring-blue-500/20"
                                    >
                                    <button type="button" @click="clearOrderFilters()" title="Reset filter" aria-label="Reset filter" class="shrink-0 rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 7v5h-5"/><path d="M4.8 9a8 8 0 0 1 13.5-2L20 12M4 17v-5h5"/><path d="M19.2 15a8 8 0 0 1-13.5 2L4 12"/></svg>
                                    </button>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        <template x-for="order in orders" :key="order.wo_number">
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900 dark:text-slate-100" x-text="order.wo_number"></td>
                                <td class="px-4 py-3">
                                    <div class="max-w-[240px] truncate font-medium text-slate-800 dark:text-slate-200" x-text="order.product_name"></div>
                                    <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400" x-text="order.product_code"></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800 dark:text-slate-200" x-text="order.machine_name"></div>
                                    <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400" x-text="order.machine_code"></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset" :class="statusClasses(order.status)" x-text="order.status"></span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-700 dark:text-slate-300" x-text="formatNumber(order.target_qty)"></td>
                                <td class="px-4 py-3 text-right tabular-nums font-medium text-emerald-700 dark:text-emerald-400" x-text="formatNumber(order.good_qty)"></td>
                                <td class="px-4 py-3 text-right tabular-nums text-rose-700 dark:text-rose-400" x-text="formatNumber(order.reject_qty)"></td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-400" x-text="formatDateTime(order.plan_start)"></td>
                            </tr>
                        </template>
                        <tr x-show="!orderLoading && orders.length === 0">
                            <td colspan="8" class="px-4 py-8">
                                <x-ui.empty-state title="Tidak ada production order" description="Ubah filter atau pastikan dataset sudah diimport." />
                            </td>
                        </tr>
                        <tr x-show="orderLoading">
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Memuat production orders…</td>
                        </tr>
                    </tbody>
                </table>
            </x-ui.table-shell>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="formatNumber(orderMeta.from || 0)"></span>–<span class="font-semibold text-slate-700 dark:text-slate-200" x-text="formatNumber(orderMeta.to || 0)"></span>
                    dari <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="formatNumber(orderMeta.total || 0)"></span> work order
                </div>
                <div class="flex items-center gap-2">
                    <label for="orders-per-page" class="text-xs text-slate-500 dark:text-slate-400">Baris</label>
                    <select id="orders-per-page" x-model.number="orderFilters.per_page" @change="loadOrders(1)" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <button type="button" @click="loadOrders(Math.max(1, orderMeta.current_page - 1))" :disabled="orderMeta.current_page <= 1 || orderLoading" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Sebelumnya</button>
                    <span class="min-w-16 text-center text-xs font-medium text-slate-600 dark:text-slate-300" x-text="`${orderMeta.current_page || 1} / ${orderMeta.last_page || 1}`"></span>
                    <button type="button" @click="loadOrders(Math.min(orderMeta.last_page, orderMeta.current_page + 1))" :disabled="orderMeta.current_page >= orderMeta.last_page || orderLoading" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Berikutnya</button>
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
        <section role="dialog" aria-modal="true" aria-labelledby="machine-detail-title" class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-600 dark:text-blue-400">Machine detail</div>
                    <h2 id="machine-detail-title" class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100" x-text="machineDetail?.machine_name || 'Memuat mesin…'"></h2>
                    <div class="mt-1 text-sm text-slate-500 dark:text-slate-400" x-text="machineDetail?.machine_code || ''"></div>
                </div>
                <button type="button" @click="closeMachineDetails()" aria-label="Tutup detail" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div x-show="machineDetailLoading" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">Memuat performa mesin…</div>
            <div x-show="machineDetailError" x-cloak role="alert" class="mt-5 rounded-xl bg-rose-50 p-3 text-sm text-rose-700 dark:bg-rose-950/50 dark:text-rose-300" x-text="machineDetailError"></div>
            <dl x-show="machineDetail" class="mt-6 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800"><dt class="text-xs text-slate-500 dark:text-slate-400">Total Work Order</dt><dd class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100" x-text="formatNumber(machineDetail?.total_order)"></dd></div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800"><dt class="text-xs text-slate-500 dark:text-slate-400">Achievement</dt><dd class="mt-1 text-xl font-semibold text-blue-700 dark:text-blue-400" x-text="formatPercent(machineDetail?.achievement)"></dd></div>
                <div class="rounded-2xl bg-emerald-50 p-4 dark:bg-emerald-500/10"><dt class="text-xs text-emerald-700 dark:text-emerald-400">Good Qty</dt><dd class="mt-1 text-xl font-semibold text-emerald-900 dark:text-emerald-300" x-text="formatNumber(machineDetail?.good_qty)"></dd></div>
                <div class="rounded-2xl bg-rose-50 p-4 dark:bg-rose-500/10"><dt class="text-xs text-rose-700 dark:text-rose-400">Reject Qty</dt><dd class="mt-1 text-xl font-semibold text-rose-900 dark:text-rose-300" x-text="formatNumber(machineDetail?.reject_qty)"></dd></div>
                <div class="col-span-2 rounded-2xl bg-amber-50 p-4 dark:bg-amber-500/10"><dt class="text-xs text-amber-700 dark:text-amber-400">Downtime</dt><dd class="mt-1 text-xl font-semibold text-amber-900 dark:text-amber-300"><span x-text="formatNumber(machineDetail?.downtime_minutes)"></span> menit</dd></div>
            </dl>
        </section>
    </div>
</div>
@endsection
