@extends('layouts.master')

@section('content')
@php
    $userCount = \App\Models\User::count();
    $courseCount = \App\Models\Course::count();
    $moduleCount = \App\Models\Module::count();
@endphp
<div class="app-shell">
    <div class="app-grid">
            <x-sidebar />

            <main class="app-main fade-in">
                <x-app-header />
                <header class="glass overflow-hidden p-7 sm:p-8 lg:p-10">
                    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr),320px]">
                        <div>
                            <p class="eyebrow">Dashboard</p>
                            <h1 class="page-title">Welcome, {{ auth()->user()->name }}</h1>
                            <p class="page-description">This dashboard gives you a quick overview of the main sections available.</p>
                        </div>
                    </div>
                </header>

                <section class="glass p-6 sm:p-7">
                    <p class="eyebrow">Navigation Guide</p>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div class="surface-muted p-5">
                            <p class="eyebrow">Account</p>
                            <h3 class="mt-3 font-display text-2xl tracking-[-0.04em] text-slate-900">Profile</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                Review and manage your personal account information in the platform.
                            </p>
                        </div>

                        <div class="surface-muted p-5">
                            <p class="eyebrow">Users</p>
                            <h3 class="mt-3 font-display text-2xl tracking-[-0.04em] text-slate-900">Users Management</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                Organize user accounts and keep access roles aligned with the practicum environment.
                            </p>
                        </div>

                        <div class="surface-muted p-5">
                            <p class="eyebrow">Contents</p>
                            <h3 class="mt-3 font-display text-2xl tracking-[-0.04em] text-slate-900">Practicum Contents</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                Maintain the learning materials used to support practicum sessions.
                            </p>
                        </div>

                        <div class="surface-muted p-5">
                            <p class="eyebrow">Runtime</p>
                            <h3 class="mt-3 font-display text-2xl tracking-[-0.04em] text-slate-900">Monitoring</h3>
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
