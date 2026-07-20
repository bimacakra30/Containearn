@php
$activeView = request('view', 'material');
$validViews = ['material', 'quiz', 'lab', 'summary'];
$activeView = in_array($activeView, $validViews, true) ? $activeView : 'material';
$materialUrl = $module->module_pdf_path ? route('mahasiswa.module.pdf', $module) : null;
$quizTotal = $quizQuestions->count();
$quizWindow = $quizWindow ?? [
'starts_label' => null,
'ends_label' => null,
'has_not_opened' => false,
'has_closed' => false,
'is_open' => true,
];

$editorLanguage = $editorLanguage ?? 'plaintext';
$editorFilename = $editorFilename ?? 'answer.txt';

$steps = [
[
'key' => 'material',
'label' => 'Learning Materials',
'meta' => $materialUrl ? 'PDF available' : 'No PDF available',
'done' => (bool) $materialUrl,
'href' => route('mahasiswa.content.show', ['module' => $module, 'view' => 'material']),
],
[
'key' => 'quiz',
'label' => 'Quiz',
'meta' => $correctCount . ' / ' . $quizTotal . ' Correct',
'done' => $quizAllCorrect,
'href' => route('mahasiswa.content.show', ['module' => $module, 'view' => 'quiz']),
],
];

if ($hasLab) {
$steps[] = [
'key' => 'lab',
'label' => 'Practicum',
'meta' => strtoupper($state['runtime'] ?? 'text') . ' sandbox',
'done' => $isCompleted,
'href' => route('mahasiswa.content.show', ['module' => $module, 'view' => 'lab', 'question' => $currentIndex]),
];
}

$steps[] = [
'key' => 'summary',
'label' => 'Summary',
'meta' => $canOpenSummary ? 'Ready to be seen' : ($quizAllCorrect ? 'Complete the practicum' : 'Complete the quiz'),
'done' => $isCompleted,
'href' => route('mahasiswa.content.show', ['module' => $module, 'view' => 'summary']),
];

$progressSteps = collect($steps)->reject(fn ($step) => $step['key'] === 'summary');
$calculatedModuleProgress = $progressSteps->isNotEmpty()
? (int) round(($progressSteps->where('done', true)->count() / $progressSteps->count()) * 100)
: 0;
$moduleProgress = (int) ($moduleProgress ?? $calculatedModuleProgress);
@endphp

<div class="min-h-screen bg-slate-50">
    <main data-module-id="{{ $module->id_module }}" class="min-h-screen">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="flex min-h-[88px] items-center justify-between gap-4 px-5 sm:px-8">
                <div class="flex min-w-0 items-center gap-4">
                    <a href="{{ route('mahasiswa.content.index') }}" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[14px] border border-slate-200 bg-white text-xl font-semibold text-slate-700 transition hover:bg-slate-50" aria-label="Back to content">&larr;</a>
                    <div class="min-w-0">
                        <p class="eyebrow">Learning Module</p>
                        <h1 class="truncate font-display text-2xl tracking-[-0.04em] text-slate-950 sm:text-3xl">{{ $module->course->course_title }}</h1>
                    </div>
                </div>
                <div class="hidden items-center gap-3 md:flex">
                    <span class="chip">{{ $module->time_limit }} min</span>
                    <span class="chip">{{ $quizTotal }} questions</span>
                    @if ($hasLab)
                    <span class="chip">{{ strtoupper($state['runtime'] ?? 'text') }}</span>
                    @endif
                </div>
            </div>
        </header>

        <div class="grid min-h-[calc(100vh-88px)] lg:grid-cols-[360px,minmax(0,1fr)]">
            <aside class="border-b border-slate-200 bg-white lg:sticky lg:top-[88px] lg:h-[calc(100vh-88px)] lg:border-b-0 lg:border-r">
                <div class="flex h-full flex-col">
                    <div class="border-b border-slate-200 p-5">
                        <p class="eyebrow">Course Outline</p>
                        <h2 class="mt-3 text-xl font-semibold leading-7 text-slate-950">{{ $module->module_title }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500 text-justify">{{ $module->description }}</p>
                        <div class="mt-5">
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-500">Progress</span>
                                <span class="font-semibold text-slate-700">{{ $moduleProgress }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-emerald-500" style="width: {{ max(0, min(100, $moduleProgress)) }}%"></div>
                            </div>
                        </div>
                    </div>

                    <nav class="min-h-0 flex-1 overflow-y-auto p-4">
                        <div class="space-y-2">
                            @foreach ($steps as $step)
                            @php
                            $isActive = $activeView === $step['key'];
                            $isLocked = $step['key'] === 'summary' && !$canOpenSummary;
                            $isLabLocked = $step['key'] === 'lab' && !$quizAllCorrect;
                            @endphp
                            @if ($isLocked || $isLabLocked)
                            <div class="flex items-start gap-3 rounded-[14px] border border-slate-100 bg-slate-50 px-4 py-3 text-slate-400">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-slate-200 text-xs">🔒</span>
                                <div>
                                    <p class="text-sm font-semibold">{{ $step['label'] }}</p>
                                    <p class="mt-1 text-xs">{{ $isLabLocked ? 'Finish the quiz first' : $step['meta'] }}</p>
                                </div>
                            </div>
                            @else
                            <a href="{{ $step['href'] }}" class="flex items-start gap-3 rounded-[14px] border px-4 py-3 transition {{ $isActive ? 'border-emerald-200 bg-emerald-50 text-slate-950' : 'border-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-50' }}">
                                <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold {{ $step['done'] ? 'bg-emerald-500 text-white' : 'border border-slate-200 bg-white text-slate-500' }}">{{ $step['done'] ? '✓' : $loop->iteration }}</span>
                                <div>
                                    <p class="text-sm font-semibold">{{ $step['label'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $step['meta'] }}</p>
                                </div>
                            </a>
                            @endif
                            @endforeach
                        </div>
                    </nav>
                </div>
            </aside>

            <section class="min-w-0 p-4 sm:p-6">
                <div class="mx-auto max-w-[1440px] space-y-4">

                    @if ($activeView === 'lab' && !$isCompleted)
                    <div class="flex flex-col gap-3 rounded-[18px] border border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="eyebrow">Practicum</p>
                            <h2 class="truncate text-xl font-semibold text-slate-950">{{ $module->module_title }}</h2>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="rounded-[14px] border border-slate-200 bg-white px-4 py-2">
                                <p class="eyebrow">Timer</p>
                                <p id="session-timer"
                                    data-expires-at="{{ $sessionExpiresAt }}"
                                    data-storage-key="practicum_timer_{{ $module->id_module }}"
                                    data-session-signature="{{ $sessionSignature }}"
                                    class="mt-1 text-lg font-semibold text-slate-900">00:00</p>
                            </div>
                            <button type="submit" form="end-session-form" class="btn-secondary">End</button>
                        </div>
                    </div>
                    @endif

                    @php $sessionError = session('error'); @endphp
                    @if ($sessionError && !str_contains($sessionError, 'No active quiz attempt'))
                    <div class="notice-danger">{{ $sessionError }}</div>
                    @endif
                    @if ($errors->any()) <div class="notice-danger">{{ $errors->first() }}</div> @endif


                    @if ($activeView === 'material')
                    <section class="overflow-hidden rounded-[18px] border border-slate-200 bg-white">
                        @if ($materialUrl)
                        <iframe src="{{ $materialUrl }}" class="h-[calc(100vh-160px)] min-h-[620px] w-full" style="border:none;"></iframe>
                        @else
                        <div class="flex min-h-[520px] items-center justify-center p-8 text-center">
                            <div>
                                <p class="eyebrow">No PDF</p>
                                <h3 class="mt-3 text-2xl font-semibold text-slate-950">Materi PDF belum tersedia.</h3>
                                <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">Upload PDF dari halaman admin untuk menampilkan materi pada tahap ini.</p>
                            </div>
                        </div>
                        @endif
                    </section>


                    @elseif ($activeView === 'quiz')
                    @php
                    $quizTimerMs = $activeAttempt?->expires_at
                    ? $activeAttempt->expires_at->getTimestampMs()
                    : null;
                    @endphp
                    @if (! $activeAttempt && ! $hasCompletedAttempt && ($attemptsLeft > 0 || $quizWindow['has_not_opened']))
                    <section class="flex min-h-[60vh] items-center justify-center py-12">
                        <div class="w-full max-w-xl text-center space-y-6">
                            <div class="rounded-[24px] border border-slate-200 bg-white p-10 shadow-sm space-y-5">
                                <div>
                                    <p class="eyebrow">Module Quiz</p>
                                    <h3 class="mt-2 text-2xl font-bold text-slate-900">{{ $module->module_title }}</h3>
                                </div>

                                <div class="flex flex-wrap justify-center gap-4 text-sm">
                                    <div class="flex flex-col items-center gap-1 rounded-[14px] border border-slate-100 bg-slate-50 px-5 py-3">
                                        <span class="text-xs text-slate-400 uppercase tracking-wider">Questions</span>
                                        <span class="text-xl font-bold text-slate-800">{{ $quizTotal }}</span>
                                    </div>
                                    @if ($module->quiz_time_limit)
                                    <div class="flex flex-col items-center gap-1 rounded-[14px] border border-amber-100 bg-amber-50 px-5 py-3">
                                        <span class="text-xs text-amber-500 uppercase tracking-wider">Time Limit</span>
                                        <span class="text-xl font-bold text-amber-700">{{ $module->quiz_time_limit }} min</span>
                                    </div>
                                    @endif
                                    <div class="flex flex-col items-center gap-1 rounded-[14px] border border-slate-100 bg-slate-50 px-5 py-3">
                                        <span class="text-xs text-slate-400 uppercase tracking-wider">Attempts Left</span>
                                        <span class="text-xl font-bold text-slate-800">{{ $attemptsLeft }} / {{ $maxAttempts }}</span>
                                    </div>
                                </div>

                                @if ($quizWindow['starts_label'] || $quizWindow['ends_label'])
                                <div class="rounded-[12px] border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                    Quiz window:
                                    <strong class="text-slate-800">{{ $quizWindow['starts_label'] ?? 'Open now' }}</strong>
                                    -
                                    <strong class="text-slate-800">{{ $quizWindow['ends_label'] ?? 'No close time' }}</strong>
                                </div>
                                @endif

                                @if ($totalAttempts > 0)
                                <div class="rounded-[12px] border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                    Previous best: <strong class="text-slate-800">{{ $correctCount }} / {{ $quizTotal }}</strong> correct
                                </div>
                                @endif

                                <form method="POST" action="{{ route('mahasiswa.content.quiz.start', $module) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        @disabled(! $canStartNewAttempt)
                                        class="{{ $canStartNewAttempt ? 'btn-primary' : 'w-full cursor-not-allowed rounded-[14px] border border-slate-200 bg-slate-50 px-5 py-3 font-semibold text-slate-400' }} w-full py-3 text-base">
                                        @if ($quizWindow['has_not_opened'])
                                            Quiz opens at {{ $quizWindow['starts_label'] }}
                                        @else
                                            Start Quiz Attempt {{ $totalAttempts + 1 }} →
                                        @endif
                                    </button>
                                </form>

                                @if ($module->quiz_time_limit)
                                <p class="text-xs text-slate-400">Timer will start immediately when you click the button above.</p>
                                @elseif ($quizWindow['ends_label'])
                                <p class="text-xs text-slate-400">Quiz will close automatically at the scheduled end time.</p>
                                @endif
                            </div>
                        </div>
                    </section>

                    @elseif ($activeAttempt)
                    <section class="space-y-4">
                        <div class="sticky top-[88px] z-20 rounded-[18px] border border-slate-200 bg-white/95 px-6 py-4 shadow-sm backdrop-blur">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="eyebrow">Module Quiz</p>
                                    <h3 class="mt-1 text-xl font-bold text-slate-900">{{ $module->module_title }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Attempt <strong class="text-slate-800">{{ $totalAttempts }}</strong> of <strong class="text-slate-800">{{ $maxAttempts }}</strong>
                                        @if ($module->quiz_time_limit)
                                        &nbsp;·&nbsp; {{ $module->quiz_time_limit }} min
                                        @endif
                                        @if ($quizWindow['ends_label'])
                                        &nbsp;·&nbsp; closes {{ $quizWindow['ends_label'] }}
                                        @endif
                                    </p>
                                </div>
                                @if ($quizTimerMs)
                                <div class="rounded-[14px] border border-amber-200 bg-amber-50 px-5 py-3 text-center min-w-[110px]">
                                    <p class="eyebrow text-amber-600">Time Left</p>
                                    <p id="quiz-timer"
                                        data-expires-at="{{ $quizTimerMs }}"
                                        class="mt-1 text-2xl font-bold text-amber-700 tabular-nums">
                                        --:--
                                    </p>
                                </div>
                                @endif
                            </div>
                        </div>

                        <form method="POST"
                            action="{{ route('mahasiswa.content.quiz.submit-all', $module) }}"
                            id="quiz-submit-all-form">
                            @csrf

                            <div class="space-y-4">
                                @forelse ($quizQuestions as $question)
                                @php
                                $ans = $quizAnswers[$question->id_quiz] ?? [];
                                $isAnswered = $ans['is_answered'] ?? false;
                                $selected = $ans['selected_option'] ?? null;
                                $isCorrect = $ans['is_correct'] ?? false;
                                $options = ['a' => $question->option_a, 'b' => $question->option_b, 'c' => $question->option_c, 'd' => $question->option_d];
                                @endphp
                                <article id="q{{ $question->id_quiz }}" class="scroll-mt-44 rounded-[18px] border border-slate-200 bg-white p-6">
                                    <div class="flex items-start justify-between gap-4 mb-5">
                                        <div class="flex-1">
                                            <span class="chip">Question {{ $loop->iteration }}</span>
                                            <p class="mt-3 text-base leading-7 text-slate-800">{{ $question->question }}</p>
                                        </div>
                                        <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold bg-slate-100 text-slate-400">
                                            Unanswered
                                        </span>
                                    </div>
                                    <div class="space-y-3" id="options-{{ $question->id_quiz }}">
                                        @foreach ($options as $key => $label)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-[14px] border px-4 py-3 transition border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/50"
                                            data-option-label for="opt-{{ $question->id_quiz }}-{{ $key }}">
                                            <input type="radio"
                                                id="opt-{{ $question->id_quiz }}-{{ $key }}"
                                                name="answers[{{ $question->id_quiz }}]"
                                                value="{{ $key }}"
                                                class="h-4 w-4 accent-emerald-500"
                                                onchange="markAnswered({{ $question->id_quiz }})">
                                            <span class="text-sm font-medium text-slate-700">
                                                <span class="mr-2 font-bold uppercase text-slate-400">{{ $key }}.</span>{{ $label }}
                                            </span>
                                        </label>
                                        @endforeach
                                    </div>
                                </article>
                                @empty
                                <div class="rounded-[18px] border border-slate-200 bg-white p-8 text-center text-slate-500">
                                    No quiz questions for this module.
                                </div>
                                @endforelse
                            </div>

                            <div class="mt-4 flex justify-center">
                                <button type="button"
                                    onclick="openSubmitModal()"
                                    class="btn-primary px-10 py-3 text-base">
                                    Submit Quiz →
                                </button>
                            </div>
                        </form>

                        <div id="quiz-submit-modal"
                            class="fixed inset-0 z-50 flex items-center justify-center px-4"
                            style="display:none!important"
                            aria-modal="true" role="dialog">

                            <div id="quiz-modal-backdrop"
                                class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
                                style="opacity:0;transition:opacity .2s ease"
                                onclick="closeSubmitModal()"></div>

                            <div id="quiz-modal-panel"
                                class="relative z-10 w-full max-w-sm rounded-[24px] bg-white p-8 shadow-2xl text-center"
                                style="opacity:0;transform:scale(.95);transition:opacity .2s ease,transform .2s ease">

                                <p class="text-base font-semibold text-slate-900">Submit your answers?</p>
                                <p class="mt-1.5 text-sm text-slate-500">This cannot be undone.</p>

                                <div class="mt-6 flex gap-3">
                                    <button type="button" onclick="closeSubmitModal()"
                                        class="btn-secondary flex-1 py-2.5">Cancel</button>
                                    <button type="button" onclick="doSubmitQuiz()"
                                        class="btn-primary flex-1 py-2.5">Submit →</button>
                                </div>
                            </div>
                        </div>

                    </section>

                    @else
                    <section class="space-y-4">
                        <div class="rounded-[18px] border border-slate-200 bg-white px-6 py-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="eyebrow">Module Quiz — Results</p>
                                    <h3 class="mt-1 text-xl font-bold text-slate-900">{{ $module->module_title }}</h3>
                                    <div class="mt-2 flex flex-wrap gap-3 text-sm text-slate-500">
                                        <span>Attempt <strong class="text-slate-800">{{ $totalAttempts }}</strong> of <strong class="text-slate-800">{{ $maxAttempts }}</strong></span>
                                        <span class="text-slate-300">|</span>
                                        <span><strong class="text-slate-800">{{ $correctCount }}</strong> / {{ $quizTotal }} correct</span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-3xl font-bold {{ $correctCount === $quizTotal ? 'text-emerald-600' : 'text-slate-700' }}">
                                        {{ $quizTotal > 0 ? round($correctCount / $quizTotal * 100) : 0 }}%
                                    </p>
                                    <p class="text-xs text-slate-400 mt-0.5">Score</p>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-3">
                                @if ($canStartNewAttempt)
                                <form method="POST" action="{{ route('mahasiswa.content.quiz.start', $module) }}">
                                    @csrf
                                    <button type="submit" class="btn-primary">
                                        Retry — Attempt {{ $totalAttempts + 1 }} →
                                    </button>
                                </form>
                                @else
                                <div class="rounded-[14px] border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm text-rose-700">
                                    <strong>No attempts remaining.</strong> You have used all {{ $maxAttempts }} attempt(s).
                                </div>
                                @endif
                            </div>
                        </div>

                        @forelse ($quizQuestions as $question)
                        @php
                        $ans = $quizAnswers[$question->id_quiz] ?? [];
                        $selected = $ans['selected_option'] ?? null;
                        $isCorrect = $ans['is_correct'] ?? false;
                        $isAnswered = $ans['is_answered'] ?? false;
                        $options = ['a' => $question->option_a, 'b' => $question->option_b, 'c' => $question->option_c, 'd' => $question->option_d];
                        @endphp
                        <article id="q{{ $question->id_quiz }}" class="scroll-mt-28 rounded-[18px] border border-slate-200 bg-white p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <span class="chip">Question {{ $loop->iteration }}</span>
                                    <p class="mt-3 text-base leading-7 text-slate-800">{{ $question->question }}</p>
                                </div>
                                @if ($isAnswered)
                                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $isCorrect ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600' }}">
                                    {{ $isCorrect ? '✓ Correct' : '✗ Incorrect' }}
                                </span>
                                @else
                                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold bg-slate-100 text-slate-400">
                                    Not answered
                                </span>
                                @endif
                            </div>
                            <div class="mt-5 space-y-3">
                                @foreach ($options as $key => $label)
                                @php
                                $isSelected = $selected === $key;
                                $styleClass = match(true) {
                                $isSelected && $isCorrect => 'border-emerald-400 bg-emerald-50',
                                $isSelected && !$isCorrect => 'border-rose-400 bg-rose-50',
                                default => 'border-slate-100 bg-slate-50',
                                };
                                @endphp
                                <div class="flex items-center gap-3 rounded-[14px] border px-4 py-3 {{ $styleClass }}">
                                    <span class="h-4 w-4 shrink-0 rounded-full border-2 {{ $isSelected ? ($isCorrect ? 'border-emerald-500 bg-emerald-500' : 'border-rose-500 bg-rose-500') : 'border-slate-300' }}"></span>
                                    <span class="text-sm font-medium {{ $isSelected ? ($isCorrect ? 'text-emerald-800' : 'text-rose-800') : 'text-slate-500' }}">
                                        <span class="mr-2 font-bold uppercase">{{ $key }}.</span>{{ $label }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </article>
                        @empty
                        <div class="rounded-[18px] border border-slate-200 bg-white p-8 text-center text-slate-500">
                            No quiz questions for this module.
                        </div>
                        @endforelse
                    </section>
                    @endif

                    @if ($activeAttempt)
                    @push('scripts')
                    <script>
                        (function() {
                            window.markAnswered = function(idQuiz) {
                                const article = document.getElementById('q' + idQuiz);
                                if (!article) return;
                                const badge = article.querySelector('.shrink-0');
                                if (badge) {
                                    badge.textContent = '✓ Answered';
                                    badge.className = 'shrink-0 rounded-full px-3 py-1 text-xs font-semibold bg-emerald-50 text-emerald-600';
                                }
                            };

                            const modal = document.getElementById('quiz-submit-modal');
                            const backdrop = document.getElementById('quiz-modal-backdrop');
                            const panel = document.getElementById('quiz-modal-panel');

                            window.openSubmitModal = function() {
                                modal.style.removeProperty('display');
                                requestAnimationFrame(() => {
                                    backdrop.style.opacity = '1';
                                    panel.style.opacity = '1';
                                    panel.style.transform = 'scale(1)';
                                });
                                document.addEventListener('keydown', onEscKey);
                            };

                            window.closeSubmitModal = function() {
                                backdrop.style.opacity = '0';
                                panel.style.opacity = '0';
                                panel.style.transform = 'scale(.95)';
                                setTimeout(() => {
                                    modal.style.display = 'none';
                                }, 200);
                                document.removeEventListener('keydown', onEscKey);
                            };

                            window.doSubmitQuiz = function() {
                                const form = document.getElementById('quiz-submit-all-form');
                                if (form) form.submit();
                            };

                            function onEscKey(e) {
                                if (e.key === 'Escape') closeSubmitModal();
                            }

                            @if($quizTimerMs) {
                                const timerEl = document.getElementById('quiz-timer');
                                const expiresAt = Number("{{ $quizTimerMs }}");

                                function updateTimer() {
                                    const remaining = Math.max(0, expiresAt - Date.now());
                                    const mins = String(Math.floor(remaining / 60000)).padStart(2, '0');
                                    const secs = String(Math.floor((remaining % 60000) / 1000)).padStart(2, '0');
                                    timerEl.textContent = mins + ':' + secs;

                                    if (remaining <= 0) {
                                        timerEl.textContent = '00:00';
                                        timerEl.classList.add('!text-rose-700');
                                        const form = document.getElementById('quiz-submit-all-form');
                                        if (form) {
                                            form.removeAttribute('onsubmit');
                                            form.submit();
                                        }
                                        return;
                                    }

                                    if (remaining < 60000) {
                                        timerEl.classList.remove('text-amber-700');
                                        timerEl.classList.add('text-rose-700');
                                    }

                                    setTimeout(updateTimer, 500);
                                }

                                updateTimer();
                            }
                            @endif
                        })();
                    </script>
                    @endpush
                    @endif


                    @elseif ($activeView === 'summary')
                    <section class="rounded-[18px] border border-slate-200 bg-white p-8">
                        <div class="mx-auto max-w-3xl text-center">
                            <p class="eyebrow">{{ $canOpenSummary ? 'Completed' : 'Locked' }}</p>
                            <h3 class="mt-3 text-3xl font-semibold tracking-[-0.03em] text-slate-950">
                                {{ $canOpenSummary ? 'Module completed.' : 'Summary unlocked after all quizzes are correct.' }}
                            </h3>
                            <p class="mt-3 text-sm leading-7 text-slate-500">
                                {{ $canOpenSummary ? 'You have completed all questions for this module.' : 'Complete all quiz questions correctly to unlock the summary.' }}
                            </p>
                            <div class="mt-6 flex justify-center gap-3">
                                <a href="{{ route('mahasiswa.content.index') }}" class="btn-secondary">Back to Content</a>
                                @unless ($canOpenSummary)
                                <a href="{{ route('mahasiswa.content.show', ['module' => $module, 'view' => 'quiz']) }}" class="btn-primary">Kerjakan Quiz</a>
                                @endunless
                            </div>
                        </div>
                    </section>


                    @else
                    @include('mahasiswa.practicum.partials.learning-lab')
                    @endif

                </div>
            </section>
        </div>
    </main>
</div>

@push('scripts')
@if ($activeView === 'lab' && !$isCompleted)
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
@endif
@endpush
