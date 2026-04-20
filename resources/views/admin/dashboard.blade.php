@extends('layouts.master')

@section('content')
<div class="min-h-screen">
    <div class="mx-auto max-w-none px-4 sm:px-6 lg:px-10 py-6 lg:py-8">
        <div class="grid gap-6 lg:gap-8 lg:grid-cols-[280px,1fr]">
            <x-sidebar />

            <main class="space-y-6 fade-in">
                <header class="glass rounded-[28px] p-6 sm:p-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-indigo-500">Dashboard</p>
                    <h1 class="mt-3 font-display text-3xl sm:text-4xl text-slate-900">Welcome, {{ auth()->user()->name }}</h1>
                    <p class="mt-4 max-w-3xl text-sm sm:text-base leading-7 text-slate-600">
                        This dashboard gives you a quick overview of the main sections available.
                    </p>
                </header>

                <section class="glass rounded-2xl p-6 sm:p-7">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Navigation Guide</p>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Account</p>
                            <h3 class="mt-3 font-display text-2xl text-slate-900">Profile</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                Review and manage your personal account information in the platform.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Users</p>
                            <h3 class="mt-3 font-display text-2xl text-slate-900">Users Management</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                Organize user accounts and keep access roles aligned with the practicum environment.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Contents</p>
                            <h3 class="mt-3 font-display text-2xl text-slate-900">Practicum Contents</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                Maintain the learning materials used to support practicum sessions.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Runtime</p>
                            <h3 class="mt-3 font-display text-2xl text-slate-900">Monitoring</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                Check the current container environment and review active Docker processes.
                            </p>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</div>
@endsection
