@extends('layouts.master')

@section('content')
<div>
    <div class="app-shell">
        <div class="app-grid">
                <x-sidebar />

                <main class="app-main fade-in">
                    <x-app-header />
                    <div class="glass p-7 space-y-5">
                        <div class="flex items-center gap-4 border-b border-slate-200 pb-5">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-950 text-lg font-bold text-white">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-lg font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                <p class="text-sm text-slate-500">{{ ucfirst(auth()->user()->role) }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 xl:grid-cols-3">
                            <div class="surface-muted px-4 py-4">
                                <p class="eyebrow mb-2">Name</p>
                                <p class="font-medium text-slate-800">{{ auth()->user()->name }}</p>
                            </div>
                            <div class="surface-muted px-4 py-4">
                                <p class="eyebrow mb-2">Identity ID</p>
                                <p class="font-medium text-slate-800">{{ auth()->user()->identity_id ?? '—' }}</p>
                            </div>
                            <div class="surface-muted px-4 py-4">
                                <p class="eyebrow mb-2">Email</p>
                                <p class="font-medium text-slate-800">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="surface-muted px-4 py-4">
                                <p class="eyebrow mb-2">Role</p>
                                <p class="font-medium text-slate-800">{{ ucfirst(auth()->user()->role) }}</p>
                            </div>
                            <div class="surface-muted px-4 py-4">
                                <p class="eyebrow mb-2">Class</p>
                                <p class="font-medium text-slate-800">{{ auth()->user()->getAttribute('class') ?? '—' }}</p>
                            </div>
                            <div class="surface-muted px-4 py-4">
                                <p class="eyebrow mb-2">Member Since</p>
                                <p class="font-medium text-slate-800">{{ auth()->user()->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="glass p-7">
                        <div class="mb-6">
                            <p class="eyebrow">Edit Profile</p>
                            <h2 class="mt-3 font-display text-2xl tracking-[-0.04em] text-slate-950">Update Profile</h2>
                        </div>
                        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                            @csrf
                            @method('PATCH')
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}"
                                        class="form-input">
                                    @error('name')
                                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label">Identity ID</label>
                                    <input type="text" name="identity_id" value="{{ old('identity_id', auth()->user()->identity_id) }}"
                                        class="form-input">
                                    @error('identity_id')
                                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                                        class="form-input">
                                    @error('email')
                                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label">Class</label>
                                    <select name="class" class="form-input">
                                        <option value="">Select class</option>
                                        @foreach (['A', 'B', 'C', 'D'] as $class)
                                            <option value="{{ $class }}" @selected(old('class', auth()->user()->getAttribute('class')) === $class)>
                                                {{ $class }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('class')
                                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label">
                                        New Password <span class="normal-case text-slate-400">(optional)</span>
                                    </label>
                                    <input type="password" name="password"
                                        class="form-input">
                                    @error('password')
                                    <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="password_confirmation"
                                        class="form-input">
                                </div>
                            </div>
                            <div class="pt-2">
                                <button type="submit" class="btn-primary">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </main>
            </div>
        </div>
    </div>

</div>
@endsection
