@extends('layouts.master')

@section('content')
<div class="min-h-screen">
    <div class="mx-auto max-w-none px-4 sm:px-6 lg:px-10 py-6 lg:py-8">
        <div class="grid gap-6 lg:gap-8 lg:grid-cols-[280px,1fr]">
            <x-sidebar />

            <main class="space-y-6 fade-in">
                <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-indigo-500">Admin Panel</p>
                        <h1 class="font-display text-3xl sm:text-4xl text-slate-900">Monitoring</h1>
                    </div>
                </header>

                <section class="grid gap-4 sm:grid-cols-10">
                    <div class="glass rounded-2xl p-5">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Active</p>
                        <p class="mt-3 font-display text-3xl text-slate-900">{{ count($containers) }}</p>
                    </div>
                </section>
                <section class="glass rounded-2xl p-6">
                    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="font-display text-xl text-slate-900">Running containers</h2>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-100">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-50">
                                <tr class="border-b border-slate-200 text-xs uppercase tracking-widest text-slate-400">
                                    <th class="px-4 py-3 font-medium">Names</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Image</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($containers as $container)
                                    @php
                                        $status = strtolower($container['status']);
                                        $statusClasses = str_contains($status, 'up')
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-slate-100 text-slate-700';
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-4 font-semibold text-slate-900">{{ $container['name'] }}</td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                                {{ $container['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 font-mono text-sm text-slate-600">{{ $container['image'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-10 text-center text-slate-500">
                                            No containers are currently active
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
</div>
@endsection
