@if ($isCompleted)
    <section class="rounded-[18px] border border-slate-200 bg-white p-6 sm:p-7">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="eyebrow">Completed</p>
                <h2 class="mt-2 font-display text-3xl tracking-[-0.04em] text-slate-900">All questions are done.</h2>
            </div>
            <a href="{{ route('mahasiswa.content.show', ['module' => $module, 'view' => 'summary']) }}" class="btn-primary">Open Summary</a>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @foreach ($questions as $question)
                @php
                    $answer = (array) data_get($state, 'answers.' . $question->id_question, []);
                @endphp
                <article class="surface-muted p-5">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm font-semibold text-slate-900">Question {{ $loop->iteration }}</span>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Correct</span>
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
    <style>
        .learning-lab-layout {
            display: grid;
            gap: 1rem;
            min-height: calc(100vh - 220px);
        }

        @media (min-width: 1024px) {
            .learning-lab-layout {
                grid-template-columns: minmax(320px, 420px) minmax(0, 1fr);
                align-items: stretch;
            }
        }
    </style>

    <section class="learning-lab-layout">
        <aside class="space-y-4 rounded-[18px] border border-slate-200 bg-white p-5">
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
                            href="{{ route('mahasiswa.content.show', ['module' => $module, 'view' => 'lab', 'question' => $loop->index]) }}"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition {{ $isCurrent ? 'bg-slate-900 text-white' : ($isCorrect ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-slate-100 text-slate-600 hover:bg-slate-200') }}">
                            Q{{ $loop->iteration }}
                        </a>
                    @else
                        <span class="cursor-not-allowed rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-400">
                            Q{{ $loop->iteration }}
                        </span>
                    @endif
                @endforeach
            </div>

            <div class="rounded-[16px] border border-slate-200 bg-white p-5">
                <p class="eyebrow">Question {{ $currentIndex + 1 }} of {{ $questions->count() }}</p>
                <h2 class="mt-2 text-xl font-semibold text-slate-900">Task</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">{{ $currentQuestion?->question }}</p>
            </div>

            <section class="rounded-[16px] border border-slate-200 bg-white p-5">
                @php
                    $hasSubmission = filled($currentAnswer['submitted_code'] ?? null) || filled($currentAnswer['stdout'] ?? null) || filled($currentAnswer['stderr'] ?? null);
                    $answerCorrect = $currentAnswer['is_correct'] ?? false;
                    $resultLabel = !$hasSubmission ? 'Pending' : ($answerCorrect ? 'Correct' : 'Incorrect');
                    $resultClass = match ($resultLabel) {
                        'Correct' => 'bg-emerald-50 text-emerald-700',
                        'Incorrect' => 'bg-rose-50 text-rose-600',
                        default => 'bg-slate-100 text-slate-500',
                    };
                @endphp
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="eyebrow">Output</p>
                        <h3 class="mt-2 text-xl font-semibold text-slate-900">Latest Result</h3>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $resultClass }}">
                        {{ $resultLabel }}
                    </span>
                </div>

                <div class="mt-4 flex flex-col gap-3">
                    <div class="min-h-[180px] rounded-[1.25rem] border border-slate-200 bg-slate-950 p-4 text-sm text-slate-100">
                        <p class="mb-2 text-xs uppercase tracking-[0.25em] text-slate-500">stdout</p>
                        <pre class="max-h-[220px] overflow-auto whitespace-pre-wrap font-mono">{{ $currentAnswer['stdout'] ?? '' ?: 'No output.' }}</pre>
                    </div>

                    @if (!empty($currentAnswer['stderr']))
                        <div class="rounded-[1.25rem] border border-rose-800 bg-slate-950 p-4 text-sm text-rose-400">
                            <p class="mb-2 text-xs uppercase tracking-[0.25em] text-rose-600">stderr</p>
                            <pre class="max-h-40 overflow-auto whitespace-pre-wrap font-mono">{{ $currentAnswer['stderr'] }}</pre>
                        </div>
                    @endif
                </div>
            </section>
        </aside>

        <section class="min-w-0">
            <div class="flex h-full min-h-[620px] flex-col overflow-hidden rounded-[18px] border border-slate-200 bg-slate-950 shadow-[0_20px_50px_rgba(2,6,23,0.16)]">
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
                        <textarea id="code" name="code" class="hidden" aria-hidden="true">{{ $codeDraft }}</textarea>
                        <input type="hidden" name="selected_question_index" value="{{ $selectedQuestionIndex }}">
                        <input type="hidden" name="session_expires_at" id="run-session-expires-at">
                    </div>
                    <div id="monaco-editor" data-language="{{ $editorLanguage }}" class="monaco-shell min-h-0 flex-1 overflow-hidden"></div>

                    <div class="flex flex-col gap-3 border-t border-slate-800 bg-slate-950 px-5 py-4 sm:flex-row sm:items-center sm:justify-end">
                        <button
                            type="submit"
                            form="continue-form"
                            @disabled(!$canContinue)
                            class="w-full rounded-2xl px-5 py-3 text-sm font-semibold transition sm:order-2 sm:w-auto {{ $canContinue ? 'bg-slate-800 text-white hover:bg-slate-700' : 'cursor-not-allowed bg-slate-700 text-slate-400' }}">
                            Continue
                        </button>
                        <button type="submit" class="rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-500 sm:order-1">
                            Run Code
                        </button>
                    </div>
                </form>
            </div>

            <form id="continue-form" method="POST" action="{{ route('mahasiswa.content.next', $module) }}" class="hidden">
                @csrf
                <input type="hidden" name="selected_question_index" value="{{ $selectedQuestionIndex }}">
                <input type="hidden" name="session_expires_at" id="continue-session-expires-at">
            </form>

            <form id="end-session-form" method="POST" action="{{ route('mahasiswa.content.end', $module) }}" class="hidden">
                @csrf
                <input type="hidden" name="reason" id="end-session-reason" value="manual">
            </form>
        </section>
    </section>
@endif
