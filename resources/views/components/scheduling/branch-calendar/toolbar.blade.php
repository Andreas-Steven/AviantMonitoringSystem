@props([
    'branchName' => '',
    'periodCode' => '',
    'dateFrom' => '',
    'dateTo' => '',
    'viewMode' => 'grid',
])

<x-ui.section-card>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">
                {{ $dateFrom }} — {{ $dateTo }}
            </h2>
            <p class="text-sm text-slate-500">
                Branch: <span class="font-medium text-slate-700">{{ $branchName }}</span>
                <span class="mx-2 text-slate-300">•</span>
                Period: <span class="font-medium text-slate-700">{{ $periodCode }}</span>
            </p>
        </div>

        <div class="inline-flex rounded-xl border border-slate-300 bg-slate-50 p-1">
            <span class="{{ $viewMode === 'grid' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }} rounded-lg px-4 py-2 text-sm font-medium">
                Grid
            </span>
            <span class="{{ $viewMode === 'list' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }} rounded-lg px-4 py-2 text-sm font-medium">
                List
            </span>
        </div>
    </div>
</x-ui.section-card>