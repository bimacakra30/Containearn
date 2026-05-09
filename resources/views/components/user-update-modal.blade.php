@props([
    'show' => 'false',
    'action' => "''",
    'availableRoles' => [],
    'roleLabels' => [],
    'currentUserId' => null,
])

<div
    x-show="{{ $show }}"
    x-cloak
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6"
>
    <div
        class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
        @click="{{ $show }} = false"
    ></div>

    <div
        x-show="{{ $show }}"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="glass relative z-10 w-full max-w-lg p-6 shadow-2xl"
    >
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <p class="eyebrow">Edit User</p>
                <h3 class="font-display text-xl text-slate-900">Update account details</h3>
            </div>

            <button
                type="button"
                @click="{{ $show }} = false"
                class="btn-secondary px-3 py-2 text-xs">
                Close
            </button>
        </div>

        <form method="POST" :action="{{ $action }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="form-label">Identity ID</label>
                    <input
                        type="text"
                        name="identity_id"
                        x-model="editUser.identity_id"
                        class="form-input">
                </div>

                <div>
                    <label class="form-label">Name</label>
                    <input
                        type="text"
                        name="name"
                        x-model="editUser.name"
                        class="form-input">
                </div>

                <div>
                    <label class="form-label">Email</label>
                    <input
                        type="email"
                        name="email"
                        x-model="editUser.email"
                        class="form-input">
                </div>

                <div>
                    <label class="form-label">Role</label>
                    <select
                        name="role"
                        x-model="editUser.role"
                        :disabled="editUser.id === {{ \Illuminate\Support\Js::from($currentUserId) }}"
                        class="form-input">
                        @foreach ($availableRoles as $role)
                            <option value="{{ $role }}">
                                {{ $roleLabels[$role] ?? ucfirst($role) }}
                            </option>
                        @endforeach
                    </select>
                    <p
                        x-show="editUser.id === {{ \Illuminate\Support\Js::from($currentUserId) }}"
                        x-cloak
                        class="mt-1 text-xs text-slate-500"
                    >
                    </p>
                </div>

                <div>
                    <label class="form-label">New Password (Optional)</label>
                    <input
                        type="password"
                        name="password"
                        class="form-input">
                </div>

                <div>
                    <label class="form-label">Confirm Password</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-input">
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button
                    type="button"
                    @click="{{ $show }} = false"
                    class="btn-secondary px-5 py-2.5">
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-primary px-5 py-2.5">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
