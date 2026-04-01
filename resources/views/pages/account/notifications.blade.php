@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-6 p-4 md:p-6" x-data="notificationsPage()" x-init="init()">
    @php
        $activeCategory = $category ?? 'all';
        $counts = $categoryCounts ?? ['all' => 0, 'applications' => 0, 'interviews' => 0, 'system' => 0];
    @endphp

    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Central inbox for application and interview updates.</p>
        </div>
        <button
            @click="markAllAsRead()"
            class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
            :disabled="processing || unreadCount < 1"
        >
            Mark all as read (<span class="ml-1" x-text="unreadCount"></span>)
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-white/[0.03]">
        @foreach([
            'all' => 'All',
            'applications' => 'Applications',
            'interviews' => 'Interviews',
            'system' => 'System',
        ] as $key => $label)
            <a
                href="{{ route('notifications.index', ['category' => $key]) }}"
                class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wide transition {{ $activeCategory === $key ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10' }}"
            >
                {{ $label }}
                <span class="rounded-full bg-black/10 px-2 py-0.5 text-[11px] {{ $activeCategory === $key ? 'bg-white/20' : '' }}">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div
        x-show="liveUpdateAvailable"
        x-transition.opacity.duration.150ms
        class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-700 dark:border-brand-700/30 dark:bg-brand-500/10 dark:text-brand-300"
    >
        New notifications arrived in real time.
        <button @click="refreshPage()" class="ml-2 font-semibold underline">Refresh list</button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
        @forelse($notifications as $item)
            <div class="flex items-start gap-3 border-b border-gray-100 px-4 py-4 last:border-b-0 dark:border-gray-800" id="notification-row-{{ $item['id'] }}">
                <div class="mt-1 h-2.5 w-2.5 rounded-full {{ $item['read_at'] ? 'bg-gray-300 dark:bg-gray-600' : 'bg-brand-500' }}"></div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item['title'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item['time'] }}</p>
                    </div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $item['message'] }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <a href="{{ $item['url'] }}" class="inline-flex h-8 items-center rounded-lg bg-brand-600 px-3 text-xs font-semibold text-white hover:bg-brand-700">
                            Open
                        </a>
                        @if(!$item['read_at'])
                            <button
                                @click="markRead('{{ $item['id'] }}')"
                                class="inline-flex h-8 items-center rounded-lg border border-gray-300 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                            >
                                Mark read
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="px-4 py-14 text-center text-sm text-gray-500 dark:text-gray-400">
                No notifications yet.
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div>{{ $notifications->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function notificationsPage() {
    return {
        unreadCount: @json($unreadCount ?? 0),
        processing: false,
        liveUpdateAvailable: false,
        init() {
            window.addEventListener('novahire:notification-received', () => {
                this.liveUpdateAvailable = true;
                this.unreadCount += 1;
            });
        },
        async markRead(id) {
            if (this.processing) return;
            this.processing = true;
            await this.post('{{ url('/notifications') }}/' + id + '/read');
            const row = document.getElementById('notification-row-' + id);
            if (row) {
                const dot = row.querySelector('.rounded-full');
                if (dot) {
                    dot.classList.remove('bg-brand-500');
                    dot.classList.add('bg-gray-300', 'dark:bg-gray-600');
                }
                const btn = row.querySelector('button');
                if (btn) btn.remove();
            }
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            this.liveUpdateAvailable = false;
            this.processing = false;
        },
        async markAllAsRead() {
            if (this.processing || this.unreadCount < 1) return;
            this.processing = true;
            await this.post('{{ route('notifications.read-all') }}');
            this.unreadCount = 0;
            this.liveUpdateAvailable = false;
            this.processing = false;
            window.location.reload();
        },
        refreshPage() {
            window.location.reload();
        },
        async post(url) {
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const token = tokenMeta ? tokenMeta.getAttribute('content') : '';
            await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({})
            });
        }
    }
}
</script>
@endpush
