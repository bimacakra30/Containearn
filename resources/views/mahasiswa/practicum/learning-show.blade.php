@php
    $activeView   = request('view', 'material');
    $validViews   = ['material', 'quiz', 'lab', 'summary'];
    $activeView   = in_array($activeView, $validViews, true) ? $activeView : 'material';
    $materialUrl  = $module->material_pdf_path ? route('mahasiswa.module.pdf', $module) : null;
    $quizTotal    = $quizQuestions->count();
    $quizPercent  = $quizTotal > 0 ? (int) round(($correctCount / $quizTotal) * 100) : 100;
    $labTotal     = $hasLab ? $questions->count() : 0;
    $labCorrect   = $hasLab
        ? $questions->filter(fn ($question) => (bool) data_get($state, 'answers.' . $question->id_question . '.is_correct', false))->count()
        : 0;

    $editorLanguage = $editorLanguage ?? 'plaintext';
    $editorFilename = $editorFilename ?? 'answer.txt';

    $steps = [
        [
            'key'   => 'material',
            'label' => 'Materi',
            'meta'  => $materialUrl ? 'PDF tersedia' : 'Belum ada PDF',
            'done'  => (bool) $materialUrl,
            'href'  => route('mahasiswa.content.show', ['module' => $module, 'view' => 'material']),
        ],
        [
            'key'   => 'quiz',
            'label' => 'Quiz',
            'meta'  => $correctCount . ' / ' . $quizTotal . ' benar',
            'done'  => $quizAllCorrect,
            'href'  => route('mahasiswa.content.show', ['module' => $module, 'view' => 'quiz']),
        ],
    ];

    if ($hasLab) {
        $steps[] = [
            'key'   => 'lab',
            'label' => 'Praktikum',
            'meta'  => strtoupper($state['runtime'] ?? 'text') . ' sandbox',
            'done'  => $isCompleted,
            'href'  => route('mahasiswa.content.show', ['module' => $module, 'view' => 'lab', 'question' => $currentIndex]),
        ];
    }

    $steps[] = [
        'key'   => 'summary',
        'label' => 'Summary',
        'meta'  => $canOpenSummary ? 'Siap dilihat' : ($quizAllCorrect ? 'Selesaikan praktikum' : 'Selesaikan quiz'),
        'done'  => $isCompleted,
        'href'  => route('mahasiswa.content.show', ['module' => $module, 'view' => 'summary']),
    ];

    $moduleProgress = collect($steps)->avg(function ($step) use ($materialUrl, $quizTotal, $correctCount, $labTotal, $labCorrect, $isCompleted) {
        return match ($step['key']) {
            'material' => $materialUrl ? 100 : 0,
            'quiz' => $quizTotal > 0 ? ($correctCount / $quizTotal) * 100 : 100,
            'lab' => $isCompleted ? 100 : ($labTotal > 0 ? ($labCorrect / $labTotal) * 100 : 0),
            'summary' => $isCompleted ? 100 : 0,
            default => 0,
        };
    });
    $moduleProgress = (int) round($moduleProgress ?? 0);
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
                        <h2 class="mt-3 text-xl font-semibold leading-7 text-slate-950">{{ $module->title }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ $module->description }}</p>
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
                                            <p class="mt-1 text-xs">{{ $isLabLocked ? 'Selesaikan quiz dulu' : $step['meta'] }}</p>
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
                                <p class="eyebrow">Praktikum</p>
                                <h2 class="truncate text-xl font-semibold text-slate-950">{{ $module->title }}</h2>
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

                    <x-alert-success />
                    @if (session('error')) <div class="notice-danger">{{ session('error') }}</div> @endif
                    @if ($errors->any()) <div class="notice-danger">{{ $errors->first() }}</div> @endif

                    {{-- ── MATERIAL ── --}}
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

                    {{-- ── QUIZ ── --}}
                    @elseif ($activeView === 'quiz')
                        <section class="space-y-4">
                            <div class="rounded-[18px] border border-slate-200 bg-white px-6 py-5">
                                <p class="eyebrow">Module Quiz</p>
                                <h3 class="mt-1 text-2xl font-semibold text-slate-950">Jawab semua pertanyaan dengan benar untuk lanjut.</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $correctCount }} dari {{ $quizTotal }} pertanyaan sudah benar.</p>
                            </div>

                            @forelse ($quizQuestions as $question)
                                @php
                                    $ans        = $quizAnswers[$question->id_question] ?? [];
                                    $selected   = $ans['selected_option'] ?? null;
                                    $isCorrect  = $ans['is_correct'] ?? false;
                                    $options    = ['a' => $question->option_a, 'b' => $question->option_b, 'c' => $question->option_c, 'd' => $question->option_d];
                                @endphp
                                <article id="q{{ $question->id_question }}" class="scroll-mt-28 rounded-[18px] border border-slate-200 bg-white p-6">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <span class="chip">Question {{ $loop->iteration }}</span>
                                            <p class="mt-3 text-base leading-7 text-slate-800">{{ $question->question }}</p>
                                        </div>
                                        @if ($selected)
                                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $isCorrect ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600' }}">
                                                {{ $isCorrect ? '✓ Benar' : '✗ Salah' }}
                                            </span>
                                        @endif
                                    </div>

                                    @if (!$isCorrect)
                                        <form method="POST" action="{{ route('mahasiswa.content.quiz', $module) }}" class="mt-5">
                                            @csrf
                                            <input type="hidden" name="question_id" value="{{ $question->id_question }}">
                                            <div class="space-y-3">
                                                @foreach ($options as $key => $label)
                                                    <label class="flex cursor-pointer items-center gap-3 rounded-[14px] border px-4 py-3 transition
                                                        {{ $selected === $key ? ($isCorrect ? 'border-emerald-400 bg-emerald-50' : 'border-rose-400 bg-rose-50') : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}">
                                                        <input type="radio" name="selected_option" value="{{ $key }}"
                                                               {{ $selected === $key ? 'checked' : '' }}
                                                               class="h-4 w-4 accent-emerald-500">
                                                        <span class="text-sm font-medium text-slate-700">
                                                            <span class="mr-2 font-bold uppercase text-slate-400">{{ $key }}.</span>{{ $label }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="mt-4 flex justify-end">
                                                <button type="submit" class="btn-primary px-6 py-2 text-sm">Submit Jawaban</button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="mt-5 space-y-3">
                                            @foreach ($options as $key => $label)
                                                <div class="flex items-center gap-3 rounded-[14px] border px-4 py-3
                                                    {{ $key === $question->correct_option ? 'border-emerald-400 bg-emerald-50' : 'border-slate-100 bg-slate-50' }}">
                                                    <span class="h-4 w-4 rounded-full border-2 {{ $key === $question->correct_option ? 'border-emerald-500 bg-emerald-500' : 'border-slate-300' }}"></span>
                                                    <span class="text-sm font-medium {{ $key === $question->correct_option ? 'text-emerald-800' : 'text-slate-400' }}">
                                                        <span class="mr-2 font-bold uppercase">{{ $key }}.</span>{{ $label }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <div class="rounded-[18px] border border-slate-200 bg-white p-8 text-center text-slate-500">
                                    Belum ada soal quiz untuk modul ini.
                                </div>
                            @endforelse

                            @if ($quizAllCorrect)
                                <div class="rounded-[18px] border border-emerald-200 bg-emerald-50 p-6 text-center">
                                    <p class="text-lg font-semibold text-emerald-800">Semua jawaban benar! 🎉</p>
                                    <p class="mt-1 text-sm text-emerald-600">
                                        @if ($hasLab)
                                            Kamu bisa lanjut ke Praktikum sekarang.
                                        @else
                                            Kamu bisa melihat Summary sekarang.
                                        @endif
                                    </p>
                                    <div class="mt-4">
                                        @if ($hasLab)
                                            <a href="{{ route('mahasiswa.content.show', ['module' => $module, 'view' => 'lab']) }}" class="btn-primary">Lanjut ke Praktikum →</a>
                                        @else
                                            <a href="{{ route('mahasiswa.content.show', ['module' => $module, 'view' => 'summary']) }}" class="btn-primary">Lihat Summary →</a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </section>

                    {{-- ── SUMMARY ── --}}
                    @elseif ($activeView === 'summary')
                        <section class="rounded-[18px] border border-slate-200 bg-white p-8">
                            <div class="mx-auto max-w-3xl text-center">
                                <p class="eyebrow">{{ $canOpenSummary ? 'Completed' : 'Locked' }}</p>
                                <h3 class="mt-3 text-3xl font-semibold tracking-[-0.03em] text-slate-950">
                                    {{ $canOpenSummary ? 'Module selesai.' : 'Summary terbuka setelah semua quiz benar.' }}
                                </h3>
                                <p class="mt-3 text-sm leading-7 text-slate-500">
                                    {{ $canOpenSummary ? 'Kamu sudah menyelesaikan seluruh pertanyaan pada module ini.' : 'Kerjakan semua soal quiz dengan benar untuk membuka summary.' }}
                                </p>
                                <div class="mt-6 flex justify-center gap-3">
                                    <a href="{{ route('mahasiswa.content.index') }}" class="btn-secondary">Back to Content</a>
                                    @unless ($canOpenSummary)
                                        <a href="{{ route('mahasiswa.content.show', ['module' => $module, 'view' => 'quiz']) }}" class="btn-primary">Kerjakan Quiz</a>
                                    @endunless
                                </div>
                            </div>
                        </section>

                    {{-- ── LAB ── --}}
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
    #monaco-editor, #monaco-editor .monaco-editor, #monaco-editor .overflow-guard { border-radius: 0; }
    #monaco-editor .margin, #monaco-editor .monaco-editor-background { background: #111827; }
</style>
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs/loader.js"></script>
@vite('resources/js/practicum.js')
@endif
@endpush
