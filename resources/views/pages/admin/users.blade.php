@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl p-4 md:p-6 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Platform Users</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">All users across companies and roles.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="GET" class="app-card app-card-body grid grid-cols-1 gap-3 md:grid-cols-4">
            <input type="text" name="q" class="input" placeholder="Search users..." value="{{ $filters['search'] ?? '' }}">
            <select name="role" class="input">
                <option value="">All roles</option>
                @foreach(['super_admin','hr_admin','hiring_manager','candidate'] as $role)
                    <option value="{{ $role }}" @selected(($filters['role'] ?? '') === $role)>{{ $role }}</option>
                @endforeach
            </select>
            <select name="status" class="input">
                <option value="">All statuses</option>
                @foreach(['active','inactive'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline">Filter</button>
        </form>

        <form id="bulk-users-form" method="POST" action="{{ route('admin.users.bulk') }}" class="app-card app-card-body grid grid-cols-1 gap-3 md:grid-cols-4">
            @csrf
            @method('PATCH')
            <select name="action" id="bulk-action" class="input">
                <option value="status">Change Status</option>
                <option value="role">Change Role</option>
            </select>
            <select name="status" id="bulk-status" class="input">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select name="role" id="bulk-role" class="input hidden">
                @foreach(['super_admin','hr_admin','hiring_manager','candidate'] as $roleOption)
                    <option value="{{ $roleOption }}">{{ $roleOption }}</option>
                @endforeach
            </select>
            <button id="bulk-submit" class="btn btn-primary" disabled>Apply To Selected</button>
            <p class="md:col-span-4 text-xs text-gray-500 dark:text-gray-400">
                Select users from the table, then apply a bulk action. Safety rules prevent deactivating or demoting the last super admin.
            </p>
        </form>

        <div class="app-card overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 w-10">
                                <input id="check-all-users" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Company</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Roles</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Last Login</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($users as $user)
                            @php
                                $currentRole = $user->roles->pluck('name')->first();
                            @endphp
                            <tr>
                                <td class="px-4 py-3 align-top">
                                    <input form="bulk-users-form" type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                        class="bulk-user-checkbox mt-1 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $user->company->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 uppercase">{{ $user->status ?? 'active' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-2 items-end">
                                        <form method="POST" action="{{ route('admin.users.status', $user) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="input h-9 py-1 text-xs min-w-[120px]">
                                                @foreach(['active', 'inactive'] as $statusValue)
                                                    <option value="{{ $statusValue }}" @selected(($user->status ?? 'active') === $statusValue)>
                                                        {{ ucfirst($statusValue) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-outline btn-xs">Save</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <select name="role" class="input h-9 py-1 text-xs min-w-[140px]">
                                                @foreach(['super_admin','hr_admin','hiring_manager','candidate'] as $roleOption)
                                                    <option value="{{ $roleOption }}" @selected($currentRole === $roleOption)>
                                                        {{ $roleOption }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-outline btn-xs">Save</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $users->links() }}</div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('check-all-users');
    const checkboxes = Array.from(document.querySelectorAll('.bulk-user-checkbox'));
    const bulkSubmit = document.getElementById('bulk-submit');
    const actionSelect = document.getElementById('bulk-action');
    const statusSelect = document.getElementById('bulk-status');
    const roleSelect = document.getElementById('bulk-role');

    const updateBulkState = () => {
        const selectedCount = checkboxes.filter(cb => cb.checked).length;
        bulkSubmit.disabled = selectedCount === 0;
        bulkSubmit.textContent = selectedCount > 0 ? `Apply To Selected (${selectedCount})` : 'Apply To Selected';
    };

    const syncActionFields = () => {
        const action = actionSelect.value;
        if (action === 'role') {
            statusSelect.classList.add('hidden');
            roleSelect.classList.remove('hidden');
        } else {
            roleSelect.classList.add('hidden');
            statusSelect.classList.remove('hidden');
        }
    };

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
            updateBulkState();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            if (!cb.checked && selectAll) {
                selectAll.checked = false;
            } else if (selectAll && checkboxes.every(x => x.checked)) {
                selectAll.checked = true;
            }
            updateBulkState();
        });
    });

    actionSelect.addEventListener('change', syncActionFields);
    syncActionFields();
    updateBulkState();
});
</script>
@endpush
