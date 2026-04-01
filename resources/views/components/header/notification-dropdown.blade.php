<div
    class="relative"
    x-data="notificationDropdown()"
    x-init="init()"
    @click.away="closeDropdown()"
>
    <button
        class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        @click="toggleDropdown()"
        type="button"
        aria-label="Notifications"
    >
        <template x-if="unreadCount > 0">
            <span class="absolute right-0 top-0.5 z-10 inline-flex min-w-4 items-center justify-center rounded-full bg-orange-500 px-1 text-[10px] font-semibold leading-4 text-white" x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
        </template>

        <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z" />
        </svg>
    </button>

    <div
        x-show="dropdownOpen"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute -right-[240px] mt-[17px] flex h-[480px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark sm:w-[361px] lg:right-0"
        style="display:none;"
    >
        <div class="mb-3 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
            <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">Notifications</h5>
            <div class="flex items-center gap-2">
                <button
                    x-show="unreadCount > 0"
                    @click.prevent="markAllAsRead()"
                    class="text-xs font-semibold text-brand-600 hover:text-brand-700"
                    type="button"
                >Mark all read</button>
                <button @click="closeDropdown()" class="text-gray-500 dark:text-gray-400" type="button">
                    <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="max-h-[390px] flex-1 space-y-2 overflow-y-auto pr-1">
            <template x-if="loading">
                <div class="rounded-lg border border-gray-100 px-3 py-4 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    Loading notifications...
                </div>
            </template>

            <template x-if="!loading && notifications.length === 0">
                <div class="flex h-48 flex-col items-center justify-center py-8 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
                                fill="currentColor"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">No notifications yet</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Updates will appear here automatically.</p>
                </div>
            </template>

            <template x-for="item in notifications" :key="item.id">
                <a
                    :href="item.url"
                    @click="openNotification(item)"
                    class="block rounded-xl border px-3 py-3 transition hover:bg-gray-50 dark:hover:bg-white/[0.04]"
                    :class="item.read_at ? 'border-gray-100 dark:border-gray-800' : 'border-brand-200 bg-brand-50/40 dark:border-brand-800/40 dark:bg-brand-500/10'"
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="item.title"></p>
                        <span x-show="!item.read_at" class="mt-1 h-2 w-2 rounded-full bg-brand-500"></span>
                    </div>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300" x-text="item.message"></p>
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400" x-text="item.time"></p>
                </a>
            </template>
        </div>

        <a
            href="{{ route('notifications.index') }}"
            class="mt-3 inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
            @click="closeDropdown()"
        >
            View All Notifications
        </a>
    </div>
</div>

<script>
    function notificationDropdown() {
        return {
            dropdownOpen: false,
            loading: true,
            unreadCount: 0,
            notifications: [],
            pollHandle: null,
            init() {
                this.fetchFeed();
                this.pollHandle = setInterval(() => this.fetchFeed(), 10000);
                this.initRealtime();
            },
            toggleDropdown() {
                this.dropdownOpen = !this.dropdownOpen;
                if (this.dropdownOpen) {
                    this.fetchFeed();
                }
            },
            closeDropdown() {
                this.dropdownOpen = false;
            },
            async fetchFeed() {
                try {
                    const response = await fetch('{{ route('notifications.feed') }}', {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });
                    if (!response.ok) return;
                    const payload = await response.json();
                    this.unreadCount = payload.unread_count || 0;
                    this.notifications = payload.notifications || [];
                } finally {
                    this.loading = false;
                }
            },
            async markAllAsRead() {
                await this.post('{{ route('notifications.read-all') }}');
                await this.fetchFeed();
            },
            async openNotification(item) {
                if (!item.read_at) {
                    await this.post('{{ url('/notifications') }}/' + item.id + '/read');
                }
                this.closeDropdown();
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
            },
            initRealtime() {
                const userId = @json(auth()->id());
                if (!userId || !window.Echo) return;

                try {
                    window.Echo.private(`App.Models.User.${userId}`)
                        .notification((notification) => {
                            this.fetchFeed();
                            this.emitIncomingNotification(notification);
                        });
                } catch (e) {
                    // Keep polling fallback if websocket is unavailable.
                }
            },
            emitIncomingNotification(notification) {
                try {
                    window.dispatchEvent(new CustomEvent('novahire:notification-received', {
                        detail: notification || {},
                    }));
                } catch (e) {
                    // No-op if CustomEvent dispatch is unavailable.
                }

                const Alpine = window.Alpine;
                if (!Alpine || typeof Alpine.store !== 'function') {
                    return;
                }

                const toast = Alpine.store('toast');
                if (!toast || typeof toast.show !== 'function') {
                    return;
                }

                const payload = this.describeNotification(notification);
                if (!payload.message) {
                    return;
                }

                toast.show(payload.message, {
                    duration: 4200,
                    type: payload.type,
                });
            },
            describeNotification(notification) {
                const type = String(notification?.type || '').toLowerCase();
                const job = String(notification?.job_title || 'your application');

                if (type.includes('applicationstatuschanged')) {
                    const status = String(notification?.status || 'updated');
                    const note = String(notification?.note_excerpt || '').trim();
                    return {
                        type: status === 'rejected' ? 'error' : 'success',
                        message: note !== ''
                            ? `${job} moved to ${status}. ${note}`
                            : `${job} moved to ${status}.`,
                    };
                }

                if (type.includes('interviewscheduled')) {
                    return {
                        type: 'success',
                        message: `Interview scheduled for ${job}.`,
                    };
                }

                if (type.includes('interviewcancelled')) {
                    return {
                        type: 'error',
                        message: `Interview cancelled for ${job}.`,
                    };
                }

                if (type.includes('interviewreminder')) {
                    return {
                        type: 'success',
                        message: `Interview reminder received for ${job}.`,
                    };
                }

                return {
                    type: 'success',
                    message: 'You have a new update.',
                };
            },
        }
    }
</script>
