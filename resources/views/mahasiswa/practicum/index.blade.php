@extends('layouts.master')

@section('content')
@php
    $totalCourses = $courses->count();
    $totalModules = $courses->sum(fn ($course) => $course->modules->count());
@endphp
<div class="app-shell">
    <div class="app-grid">
            <x-sidebar />

            <main class="app-main fade-in">
                <x-app-header />
                <header class="glass p-8 lg:p-10">
                    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr),280px]">
                        <div>
                            <p class="eyebrow">Practicum</p>
                            <h1 class="page-title">Practicum Content</h1>
                        </div>
                    </div>
                </header>

                <div class="space-y-4">
                    <x-alert-success />

                    @if (session('error'))
                    <div class="notice-danger">
                        {{ session('error') }}
                    </div>
                    @endif

                    @if ($errors->any())
                    <div class="notice-danger">
                        {{ $errors->first() }}
                    </div>
                    @endif
                </div>
                
                @forelse ($courses as $course)
                @php
                $courseLabel = \Illuminate\Support\Str::contains(strtolower($course->docker_image), 'python') ? 'Python Lab' : 'Interactive Lab';
                @endphp
                <section class="glass p-7 sm:p-8 space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <span class="chip">{{ $courseLabel }}</span>
                            <h2 class="mt-4 font-display text-3xl tracking-[-0.04em] text-slate-950">{{ $course->course_title }}</h2>
                            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500">
                                {{ $course->modules_count }} modules tersedia untuk course ini. Runtime dasar:
                                <span class="font-semibold text-slate-700">{{ $course->docker_image }}</span>
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="chip">{{ $course->modules_count }} modules</span>
                            <span class="chip">{{ $course->docker_image }}</span>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[18px] border border-slate-200 bg-white">
                        @foreach ($course->modules as $module)
                        @php
                        $status = $module->practicum_status;
                        $progress = $module->practicum_progress;
                        $progressPercent = (int) ($module->learning_progress_percent ?? (
                            $progress?->status === 'completed'
                                ? 100
                                : ($module->questions_count > 0 && $progress
                                    ? (int) round((($progress->current_question_index ?? 0) / $module->questions_count) * 100)
                                    : 0)
                        ));
                        $runtime = \Illuminate\Support\Str::contains(strtolower($course->docker_image), 'python')
                            ? 'Python'
                            : (\Illuminate\Support\Str::contains(strtolower($course->docker_image), 'mysql') ? 'SQL' : 'General');
                        @endphp

                        <article class="module-row px-6 py-6">
                            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400">
                                        <span class="chip">Module {{ $loop->iteration }}</span>
                                        <span class="chip">{{ $module->time_limit }} min</span>
                                        <span class="chip">{{ $module->questions_count }} questions</span>
                                        <span class="chip">{{ $runtime }}</span>
                                        @if ($module->material_pdf_path)
                                            <span class="chip">PDF Materi</span>
                                        @endif
                                    </div>

                                    <div class="mt-4">
                                        <h3 class="text-[1.35rem] font-semibold tracking-[-0.03em] text-slate-950">{{ $module->title }}</h3>
                                        <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">{{ $module->description }}</p>
                                    </div>

                                    <div class="mt-5 max-w-xl">
                                        <div class="mb-3 flex items-center justify-between text-sm">
                                            <p class="text-slate-400">
                                            @switch($status)
                                                @case('completed')
                                                    Completed
                                                    @break
                                                @case('in_progress')
                                                    In progress
                                                    @break
                                                @case('locked')
                                                    Locked
                                                    @break
                                                @default
                                                    Ready
                                            @endswitch
                                            </p>
                                            <p class="text-slate-500">{{ $progressPercent }}%</p>
                                        </div>
                                        <div class="h-1.5 rounded-full bg-slate-100">
                                            <div class="h-1.5 rounded-full bg-slate-400" style="width: {{ max(0, min(100, $progressPercent)) }}%"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2 xl:w-[180px] xl:shrink-0 xl:self-center">
                                    @if ($status === 'completed')
                                    <a
                                        href="{{ route('mahasiswa.content.show', $module) }}"
                                        class="btn-primary w-full">
                                        Review
                                    </a>
                                    @elseif ($status === 'in_progress')
                                    <a
                                        href="{{ route('mahasiswa.content.show', $module) }}"
                                        class="btn-primary w-full">
                                        Continue
                                    </a>
                                    @elseif ($status === 'locked')
                                    <button
                                        type="button"
                                        disabled
                                        class="w-full cursor-not-allowed rounded-[14px] border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-semibold text-slate-400">
                                        Locked
                                    </button>
                                    @else
                                    <form method="POST" action="{{ route('mahasiswa.content.start', $module) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="btn-primary w-full">
                                            Start
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                        @endforeach
                    </div>
                </section>
                @empty
                <section class="glass px-6 py-10 text-center">
                    <h2 class="font-display text-2xl text-slate-900">No content found</h2>
                </section>
                @endforelse
            </main>
        </div>
    </div>
</div>
@endsection
