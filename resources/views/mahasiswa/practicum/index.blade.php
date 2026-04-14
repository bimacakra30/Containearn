@extends('layouts.master')

@section('content')
<div class="min-h-screen">
    <div class="mx-auto max-w-none px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
        <div class="grid gap-6 lg:grid-cols-[280px,1fr] lg:gap-8">
            <x-sidebar />

            <main class="space-y-6 fade-in">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-indigo-500">Practicum</p>
                        <h1 class="mt-2 font-display text-3xl text-slate-900 sm:text-4xl">Practicum Content</h1>
                    </div>

                <div class="space-y-4">
                    <x-alert-success />

                    @if (session('error'))
                    <div class="glass rounded-2xl border-l-4 border-rose-400 px-5 py-4 text-sm font-medium text-rose-700">
                        {{ session('error') }}
                    </div>
                    @endif

                    @if ($errors->any())
                    <div class="glass rounded-2xl border-l-4 border-rose-400 px-5 py-4 text-sm font-medium text-rose-700">
                        {{ $errors->first() }}
                    </div>
                    @endif
                </div>

                <section class="glass rounded-[1.75rem] p-4 sm:p-5">
                    <form method="GET" action="{{ route('mahasiswa.content.index') }}" class="flex flex-col gap-3 sm:flex-row">
                        <label for="search" class="sr-only">Search practicum content</label>
                        <input
                            id="search"
                            name="search"
                            type="text"
                            value="{{ $search }}"
                            placeholder="Search content..."
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                        <button
                            type="submit"
                            class="rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            Search
                        </button>
                    </form>
                </section>

                @forelse ($courses as $course)
                @php
                $courseLabel = \Illuminate\Support\Str::contains(strtolower($course->docker_image), 'python') ? 'Python Lab' : 'Interactive Lab';
                @endphp
                <section class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="font-display text-2xl text-slate-900">{{ $course->course_title }}</h2>
                        </div>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-2">
                        @foreach ($course->modules as $module)
                        @php
                        $status = $module->practicum_status;
                        $progress = $module->practicum_progress;
                        $runtime = \Illuminate\Support\Str::contains(strtolower($course->docker_image), 'python')
                            ? 'Python'
                            : (\Illuminate\Support\Str::contains(strtolower($course->docker_image), 'mysql') ? 'SQL' : 'General');
                        @endphp

                        <article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-[0_16px_40px_rgba(15,23,42,0.06)] transition hover:-translate-y-1 hover:shadow-[0_24px_50px_rgba(15,23,42,0.09)]">
                            <div class="flex items-start justify-between gap-4">
                                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                                    Module {{ $loop->iteration }}
                                </span>
                                <span class="text-xs font-medium text-slate-400">
                                    {{ $module->time_limit }} min
                                </span>
                            </div>

                            <div class="mt-5 space-y-3">
                                <div>
                                    <h3 class="text-xl font-semibold text-slate-900">{{ $module->title }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $module->description }}</p>
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs font-medium text-slate-500">
                                    <span class="rounded-full border border-slate-200 px-3 py-1">{{ $module->questions_count }} questions</span>
                                    <span class="rounded-full border border-slate-200 px-3 py-1">{{ $runtime }}</span>
                                </div>
                            </div>

                            <div class="mt-6 flex items-center justify-between">
                                <p class="text-sm text-slate-400">
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

                                @if ($status === 'completed')
                                <a
                                    href="{{ route('mahasiswa.content.show', $module) }}"
                                    class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    Review
                                </a>
                                @elseif ($status === 'in_progress')
                                <a
                                    href="{{ route('mahasiswa.content.show', $module) }}"
                                    class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    Continue
                                </a>
                                @elseif ($status === 'locked')
                                <button
                                    type="button"
                                    disabled
                                    class="cursor-not-allowed rounded-2xl bg-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-400">
                                    Locked
                                </button>
                                @else
                                <form method="POST" action="{{ route('mahasiswa.content.start', $module) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                        Start
                                    </button>
                                </form>
                                @endif
                            </div>
                        </article>
                        @endforeach
                    </div>
                </section>
                @empty
                <section class="glass rounded-[1.75rem] px-6 py-10 text-center">
                    <h2 class="font-display text-2xl text-slate-900">No content found</h2>
                </section>
                @endforelse
            </main>
        </div>
    </div>
</div>
@endsection
