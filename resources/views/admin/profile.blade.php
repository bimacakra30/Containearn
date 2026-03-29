@extends('layouts.master')

@section('content')
<div x-data="{ showConfirm: false, showDelete: false }">
    <div class="min-h-screen">
        <div class="mx-auto max-w-none px-4 sm:px-6 lg:px-10 py-6 lg:py-8">
            <div class="grid gap-6 lg:gap-8 lg:grid-cols-[280px,1fr]">
                <x-sidebar />

                <main class="space-y-6 fade-in">
                    <header>
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-indigo-500">My Account</p>
                        <h1 class="font-display text-3xl sm:text-4xl text-slate-900">Profile Details</h1>
                    </header>

                    <x-alert-success />

                    <div class="glass rounded-2xl p-6 space-y-4">
                        <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
                            <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-display text-xl font-bold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                <p class="text-sm text-slate-500">{{ ucfirst(auth()->user()->role) }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                <p class="text-xs uppercase tracking-widest text-slate-400 mb-1">Name</p>
                                <p class="font-medium text-slate-800">{{ auth()->user()->name }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                <p class="text-xs uppercase tracking-widest text-slate-400 mb-1">Identity ID</p>
                                <p class="font-medium text-slate-800">{{ auth()->user()->identity_id ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                <p class="text-xs uppercase tracking-widest text-slate-400 mb-1">Email</p>
                                <p class="font-medium text-slate-800">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                <p class="text-xs uppercase tracking-widest text-slate-400 mb-1">Role</p>
                                <p class="font-medium text-slate-800">{{ ucfirst(auth()->user()->role) }}</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                <p class="text-xs uppercase tracking-widest text-slate-400 mb-1">Member Since</p>
                                <p class="font-medium text-slate-800">{{ auth()->user()->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass rounded-2xl p-6">
                        <h2 class="font-display text-lg text-slate-900 mb-5">Edit Profile</h2>
                        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4"
                            @submit.prevent="showConfirm = true" x-ref="editForm">
                            @csrf
                            @method('PATCH')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-1.5">Name</label>
                                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                                    @error('name')
                                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-1.5">Identity ID</label>
                                    <input type="text" name="identity_id" value="{{ old('identity_id', auth()->user()->identity_id) }}"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                                    @error('identity_id')
                                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-1.5">Email</label>
                                    <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                                    @error('email')
                                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-1.5">
                                        New Password <span class="normal-case text-slate-400">(optional)</span>
                                    </label>
                                    <input type="password" name="password"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                                    @error('password')
                                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs uppercase tracking-widest text-slate-500 mb-1.5">Confirm Password</label>
                                    <input type="password" name="password_confirmation"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                                </div>
                            </div>
                            <div class="pt-2">
                                <button type="submit"
                                    class="rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 text-sm font-semibold transition">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    @if(auth()->user()->role !== 'superadmin')
                    <div class="glass rounded-2xl p-6 border border-rose-100">
                        <h2 class="font-display text-lg text-rose-600 mb-1">Delete Account</h2>
                        <p class="text-sm text-slate-500 mb-5">Once deleted, your account cannot be recovered.</p>
                        <form method="POST" action="{{ route('profile.destroy') }}" x-ref="deleteForm">
                            @csrf
                            @method('DELETE')
                            <div class="flex flex-col sm:flex-row gap-3">
                                <div class="flex-1">
                                    <input type="password" name="password" placeholder="Enter your password to confirm"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 outline-none focus:border-rose-400 focus:ring-2 focus:ring-rose-100 transition">
                                    @error('password')
                                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="button" @click="showDelete = true"
                                    class="rounded-xl bg-rose-500 hover:bg-rose-600 text-white px-6 py-2.5 text-sm font-semibold transition">
                                    Delete Account
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif

                </main>
            </div>
        </div>
    </div>

    <x-modal-confirm
        show="showConfirm"
        title="Save Changes?"
        message="Your profile will be updated."
        action="$refs.editForm.submit()" />

    <x-modal-confirm
        show="showConfirm"
        title="Save Changes?"
        message="Your profile will be updated."
        action="$refs.editForm.submit()" />

</div>
@endsection