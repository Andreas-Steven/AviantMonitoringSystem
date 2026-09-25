@props([
    'actions' => [],
    'align' => 'right',
    'width' => 'w-56',
])

@php
    $filteredActions = collect($actions)->filter(function ($action) {
        if (!is_array($action)) {
            return false;
        }

        if (($action['visible'] ?? true) === false) {
            return false;
        }

        return filled($action['label'] ?? null);
    })->values();
@endphp

<div
    x-data="{
        open: false,
        top: 0,
        left: 0,
        width: 224,
        panelHeight: 0,

        updatePosition() {
            const rect = this.$refs.trigger.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const gap = 8;
            const estimatedHeight = this.panelHeight || 220;

            this.width = this.$refs.panel ? this.$refs.panel.offsetWidth || 224 : 224;

            if ('{{ $align }}' === 'left') {
                this.left = rect.left;
            } else {
                this.left = rect.right - this.width;
            }

            if (this.left < gap) {
                this.left = gap;
            }

            if ((this.left + this.width) > (viewportWidth - gap)) {
                this.left = viewportWidth - this.width - gap;
            }

            this.top = rect.bottom + gap;

            if ((this.top + estimatedHeight) > (viewportHeight - gap)) {
                this.top = Math.max(gap, rect.top - estimatedHeight - gap);
            }
        },

        toggle() {
            this.open = !this.open;

            if (this.open) {
                this.$nextTick(() => {
                    this.panelHeight = this.$refs.panel ? this.$refs.panel.offsetHeight : 0;
                    this.updatePosition();
                });
            }
        },

        close() {
            this.open = false;
        }
    }"
    x-on:keydown.escape.window="close()"
    x-on:resize.window="if (open) updatePosition()"
    x-on:scroll.window="if (open) updatePosition()"
    class="relative inline-block text-left"
>
    <button
        type="button"
        x-ref="trigger"
        @click="toggle()"
        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
    >
        <span>Actions</span>
        <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 z-[9998]"
            style="display: none;"
            @click="close()"
        >
            <div
                x-ref="panel"
                @click.stop
                x-show="open"
                x-transition.origin.top.right
                class="fixed {{ $width }} z-[9999] rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl"
                :style="`top:${top}px; left:${left}px;`"
            >
                <div class="space-y-1">
                    @forelse($filteredActions as $action)
                        @php
                            $type = $action['type'] ?? 'link';
                            $tone = $action['tone'] ?? 'default';

                            $baseClasses = 'flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm transition';
                            $toneClasses = match ($tone) {
                                'danger' => 'text-red-600 hover:bg-red-50',
                                'warning' => 'text-amber-700 hover:bg-amber-50',
                                default => 'text-slate-700 hover:bg-slate-50',
                            };
                        @endphp

                        @if($type === 'form')
                            <form method="POST" action="{{ $action['url'] ?? '#' }}">
                                @csrf
                                @if(($action['method'] ?? 'POST') !== 'POST')
                                    @method($action['method'])
                                @endif

                                <button
                                    type="submit"
                                    class="{{ $baseClasses }} {{ $toneClasses }}"
                                    @if(!empty($action['confirm']))
                                        onclick="return confirm(@js($action['confirm']))"
                                    @endif
                                >
                                    <span>{{ $action['label'] }}</span>

                                    @if(!empty($action['hint']))
                                        <span class="ml-3 text-xs text-slate-400">{{ $action['hint'] }}</span>
                                    @endif
                                </button>
                            </form>
                        @else
                            <button
                                type="button"
                                class="{{ $baseClasses }} {{ $toneClasses }}"
                                @if(!empty($action['url']))
                                    onclick="window.location='{{ $action['url'] }}'"
                                @elseif(!empty($action['onclick']))
                                    onclick="{{ $action['onclick'] }}"
                                @endif
                            >
                                <span>{{ $action['label'] }}</span>

                                @if(!empty($action['hint']))
                                    <span class="ml-3 text-xs text-slate-400">{{ $action['hint'] }}</span>
                                @endif
                            </button>
                        @endif
                    @empty
                        <div class="rounded-xl px-3 py-2 text-sm text-slate-400">
                            No actions
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </template>
</div>