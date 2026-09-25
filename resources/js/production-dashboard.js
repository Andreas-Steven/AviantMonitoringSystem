import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

Alpine.data('productionDashboard', () => {
    const charts = { trend: null, status: null, machine: null };

    return {
    dashboardLoading: true,
    dashboardError: '',
    orderLoading: false,
    orderError: '',
    summary: {
        total_machine: 0,
        running_order: 0,
        finished_order: 0,
        today_target: 0,
        today_good: 0,
        today_reject: 0,
        achievement: 0,
    },
    trend: [],
    statusBreakdown: [],
    topMachines: [],
    trendMetric: 'good_qty',
    activeStatus: null,
    machineSearch: '',
    machineSort: { field: 'good_qty', direction: 'desc' },
    orders: [],
    orderMeta: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },
    orderFilters: {
        search: '',
        product: '',
        machine: '',
        status: '',
        date: '',
        sort_by: 'plan_start',
        sort_direction: 'desc',
        per_page: 10,
        page: 1,
    },
    machineDetail: null,
    machineDetailLoading: false,
    machineDetailError: '',
    get orderCount() {
        return this.statusBreakdown.reduce((count, item) => count + Number(item.total || 0), 0);
    },
    init() {
        this.$nextTick(() => this.initCharts());
        this.$watch('trendMetric', () => this.updateTrendChart());
        this.$watch('machineSearch', () => this.updateMachineChart());
        window.addEventListener('theme-changed', () => this.applyChartTheme());
        this.refresh();
    },
    async requestJson(url, options = {}) {
        const headers = new Headers(options.headers || {});
        headers.set('Accept', 'application/json');

        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (token) {
            headers.set('X-CSRF-TOKEN', token);
        }

        const response = await fetch(url, {
            ...options,
            credentials: 'same-origin',
            headers,
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message || `Request gagal (${response.status}).`);
        }

        return payload;
    },
    async refresh() {
        await Promise.all([this.loadDashboard(), this.loadOrders(1)]);
    },
    async loadDashboard() {
        this.dashboardLoading = true;
        this.dashboardError = '';

        try {
            const response = await this.requestJson('/api/dashboard');
            const payload = response.data || {};
            this.summary = payload.summary || this.summary;
            this.trend = payload.trend_7_days || [];
            this.statusBreakdown = payload.status_breakdown || [];
            this.topMachines = payload.top_machines || [];
            this.updateCharts();
        } catch (error) {
            this.dashboardError = error.message;
        } finally {
            this.dashboardLoading = false;
        }
    },
    async loadOrders(page = this.orderFilters.page) {
        this.orderLoading = true;
        this.orderError = '';
        this.orderFilters.page = page;
        const params = new URLSearchParams();

        Object.entries(this.orderFilters).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                params.set(key, value);
            }
        });

        try {
            const response = await this.requestJson(`/api/production-orders?${params.toString()}`);
            const pagination = response.meta?.pagination || {};
            const currentPage = Number(pagination.page || 1);
            const pageSize = Number(pagination.page_size || 10);
            const total = Number(pagination.total || 0);
            const display = Number(pagination.display || 0);
            const offset = (currentPage - 1) * pageSize;

            this.orders = response.data || [];
            this.orderMeta = {
                current_page: currentPage,
                last_page: Math.max(1, Math.ceil(total / pageSize)),
                from: display > 0 ? offset + 1 : 0,
                to: display > 0 ? offset + display : 0,
                total,
            };
        } catch (error) {
            this.orders = [];
            this.orderMeta = { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 };
            this.orderError = error.message;
        } finally {
            this.orderLoading = false;
        }
    },
    clearOrderFilters() {
        this.orderFilters = {
            search: '',
            product: '',
            machine: '',
            status: '',
            date: '',
            sort_by: 'plan_start',
            sort_direction: 'desc',
            per_page: 10,
            page: 1,
        };
        this.loadOrders(1);
    },
    sortOrders(field) {
        if (this.orderFilters.sort_by === field) {
            this.orderFilters.sort_direction = this.orderFilters.sort_direction === 'asc' ? 'desc' : 'asc';
        } else {
            this.orderFilters.sort_by = field;
            this.orderFilters.sort_direction = 'asc';
        }
        this.loadOrders(1);
    },
    sortMachines(field) {
        if (this.machineSort.field === field) {
            this.machineSort.direction = this.machineSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            this.machineSort = { field, direction: 'desc' };
        }
    },
    filteredMachines() {
        const term = this.machineSearch.trim().toLowerCase();
        const rows = this.topMachines.filter((machine) => {
            const name = String(machine.machine_name || '').toLowerCase();
            const code = String(machine.machine_code || '').toLowerCase();
            return !term || name.includes(term) || code.includes(term);
        });
        const { field, direction } = this.machineSort;

        return rows.sort((left, right) => {
            const a = left[field];
            const b = right[field];
            const comparison = typeof a === 'string'
                ? String(a).localeCompare(String(b))
                : Number(a || 0) - Number(b || 0);

            return direction === 'asc' ? comparison : -comparison;
        });
    },
    isDarkMode() {
        return document.documentElement.classList.contains('dark');
    },
    initCharts() {
        const dark = this.isDarkMode();
        Chart.defaults.font.family = 'ui-sans-serif, system-ui, sans-serif';
        Chart.defaults.color = dark ? '#94a3b8' : '#64748b';

        const gridColor = dark ? '#334155' : '#e2e8f0';
        const donutBorder = dark ? '#0f172a' : '#ffffff';

        if (this.$refs.trendChart) {
            charts.trend = new Chart(this.$refs.trendChart, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Good Qty',
                        data: [],
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.15)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#2563eb',
                        pointBorderWidth: 2.5,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => ` ${context.dataset.label}: ${this.formatNumber(context.parsed.y)}`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            border: { display: false },
                            grid: { display: false },
                            ticks: { font: { size: 10 } },
                        },
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: gridColor },
                            ticks: { font: { size: 10 }, callback: (value) => this.formatCompact(value) },
                        },
                    },
                },
            });
        }

        if (this.$refs.statusChart) {
            charts.status = new Chart(this.$refs.statusChart, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [],
                        borderColor: donutBorder,
                        borderWidth: 2,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    onHover: (event, elements) => {
                        const index = elements.length && this.orderCount ? elements[0].index : -1;
                        this.activeStatus = index >= 0 ? this.statusBreakdown[index] || null : null;
                        if (event.native?.target) {
                            event.native.target.style.cursor = index >= 0 ? 'pointer' : 'default';
                        }
                    },
                    onClick: (event, elements) => {
                        const item = elements.length && this.orderCount ? this.statusBreakdown[elements[0].index] : null;
                        if (item) this.applyStatusFilter(item.status);
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            filter: () => this.orderCount > 0,
                            callbacks: {
                                label: (context) => {
                                    const percent = (context.parsed / this.orderCount) * 100;
                                    return ` ${context.label}: ${this.formatNumber(context.parsed)} (${this.formatPercent(percent)})`;
                                },
                            },
                        },
                    },
                },
            });
        }

        if (this.$refs.machineChart) {
            charts.machine = new Chart(this.$refs.machineChart, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Good Qty',
                        data: [],
                        backgroundColor: '#2563eb',
                        hoverBackgroundColor: '#1d4ed8',
                        borderRadius: 6,
                        maxBarThickness: 28,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    onHover: (event, elements) => {
                        if (event.native?.target) {
                            event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                        }
                    },
                    onClick: (event, elements) => {
                        const machine = elements.length ? this.filteredMachines()[elements[0].index] : null;
                        if (machine) this.openMachineDetails(machine.machine_code);
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => ` Good Qty: ${this.formatNumber(context.parsed.x)}`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: gridColor },
                            ticks: { font: { size: 10 }, callback: (value) => this.formatCompact(value) },
                        },
                        y: {
                            border: { display: false },
                            grid: { display: false },
                            ticks: { font: { size: 11 } },
                        },
                    },
                },
            });
        }
    },
    applyChartTheme() {
        const dark = this.isDarkMode();
        Chart.defaults.color = dark ? '#94a3b8' : '#64748b';
        const gridColor = dark ? '#334155' : '#e2e8f0';

        if (charts.trend) {
            charts.trend.options.scales.y.grid.color = gridColor;
            charts.trend.update();
        }
        if (charts.machine) {
            charts.machine.options.scales.x.grid.color = gridColor;
            charts.machine.update();
        }
        if (charts.status) {
            charts.status.data.datasets[0].borderColor = dark ? '#0f172a' : '#ffffff';
            charts.status.update();
        }
    },
    updateCharts() {
        if (!charts.trend && !charts.status && !charts.machine) {
            this.initCharts();
        }
        this.updateTrendChart();
        this.updateStatusChart();
        this.updateMachineChart();
    },
    updateTrendChart() {
        if (!charts.trend) return;

        const reject = this.trendMetric === 'reject_qty';
        const color = reject ? '#dc2626' : '#2563eb';
        const dataset = charts.trend.data.datasets[0];
        dataset.label = reject ? 'Reject Qty' : 'Good Qty';
        dataset.data = this.trend.map((day) => Number(day[this.trendMetric] || 0));
        dataset.borderColor = color;
        dataset.backgroundColor = reject ? 'rgba(220, 38, 38, 0.15)' : 'rgba(37, 99, 235, 0.15)';
        dataset.pointBorderColor = color;
        charts.trend.data.labels = this.trend.map((day) => this.formatDate(day.date));
        charts.trend.update();
    },
    updateStatusChart() {
        if (!charts.status) return;

        const hasData = this.orderCount > 0;
        charts.status.data.labels = hasData ? this.statusBreakdown.map((item) => item.status) : ['Kosong'];
        charts.status.data.datasets[0].data = hasData ? this.statusBreakdown.map((item) => Number(item.total || 0)) : [1];
        charts.status.data.datasets[0].backgroundColor = hasData
            ? this.statusBreakdown.map((item) => this.statusColor(item.status))
            : ['#e2e8f0'];
        charts.status.update();
    },
    updateMachineChart() {
        if (!charts.machine) return;

        const machines = this.filteredMachines();
        charts.machine.data.labels = machines.map((machine) => machine.machine_name);
        charts.machine.data.datasets[0].data = machines.map((machine) => Number(machine.good_qty || 0));
        charts.machine.update();
    },
    highlightStatus(status) {
        const index = this.statusBreakdown.findIndex((item) => item.status === status);
        this.activeStatus = index >= 0 ? this.statusBreakdown[index] : null;
        if (!charts.status || index < 0) return;

        const active = [{ datasetIndex: 0, index }];
        charts.status.setActiveElements(active);
        charts.status.tooltip.setActiveElements(active, { x: 0, y: 0 });
        charts.status.update();
    },
    clearStatusHighlight() {
        this.activeStatus = null;
        if (!charts.status) return;

        charts.status.setActiveElements([]);
        charts.status.tooltip.setActiveElements([], { x: 0, y: 0 });
        charts.status.update();
    },
    statusColor(status) {
        return {
            RUNNING: '#3b82f6',
            FINISHED: '#10b981',
            OPEN: '#f59e0b',
            CANCELLED: '#f43f5e',
        }[String(status).toUpperCase()] || '#64748b';
    },
    statusClasses(status) {
        return {
            RUNNING: 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/30',
            FINISHED: 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30',
            OPEN: 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30',
            CANCELLED: 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30',
        }[String(status).toUpperCase()] || 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-500/10 dark:text-slate-300 dark:ring-slate-500/30';
    },
    applyStatusFilter(status) {
        this.orderFilters.status = this.orderFilters.status === status ? '' : status;
        this.loadOrders(1);
        document.getElementById('production-orders')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
    async openMachineDetails(machineCode) {
        this.machineDetail = null;
        this.machineDetailError = '';
        this.machineDetailLoading = true;

        try {
            const response = await this.requestJson(`/api/dashboard/machine/${encodeURIComponent(machineCode)}`);
            this.machineDetail = response.data || null;
        } catch (error) {
            this.machineDetailError = error.message;
        } finally {
            this.machineDetailLoading = false;
        }
    },
    closeMachineDetails() {
        this.machineDetail = null;
        this.machineDetailError = '';
    },
    formatNumber(value) {
        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value || 0));
    },
    formatCompact(value) {
        return new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(Number(value || 0));
    },
    formatPercent(value) {
        return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value || 0))}%`;
    },
    formatDate(value) {
        if (!value) return '—';
        const [year, month, day] = String(value).slice(0, 10).split('-');
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return `${day} ${monthNames[Number(month) - 1]} ${year}`;
    },
    formatDateTime(value) {
        return value ? String(value).replace('T', ' ').slice(0, 16) : '—';
    },
    };
});
