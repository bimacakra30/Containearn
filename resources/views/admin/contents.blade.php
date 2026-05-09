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
                            <p class="eyebrow">Practicum Panel</p>
                            <h1 class="page-title">Practicum Contents</h1>
                        </div>
                    </div>
                </header>

                <section class="glass p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="eyebrow">Content Structure</p>
                            <h2 class="mt-3 font-display text-2xl tracking-[-0.04em] text-slate-900">Courses, modules, and questions</h2>
                        </div>
                    </div>
                </section>

                <section class="space-y-6">
                    @forelse ($courses as $course)
                        <div
                            x-data="{ courseOpen: false }"
                            class="glass p-6 space-y-5"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex items-center gap-3">
                                        <span class="chip bg-blue-50 text-blue-700 border-blue-100">
                                            Course #{{ $course->id_course }}
                                        </span>
                                        <span class="chip">
                                            {{ $course->modules_count }} modules
                                        </span>
                                    </div>
                                    <h3 class="mt-4 font-display text-3xl tracking-[-0.04em] text-slate-900">{{ $course->course_title }}</h3>
                                    <p class="mt-2 text-sm text-slate-500">Docker image: <span class="font-medium text-slate-700">{{ $course->docker_image }}</span></p>
                                </div>

                                <div class="flex flex-col items-end gap-3">
                                    <button
                                        type="button"
                                        @click="courseOpen = !courseOpen"
                                        class="btn-secondary px-4 py-2 text-xs uppercase tracking-[0.22em]"
                                    >
                                        <span x-text="courseOpen ? 'Hide Details' : 'Show Details'"></span>
                                    </button>
                                </div>
                            </div>

                            <div
                                x-show="courseOpen"
                                x-cloak
                                x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-2"
                                class="space-y-4"
                            >
                                @forelse ($course->modules as $module)
                                    <div
                                        x-data="{ open: false }"
                                        class="surface-muted"
                                    >
                                        <button
                                            type="button"
                                            @click="open = !open"
                                            class="flex w-full items-start justify-between gap-4 px-5 py-4 text-left"
                                        >
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="chip bg-blue-50 text-blue-700 border-blue-100">
                                                        Module #{{ $module->id_module }}
                                                    </span>
                                                    <span class="chip bg-emerald-50 text-emerald-700 border-emerald-100">
                                                        {{ $module->questions->count() }} questions
                                                    </span>
                                                </div>
                                                <h4 class="mt-3 text-lg font-semibold text-slate-900">{{ $module->title }}</h4>
                                                <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ $module->description }}</p>
                                            </div>

                                            <div class="flex flex-col items-end gap-3">
                                                <span class="chip bg-amber-50 text-amber-700 border-amber-100">
                                                    {{ $module->time_limit }} minutes
                                                </span>
                                                <span
                                                    class="text-xs font-semibold uppercase tracking-widest text-slate-400"
                                                    x-text="open ? 'Hide' : 'Show'"
                                                ></span>
                                            </div>
                                        </button>

                                        <div
                                            x-show="open"
                                            x-cloak
                                            x-transition:enter="ease-out duration-200"
                                            x-transition:enter-start="opacity-0 -translate-y-2"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            x-transition:leave="ease-in duration-150"
                                            x-transition:leave-start="opacity-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 -translate-y-2"
                                            class="border-t border-slate-200 px-5 py-5"
                                        >
                                            <div class="space-y-3">
                                                @forelse ($module->questions as $question)
                                                    <div class="surface-subtle px-4 py-4">
                                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                            <div class="space-y-2">
                                                                <span class="inline-flex rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">
                                                                    Question #{{ $loop->iteration }}
                                                                </span>
                                                                <p class="text-sm font-medium leading-6 text-slate-800">{{ $question->question }}</p>
                                                            </div>

                                                            <div class="lg:max-w-sm lg:text-right">
                                                                <p class="eyebrow">Expected Output</p>
                                                                <p class="mt-2 rounded-2xl bg-white px-3 py-2 font-mono text-sm text-slate-700 border border-slate-200">
                                                                    {{ $question->output }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500">
                                                        No questions have been added to this module yet.
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-200 px-5 py-8 text-sm text-slate-500">
                                        No modules are available for this course yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <div class="glass rounded-2xl px-6 py-10 text-center text-slate-500">
                            No practicum contents are available yet.
                        </div>
                    @endforelse
                </section>
            </main>
        </div>
    </div>
</div>
@endsection
