@extends('layouts.master')

@section('content')
@php
    $courseCount = \App\Models\Course::count();
    $moduleCount = \App\Models\Module::count();
@endphp
<div class="app-shell">
    <div class="app-grid">
            <x-sidebar />

            <main class="app-main fade-in">
                <x-app-header />
                <header class="glass p-8 lg:p-10">
                    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr),280px] lg:items-start">
                        <div>
                            <p class="eyebrow">Dashboard</p>
                            <h1 class="page-title">Welcome, {{ auth()->user()->name }}</h1>
                            <p class="page-description">This dashboard gives you a quick overview of the main sections available.</p>
                        </div>
                    </div>
                </header>

                <section class="glass p-7 sm:p-8">
                    <p class="eyebrow">Navigation</p>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <a href="{{ route('mahasiswa.content.index') }}" class="surface-muted block p-6 transition hover:border-slate-300">
                            <h3 class="font-display text-2xl tracking-[-0.04em] text-slate-950">Practicum Content</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">Access the practicum modules and continue your learning activities in the platform.</p>
                        </a>
                        <a href="{{ route('mahasiswa.profile') }}" class="surface-muted block p-6 transition hover:border-slate-300">
                            <h3 class="font-display text-2xl tracking-[-0.04em] text-slate-950">Profile</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">Review and manage your personal account information in the platform.</p>
                        </a>
                    </div>
                </section>
            </main>
        </div>
    </div>
</div>
@endsection
