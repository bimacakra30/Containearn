@extends('layouts.master')

@section('content')
<div class="min-h-screen">
    <div class="mx-auto max-w-none px-4 sm:px-6 lg:px-10 py-6 lg:py-8">
        <div class="grid gap-6 lg:gap-8 lg:grid-cols-[280px,1fr]">
            <x-sidebar />
            <main class="space-y-6 fade-in">
                <header>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-indigo-500">Dashboard</p>
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