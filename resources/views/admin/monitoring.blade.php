@extends('layouts.master')

@section('content')
<div class="app-shell">
    <div class="app-grid">
            <x-sidebar />

            <main class="app-main fade-in">
                <x-app-header />
                <header class="glass overflow-hidden p-7 sm:p-8 lg:p-10">
                    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr),320px]">
                        <div>
                            <p class="eyebrow">Admin Panel</p>
                            <h1 class="page-title">Monitoring</h1>
                        </div>
                    </div>
                </header>

                <section class="glass p-6">
                    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="eyebrow">Runtime Table</p>
                            <h2 class="mt-3 font-display text-2xl tracking-[-0.04em] text-slate-900">Running containers</h2>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-[24px] border border-slate-200">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-50/90">
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
