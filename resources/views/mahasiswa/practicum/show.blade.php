@extends('layouts.master')

@section('content')
<div class="{{ $isCompleted ? 'min-h-screen' : 'h-screen overflow-hidden' }}">
    <div class="mx-auto h-full max-w-none px-4 py-4 sm:px-6 lg:px-8">
        <main class="{{ $isCompleted ? 'space-y-6 fade-in' : 'flex h-full min-h-0 flex-col gap-4 fade-in' }}">
            <header class="flex shrink-0 flex-col gap-4 rounded-[2rem] border border-white/70 bg-white/70 px-5 py-4 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <a href="{{ route('mahasiswa.content.index') }}" class="text-sm font-medium text-indigo-600 transition hover:text-indigo-700">
                        &larr; Back to content
                    </a>
                    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.35em] text-indigo-500">Practicum Lab</p>
                    <h1 class="mt-1 font-display text-2xl text-slate-900 sm:text-3xl">{{ $module->title }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">{{ $module->description }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:min-w-[430px] xl:grid-cols-[repeat(3,minmax(0,1fr)),auto]">
                    @unless ($isCompleted)
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Timer</p>
                        <p
                            id="session-timer"
                            data-expires-at="{{ $sessionExpiresAt }}"
                            data-storage-key="practicum_timer_{{ $module->id_module }}"
                            data-session-signature="{{ $sessionSignature }}"
                            class="mt-1 text-lg font-semibold text-slate-900">
                            00:00
                        </p>
                    </div>
                    @endunless
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Progress</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ $correctCount }} / {{ $questions->count() }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Runtime</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ strtoupper($state['runtime'] ?? 'text') }}</p>
                    </div>
                    @unless ($isCompleted)
                    <button
                        type="submit"
                        form="end-session-form"
                        class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        End
                    </button>
                    @endunless
                </div>
            </header>

            <div class="shrink-0 space-y-3">
                <x-alert-success />

                @if (session('error'))
                <div class="glass rounded-2xl border-l-4 border-rose-400 px-5 py-3 text-sm font-medium text-rose-700">
                    {{ session('error') }}
                </div>
                @endif

                @if ($errors->any())
                <div class="glass rounded-2xl border-l-4 border-rose-400 px-5 py-3 text-sm font-medium text-rose-700">
                    {{ $errors->first() }}
                </div>
                @endif
            </div>

            @if (($state['runtime'] ?? null) !== 'python')
            <div class="glass shrink-0 rounded-2xl border-l-4 border-amber-400 px-5 py-3 text-sm text-amber-800">
                Answer-key validation mode.
            </div>
            @endif

            @if ($isCompleted)
            <section class="glass rounded-[1.75rem] p-6 sm:p-7">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-indigo-500">Completed</p>
                        <h2 class="mt-2 font-display text-3xl text-slate-900">All questions are done.</h2>
                    </div>
                    <a
                        href="{{ route('mahasiswa.content.index') }}"
                        class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Back to content
                    </a>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    @foreach ($questions as $question)
                    @php
                    $answer = (array) data_get($state, 'answers.' . $question->id_question, []);
                    $isCorrect = $answer['is_correct'] ?? false;
                    @endphp
                    <article class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm font-semibold text-slate-900">Question {{ $loop->iteration }}</span>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $isCorrect ? 'bg-indigo-50 text-indigo-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ $isCorrect ? 'Correct' : 'Pending' }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $question->question }}</p>

                        @if (!empty($answer['submitted_code']))
                        <div class="mt-4 rounded-2xl bg-slate-950 p-4 text-sm text-slate-100">
                            <p class="mb-2 text-xs uppercase tracking-[0.25em] text-slate-400">Last submission</p>
                            <pre class="overflow-x-auto whitespace-pre-wrap font-mono">{{ $answer['submitted_code'] }}</pre>
                        </div>
                        @endif
                    </article>
                    @endforeach
                </div>
            </section>
            @else
            @php
            $editorLanguage = match ($state['runtime'] ?? 'text') {
            'python' => 'python',
            'sql' => 'sql',
            default => 'plaintext',
            };
            $editorFilename = match ($editorLanguage) {
            'python' => 'main.py',
            'sql' => 'query.sql',
            default => 'answer.txt',
            };
            @endphp
            <section class="grid min-h-0 flex-1 gap-4 xl:grid-cols-[450px,minmax(0,1fr),400px]">
                <aside class="glass flex min-h-0 flex-col rounded-[1.75rem] p-5">
                    <div class="flex flex-wrap gap-2">
                        @foreach ($questions as $question)
                        @php
                        $answer = (array) data_get($state, 'answers.' . $question->id_question, []);
                        $isCurrent = $currentQuestion && $currentQuestion->id_question === $question->id_question;
                        $isCorrect = $answer['is_correct'] ?? false;
                        $isAccessible = $isCompleted || $loop->index <= $checkpointIndex;
                            @endphp
                            @if ($isAccessible)
                            <a
                            href="{{ route('mahasiswa.content.show', ['module' => $module, 'question' => $loop->index]) }}"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $isCurrent ? 'bg-slate-900 text-white' : ($isCorrect ? 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' : 'bg-slate-100 text-slate-600 hover:bg-slate-200') }}">
                            Q{{ $loop->iteration }}
                            </a>
                            @else
                            <span class="cursor-not-allowed rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-400">
                                Q{{ $loop->iteration }}
                            </span>
                            @endif
                            @endforeach
                    </div>

                    <div class="mt-4 rounded-[1.5rem] border border-slate-200 bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-indigo-500">
                            Question {{ $currentIndex + 1 }} of {{ $questions->count() }}
                        </p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900">Task</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">
                            {{ $currentQuestion?->question }}
                        </p>
                    </div>
                </aside>

                <section class="min-w-0">
                    <div class="flex h-full min-h-0 flex-col overflow-hidden rounded-[1.75rem] border border-slate-200 bg-slate-950 shadow-[0_20px_50px_rgba(2,6,23,0.16)]">
                        <div class="flex items-center justify-between border-b border-slate-800 px-5 py-4">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full bg-rose-400"></span>
                                <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                                <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
                            </div>
                            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-400">Editor / Terminal</p>
                        </div>

                        <form id="code-run-form" method="POST" action="{{ route('mahasiswa.content.run', $module) }}" class="flex min-h-0 flex-1 flex-col min-w-0">
                            @csrf
                            <div class="border-b border-slate-800 bg-slate-900/70 px-5 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <span class="rounded-lg bg-slate-800 px-3 py-1.5 font-mono text-xs text-slate-200">{{ $editorFilename }}</span>
                                        <span class="rounded-full border border-slate-700 px-3 py-1 text-xs font-medium text-slate-400">{{ strtoupper($editorLanguage) }}</span>
                                    </div>
                                    <p class="text-sm text-slate-400">Run to validate.</p>
                                </div>
                                <textarea
                                    id="code"
                                    name="code"
                                    class="hidden"
                                    aria-hidden="true">{{ $codeDraft }}</textarea>
                                <input type="hidden" name="selected_question_index" value="{{ $selectedQuestionIndex }}">
                                <input type="hidden" name="session_expires_at" id="run-session-expires-at">
                            </div>
                            <div
                                id="monaco-editor"
                                data-language="{{ $editorLanguage }}"
                                class="monaco-shell min-h-0 flex-1 overflow-hidden"></div>

                            <div class="flex flex-col gap-3 border-t border-slate-800 bg-slate-950 px-5 py-4 sm:flex-row sm:items-center sm:justify-end">
                                <button
                                    type="submit"
                                    form="continue-form"
                                    @disabled(!$canContinue)
                                    class="w-full rounded-2xl px-5 py-3 text-sm font-semibold transition sm:order-2 sm:w-auto {{ $canContinue ? 'bg-slate-800 text-white hover:bg-slate-700' : 'cursor-not-allowed bg-slate-700 text-slate-400' }}">
                                    Continue
                                </button>
                                <button
                                    type="submit"
                                    class="rounded-2xl bg-indigo-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-400 sm:order-1">
                                    Run Code
                                </button>
                            </div>
                        </form>
                    </div>

                    <form id="continue-form" method="POST" action="{{ route('mahasiswa.content.next', $module) }}" class="hidden">
                        @csrf
                        <input type="hidden" name="session_expires_at" id="continue-session-expires-at">
                    </form>

                    <form id="end-session-form" method="POST" action="{{ route('mahasiswa.content.end', $module) }}" class="hidden">
                        @csrf
                    </form>

                </section>

                <section class="glass flex min-h-0 flex-col rounded-[1.75rem] p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-indigo-500">Output</p>
                            <h3 class="mt-2 text-xl font-semibold text-slate-900">Latest Result</h3>
                        </div>

                        @php
                        $answerCorrect = $currentAnswer['is_correct'] ?? false;
                        @endphp
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $answerCorrect ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $answerCorrect ? 'Correct' : 'Pending' }}
                        </span>
                    </div>

                    <div class="mt-4 min-h-0 flex-1 flex flex-col gap-3 overflow-hidden">
                        <div class="flex-1 min-h-0 rounded-[1.5rem] border border-slate-200 bg-slate-950 p-4 text-sm text-slate-100 flex flex-col">
                            <p class="mb-2 shrink-0 text-xs uppercase tracking-[0.25em] text-slate-500">stdout</p>
                            <pre class="flex-1 min-h-0 overflow-auto whitespace-pre-wrap font-mono">{{ $currentAnswer['stdout'] ?? '' ?: 'No output.' }}</pre>
                        </div>

                        @if (!empty($currentAnswer['stderr']))
                        <div class="shrink-0 rounded-[1.5rem] border border-rose-800 bg-slate-950 p-4 text-sm text-rose-400">
                            <p class="mb-2 text-xs uppercase tracking-[0.25em] text-rose-600">stderr</p>
                            <pre class="max-h-40 overflow-auto whitespace-pre-wrap font-mono">{{ $currentAnswer['stderr'] }}</pre>
                        </div>
                        @endif
                    </div>
                </section>
            </section>
            @endif
        </main>
    </div>
</div>
@endsection

@push('scripts')
<style>
    #monaco-editor,
    #monaco-editor .monaco-editor,
    #monaco-editor .overflow-guard {
        border-radius: 0;
    }

    #monaco-editor .margin,
    #monaco-editor .monaco-editor-background {
        background: #111827;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs/loader.js"></script>
@vite('resources/js/practicum.js')
@endpush