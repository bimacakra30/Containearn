@extends('layouts.master')

@section('content')
<div class="min-h-screen">
    <div class="mx-auto max-w-none px-4 sm:px-6 lg:px-10 py-6 lg:py-8">
        <div class="grid gap-6 lg:gap-8 lg:grid-cols-[280px,1fr]">

            <aside class="glass rounded-2xl p-5 sticky top-6 h-fit fade-in">
                <div class="flex items-center gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Containearn</p>
                        <p class="font-display text-lg text-slate-1000">INTERACTIVE</p>
                    </div>
                </div>
                <nav class="mt-6 space-y-2 text-sm">
                    <a href="{{ route('admin.dashboard') }}"
                        class="flex items-center gap-2 rounded-xl border px-3 py-2 transition
            {{ request()->routeIs('admin.dashboard')
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 font-semibold'
                : 'border-slate-200 text-slate-700 hover:border-emerald-200 hover:bg-emerald-50' }}">
                        Dashboard
                    </a>
                    <a href="#"
                        class="flex items-center gap-2 rounded-xl border px-3 py-2 transition
            {{ request()->routeIs('#')
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 font-semibold'
                : 'border-slate-200 text-slate-700 hover:border-emerald-200 hover:bg-emerald-50' }}">
                        Profile
                    </a>
                    <a href="#"
                        class="flex items-center gap-2 rounded-xl border px-3 py-2 transition
            {{ request()->routeIs('#')
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 font-semibold'
                : 'border-slate-200 text-slate-700 hover:border-emerald-200 hover:bg-emerald-50' }}">
                        Manage Users
                    </a>
                    <a href="#"
                        class="flex items-center gap-2 rounded-xl border px-3 py-2 transition
            {{ request()->routeIs('#')
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 font-semibold'
                : 'border-slate-200 text-slate-700 hover:border-emerald-200 hover:bg-emerald-50' }}">
                        Manage Module
                    </a>
                    <a href="#"
                        class="flex items-center gap-2 rounded-xl border px-3 py-2 transition
            {{ request()->routeIs('#')
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 font-semibold'
                : 'border-slate-200 text-slate-700 hover:border-emerald-200 hover:bg-emerald-50' }}">
                        Monitoring
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-slate-700 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600 transition text-sm">
                            Logout
                        </button>
                    </form>
                </nav>
            </aside>

            <main class="space-y-6 fade-in">
                <header>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-emerald-600">Dashboard</p>
                    <h1 class="font-display text-3xl sm:text-4xl text-slate-900">
                        Welcome!, {{ auth()->user()->name }}
                    </h1>
                </header>
                <div class="glass rounded-2xl p-6">
                    <p class="text-slate-600">Ehehehe</p>
                </div>
            </main>

        </div>
    </div>
</div>
@endsection