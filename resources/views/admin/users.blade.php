@extends('layouts.master')

@section('content')
@php
    $actor = auth()->user();
    $availableRoles = $actor->role === 'superadmin'
        ? ['dosen', 'mahasiswa']
        : ['dosen', 'mahasiswa'];
    $roleLabels = [
        'superadmin' => 'Superadmin',
        'dosen' => 'Dosen',
        'mahasiswa' => 'Mahasiswa',
    ];
    $createHasOldInput = old('identity_id') || old('name') || old('email');
@endphp

<div
    class="min-h-screen"
    x-data="{
        showCreateModal: @js($createHasOldInput),
        editModalOpen: false,
        deleteModalOpen: false,
        editFormAction: '',
        deleteFormAction: '',
        deleteUserName: '',
        editUser: {
            id: null,
            identity_id: '',
            name: '',
            email: '',
            role: 'mahasiswa',
        },
        openEditModal(user) {
            this.editFormAction = user.action;
            this.editUser = {
                id: user.id,
                identity_id: user.identity_id,
                name: user.name,
                email: user.email,
                role: user.role,
            };
            this.editModalOpen = true;
        },
        openDeleteModal(user) {
            this.deleteFormAction = user.action;
            this.deleteUserName = user.name;
            this.deleteModalOpen = true;
        },
    }"
>
    <div class="mx-auto max-w-none px-4 sm:px-6 lg:px-10 py-6 lg:py-8">
        <div class="grid gap-6 lg:gap-8 lg:grid-cols-[280px,1fr]">
            <x-sidebar />

            <main class="space-y-6 fade-in">
                <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-indigo-500">Admin Panel</p>
                        <h1 class="font-display text-3xl sm:text-4xl text-slate-900">User Management</h1>
                    </div>
                </header>

                <x-alert-success />

                @if ($errors->any())
                    <div class="glass rounded-2xl px-5 py-4 border-l-4 border-rose-400 text-rose-700 text-sm font-medium">
                        <p class="font-semibold">Validation failed. Please review the highlighted fields.</p>
                    </div>
                @endif

                <section class="glass rounded-2xl p-6 space-y-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">User Actions</p>
                            <h2 class="font-display text-xl text-slate-900">Create and browse accounts</h2>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-3">
                                <label for="per_page" class="text-sm font-medium text-slate-600">Show</label>
                                <select
                                    id="per_page"
                                    name="per_page"
                                    onchange="this.form.submit()"
                                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                                    @foreach ([10, 50, 100] as $size)
                                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                                    @endforeach
                                </select>
                                <span class="text-sm text-slate-500">entries</span>
                            </form>

                            <button
                                type="button"
                                @click="showCreateModal = true"
                                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                Add User
                            </button>
                        </div>
                    </div>
                </section>

                <section class="glass rounded-2xl p-6">
                    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Users Table</p>
                            <h2 class="font-display text-xl text-slate-900">Registered accounts</h2>
                        </div>
                        <p class="text-sm text-slate-500">Total {{ $users->total() }} users</p>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-100">
                        <table class="min-w-[980px] w-full text-left text-sm">
                            <thead class="bg-slate-50">
                                <tr class="border-b border-slate-200 text-xs uppercase tracking-widest text-slate-400">
                                    <th class="px-4 py-3 font-medium">User</th>
                                    <th class="px-4 py-3 font-medium">Identity ID</th>
                                    <th class="px-4 py-3 font-medium">Role</th>
                                    <th class="px-4 py-3 font-medium">Joined</th>
                                    <th class="px-4 py-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($users as $user)
                                    @php
                                        $canManageUser = $actor->role === 'superadmin' || $user->role !== 'superadmin';
                                    @endphp
                                    <tr class="align-top">
                                        <td class="px-4 py-4">
                                            <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                            <p class="text-slate-500">{{ $user->email }}</p>
                                            @if ($actor->is($user))
                                                <span class="mt-2 inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                                    Logged in
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-slate-600">{{ $user->identity_id }}</td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                                {{ $user->role === 'superadmin' ? 'bg-amber-100 text-amber-700' : ($user->role === 'dosen' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700') }}">
                                                {{ $roleLabels[$user->role] ?? ucfirst($user->role) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-slate-600">{{ $user->created_at->format('d M Y') }}</td>
                                        <td class="px-4 py-4">
                                            @if ($canManageUser)
                                                <div class="flex flex-wrap gap-2">
                                                    <button
                                                        type="button"
                                                        @click="openEditModal({
                                                            id: @js($user->id),
                                                            action: @js(route('admin.users.update', $user)),
                                                            identity_id: @js($user->identity_id),
                                                            name: @js($user->name),
                                                            email: @js($user->email),
                                                            role: @js($user->role),
                                                        })"
                                                        class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                                        Edit
                                                    </button>

                                                    @if (! $actor->is($user))
                                                        <form
                                                            method="POST"
                                                            action="{{ route('admin.users.destroy', $user) }}"
                                                            x-ref="deleteForm{{ $user->id }}"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button
                                                                type="button"
                                                                @click="openDeleteModal({
                                                                    action: @js(route('admin.users.destroy', $user)),
                                                                    name: @js($user->name),
                                                                })"
                                                                class="rounded-xl bg-rose-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-600">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Restricted</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-10 text-center text-slate-500">
                                            No users are available to display.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($users->hasPages())
                        <div class="mt-6">
                            {{ $users->links() }}
                        </div>
                    @endif
                </section>
            </main>
        </div>
    </div>

    <x-user-create-modal
        show="showCreateModal"
        :action="route('admin.users.store')"
        :available-roles="$availableRoles"
        :role-labels="$roleLabels" />

    <x-user-update-modal
        show="editModalOpen"
        action="editFormAction"
        :available-roles="$availableRoles"
        :role-labels="$roleLabels"
        :current-user-id="$actor->id" />

    <form method="POST" :action="deleteFormAction" x-ref="deleteForm">
        @csrf
        @method('DELETE')
    </form>

    <x-modal-delete
        show="deleteModalOpen"
        title="Delete User?"
        message="This will permanently remove the selected account."
        action="$refs.deleteForm.submit()" />
</div>
@endsection
