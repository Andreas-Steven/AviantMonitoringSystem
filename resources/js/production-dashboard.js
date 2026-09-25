import Alpine from 'alpinejs';

Alpine.data('productionDashboard', () => ({
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
    activeTrendPoint: null,
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
    chartMax() {
        return Math.max(1, ...this.trend.map((day) => Number(day[this.trendMetric] || 0)));
    },
    chartPoints() {
        const left = 54;
        const right = 692;
        const top = 24;
        const bottom = 190;
        const max = this.chartMax();

        return this.trend.map((day, index) => {
            const value = Number(day[this.trendMetric] || 0);
            const x = this.trend.length > 1
                ? left + ((right - left) * index) / (this.trend.length - 1)
                : (left + right) / 2;

            return {
                ...day,
                x,
                y: bottom - ((value / max) * (bottom - top)),
                value,
            };
        });
    },
    chartGridLines() {
        const max = this.chartMax();

        return Array.from({ length: 5 }, (_, index) => ({
            y: 24 + (42 * index),
            value: Math.round((max * (4 - index)) / 4),
        }));
    },
    trendLinePath() {
        return this.chartPoints()
            .map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`)
            .join(' ');
    },
    trendAreaPath() {
        const points = this.chartPoints();
        if (!points.length) return '';

        return `M ${points[0].x} 190 ${points.map((point) => `L ${point.x} ${point.y}`).join(' ')} L ${points[points.length - 1].x} 190 Z`;
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
            RUNNING: 'bg-blue-50 text-blue-700 ring-blue-200',
            FINISHED: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            OPEN: 'bg-amber-50 text-amber-700 ring-amber-200',
            CANCELLED: 'bg-rose-50 text-rose-700 ring-rose-200',
        }[String(status).toUpperCase()] || 'bg-slate-100 text-slate-700 ring-slate-200';
    },
    statusDonutStyle() {
        const total = this.orderCount;
        if (!total) return 'background: conic-gradient(#e2e8f0 0deg 360deg)';

        let start = 0;
        const slices = this.statusBreakdown.map((item) => {
            const end = start + ((Number(item.total || 0) / total) * 360);
            const slice = `${this.statusColor(item.status)} ${start}deg ${end}deg`;
            start = end;
            return slice;
        });

        return `background: conic-gradient(${slices.join(', ')})`;
    },
    machineBarWidth(value) {
        const maximum = Math.max(1, ...this.topMachines.map((machine) => Number(machine.good_qty || 0)));
        return Math.min(100, (Number(value || 0) / maximum) * 100);
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
    formatDateShort(value) {
        if (!value) return '';
        const [, month, day] = String(value).slice(0, 10).split('-');
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return `${day} ${monthNames[Number(month) - 1]}`;
    },
    formatDateTime(value) {
        return value ? String(value).replace('T', ' ').slice(0, 16) : '—';
    },
}));
