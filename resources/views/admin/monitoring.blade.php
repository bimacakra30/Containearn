@extends('layouts.master')

@section('content')
<div class="app-shell">
    <div class="app-grid">
            <x-sidebar />

            <main class="app-main fade-in">
                <x-app-header />
                <section class="glass p-6" x-data="containerLogModal()">
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
                                    <th class="px-4 py-3 font-medium text-right">Actions</th>
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
                                        <td class="px-4 py-4 text-right">
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center rounded-[12px] border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                                x-on:click="openLog(@js(route('admin.monitoring.logs', ['containerName' => $container['name']])), @js($container['name']))"
                                            >
                                                Log
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-10 text-center text-slate-500">
                                            No containers are currently active
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div
                        x-cloak
                        x-show="isOpen"
                        class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6"
                        role="dialog"
                        aria-modal="true"
                        x-on:keydown.escape.window="closeLog()"
                    >
                        <div
                            class="absolute inset-0 bg-slate-950/45"
                            x-on:click="closeLog()"
                            x-transition.opacity
                        ></div>

                        <div
                            class="relative z-10 flex max-h-[82vh] w-full max-w-4xl flex-col overflow-hidden rounded-[18px] border border-slate-200 bg-white shadow-xl shadow-slate-950/10"
                            x-transition
                        >
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                                <div class="min-w-0">
                                    <p class="eyebrow">Container Log</p>
                                    <h3 class="mt-2 truncate font-display text-lg tracking-[-0.03em] text-slate-950" x-text="activeContainer"></h3>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-[12px] border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-950"
                                    x-on:click="closeLog()"
                                    aria-label="Close log modal"
                                >
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="min-h-0 flex-1 bg-slate-950 p-4">
                                <div x-show="isLoading" class="rounded-[14px] border border-slate-800 bg-slate-900 px-4 py-10 text-center text-sm font-medium text-slate-300">
                                    Loading logs...
                                </div>
                                <pre
                                    x-show="!isLoading"
                                    class="max-h-[58vh] min-h-[18rem] overflow-auto whitespace-pre-wrap rounded-[14px] border border-slate-800 bg-slate-900 p-4 font-mono text-xs leading-6 text-slate-100"
                                    x-text="logText"
                                ></pre>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function containerLogModal() {
        return {
            isOpen: false,
            isLoading: false,
            activeContainer: '',
            logText: '',
            async openLog(url, containerName) {
                this.isOpen = true;
                this.isLoading = true;
                this.activeContainer = containerName;
                this.logText = '';

                try {
                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.logs || 'Unable to load container logs.');
                    }

                    this.logText = data.logs;
                } catch (error) {
                    this.logText = error.message || 'Unable to load container logs.';
                } finally {
                    this.isLoading = false;
                }
            },
            closeLog() {
                this.isOpen = false;
            },
        };
    }
</script>
@endpush
