<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LabQuestion;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\Question;
use App\Models\QuestionProgress;
use App\Models\QuizProgress;
use App\Models\User;
use App\Services\DockerService;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class StudentPracticumController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $courses = Course::query()
            ->with(['modules' => fn($q) => $q
                ->withCount(['questions', 'labQuestions'])
                ->with('questions:id_question,id_module')
                ->orderBy('id_module')])
            ->orderBy('id_course')
            ->get();

        $progresses = ModuleProgress::query()
            ->where('user_id', $user->getKey())
            ->get()
            ->keyBy('module_id');

        $quizProgresses = QuizProgress::query()
            ->where('user_id', $user->getKey())
            ->where('is_correct', true)
            ->get()
            ->keyBy('question_id');

        $courses = $courses
            ->map(fn(Course $c) => $this->decorateCourseModules($c, $progresses, $quizProgresses))
            ->values();

        return view('mahasiswa.practicum.index', [
            'courses' => $courses,
        ]);
    }

    public function start(Request $request, Module $module, DockerService $docker): RedirectResponse
    {
        $user = $request->user();
        $module->load(['course', 'questions', 'labQuestions']);

        $progress = $this->findProgress($user, $module);

        if ($progress === null && !$this->isModuleUnlocked($user, $module)) {
            return redirect()->route('mahasiswa.content.index')
                ->with('error', 'Complete the previous module first.');
        }

        $progress ??= ModuleProgress::query()->create([
            'user_id'                => $user->getKey(),
            'module_id'              => $module->getKey(),
            'status'                 => 'in_progress',
            'current_question_index' => 0,
        ]);

        return redirect()->route('mahasiswa.content.show', ['module' => $module, 'view' => 'material'])
            ->with('success', $progress->wasRecentlyCreated ? 'Module started.' : 'Progress resumed.');
    }

    public function show(Request $request, Module $module, DockerService $docker): View|RedirectResponse
    {
        $user = $request->user();
        $module->load(['course', 'questions', 'labQuestions']);

        $progress = $this->findProgress($user, $module);

        if ($progress === null) {
            if (!$this->isModuleUnlocked($user, $module)) {
                return redirect()->route('mahasiswa.content.index')
                    ->with('error', 'Complete the previous module first.');
            }
            return redirect()->route('mahasiswa.content.index')
                ->with('error', 'Start a module first.');
        }

        $activeView   = $request->query('view', 'material');
        $validViews   = ['material', 'quiz', 'lab', 'summary'];
        $activeView   = in_array($activeView, $validViews, true) ? $activeView : 'material';

        $quizQuestions    = $module->questions->values();
        $quizProgresses   = QuizProgress::query()
            ->where('user_id', $user->getKey())
            ->whereIn('question_id', $quizQuestions->pluck('id_question'))
            ->get()
            ->keyBy('question_id');

        $quizAnswers      = $quizQuestions->mapWithKeys(fn(Question $q) => [
            $q->id_question => [
                'selected_option' => $quizProgresses->get($q->id_question)?->selected_option,
                'is_correct'      => $quizProgresses->get($q->id_question)?->is_correct ?? false,
            ],
        ])->all();

        $correctCount     = $quizProgresses->where('is_correct', true)->count();
        $quizTotal        = $quizQuestions->count();
        $quizAllCorrect   = $quizTotal === 0 || $correctCount >= $quizTotal;
        $labQuestions  = $module->labQuestions->values();
        $hasLab        = $labQuestions->isNotEmpty();
        $runtimeState  = [];

        $shouldPrepareRuntime = $hasLab && $progress->status !== 'completed' && $activeView === 'lab';

        if ($shouldPrepareRuntime) {
            $sessionKey   = 'practicum.runtime.' . $module->getKey();
            $runtimeState = $request->session()->get($sessionKey);
            $runtimeState = is_array($runtimeState) ? $runtimeState : null;

            if ($runtimeState === null) {
                try {
                    $this->resetAllRuntimeSessions($request, $docker);
                    $runtimeState = $this->prepareRuntimeState($user, $module, $docker);
                    $request->session()->put($sessionKey, $runtimeState);
                } catch (Throwable $e) {
                    return redirect()->route('mahasiswa.content.index')
                        ->with('error', 'Failed to prepare the lab container: ' . $e->getMessage());
                }
            } else {
                $runtimeState = $this->normalizeRuntimeState($runtimeState, $module);
                $request->session()->put($sessionKey, $runtimeState);
            }

            if ($expired = $this->expiredSessionResponse($request, $module, $docker, $runtimeState)) {
                return $expired;
            }
        }

        [$state, $labProgresses] = $this->buildModuleState($user, $module, $progress, $runtimeState, $labQuestions);

        $rawIndex        = (int) ($progress->current_question_index ?? 0);
        $checkpointIndex = min($rawIndex, max($labQuestions->count() - 1, 0));
        $isCompleted     = $progress->status === 'completed'
            || ($quizAllCorrect && (!$hasLab || $rawIndex >= $labQuestions->count()));

        $selectedIndex   = $this->resolveSelectedQuestionIndex($request->integer('question', $rawIndex), $progress, $labQuestions);
        $currentQuestion = $labQuestions->get($selectedIndex);
        $currentAnswer   = $currentQuestion instanceof LabQuestion
            ? (array) data_get($state, 'answers.' . $currentQuestion->id_question, [])
            : [];

        $editorLanguage = match ($state['runtime'] ?? 'text') {
            'python' => 'python',
            'sql'    => 'sql',
            default  => 'plaintext',
        };
        $editorFilename = match ($editorLanguage) {
            'python' => 'main.py',
            'sql'    => 'query.sql',
            default  => 'answer.txt',
        };
        $canOpenSummary = $isCompleted || ($quizAllCorrect && !$hasLab);

        return view('mahasiswa.practicum.show', [
            'module'                => $module,
            'quizQuestions'         => $quizQuestions,
            'quizAnswers'           => $quizAnswers,
            'quizAllCorrect'        => $quizAllCorrect,
            'correctCount'          => $correctCount,
            'questions'             => $labQuestions,
            'currentIndex'          => $selectedIndex,
            'checkpointIndex'       => $checkpointIndex,
            'currentQuestion'       => $currentQuestion,
            'currentAnswer'         => $currentAnswer,
            'selectedQuestionIndex' => $selectedIndex,
            'codeDraft'             => old('code', $currentAnswer['submitted_code'] ?? ''),
            'state'                 => $state,
            'isCompleted'           => $isCompleted,
            'hasLab'                => $hasLab,
            'canOpenSummary'        => $canOpenSummary,
            'editorLanguage'        => $editorLanguage,
            'editorFilename'        => $editorFilename,
            'canContinue'           => !$isCompleted && $selectedIndex <= $checkpointIndex && ($currentAnswer['is_correct'] ?? false),
            'sessionExpiresAt'      => $isCompleted ? null : data_get($state, 'session_expires_at'),
            'sessionSignature'      => data_get($state, 'session_signature'),
        ]);
    }

    public function submitQuiz(Request $request, Module $module): RedirectResponse
    {
        $user = $request->user();
        $module->load(['questions']);

        $progress = $this->findProgress($user, $module);
        if ($progress === null) {
            return redirect()->route('mahasiswa.content.index')
                ->with('error', 'Start the module first.');
        }

        $payload = $request->validate([
            'question_id' => ['required', 'integer'],
            'selected_option' => ['required', 'in:a,b,c,d'],
        ]);

        $question = $module->questions->firstWhere('id_question', $payload['question_id']);
        if (!$question instanceof Question) {
            return back()->with('error', 'Question not found.');
        }

        $isCorrect = $payload['selected_option'] === $question->correct_option;

        QuizProgress::query()->updateOrCreate(
            ['user_id' => $user->getKey(), 'question_id' => $question->id_question],
            ['selected_option' => $payload['selected_option'], 'is_correct' => $isCorrect]
        );

        return redirect()->to(route('mahasiswa.content.show', ['module' => $module, 'view' => 'quiz']) . '#q' . $question->id_question)
            ->with($isCorrect ? 'success' : 'error', $isCorrect ? 'Jawaban benar!' : 'Jawaban salah, coba lagi.');
    }

    public function run(Request $request, Module $module, DockerService $docker): RedirectResponse
    {
        $user = $request->user();
        $payload = $request->validate([
            'code'                    => ['required', 'string', 'max:20000'],
            'selected_question_index' => ['nullable', 'integer', 'min:0'],
            'session_expires_at'      => ['nullable', 'integer', 'min:1'],
        ]);

        $module->load(['course', 'labQuestions']);
        $progress = $this->findProgress($user, $module);

        if ($progress === null) {
            return redirect()->route('mahasiswa.content.index')
                ->with('error', 'Session not found. Start the module again.');
        }

        if ($progress->status === 'completed') {
            return redirect()->route('mahasiswa.content.show', ['module' => $module, 'view' => 'summary'])
                ->with('success', 'This module is complete.');
        }

        $labQuestions    = $module->labQuestions->values();
        $selectedIndex   = $this->resolveSelectedQuestionIndex(
            (int) ($payload['selected_question_index'] ?? $progress->current_question_index),
            $progress,
            $labQuestions
        );
        $currentQuestion = $labQuestions->get($selectedIndex);

        if (!$currentQuestion instanceof LabQuestion) {
            return redirect()->route('mahasiswa.content.show', ['module' => $module, 'view' => 'summary'])
                ->with('success', 'This module is complete.');
        }

        $sessionKey   = 'practicum.runtime.' . $module->getKey();
        $runtimeState = $this->normalizeRuntimeState(
            $request->session()->get($sessionKey) ?? [],
            $module
        );
        $request->session()->put($sessionKey, $runtimeState);

        if ($expired = $this->expiredSessionResponse($request, $module, $docker, $runtimeState, $payload['session_expires_at'] ?? null)) {
            return $expired;
        }

        try {
            $execution = $this->executeSubmission($module, $currentQuestion, $payload['code'], $runtimeState, $docker, $user);
        } catch (Throwable $e) {
            $execution = ['exit_code' => 1, 'stdout' => '', 'stderr' => $e->getMessage(), 'is_correct' => false];
        }

        $request->session()->put($sessionKey, $runtimeState);

        QuestionProgress::query()->updateOrCreate(
            ['user_id' => $user->getKey(), 'lab_question_id' => $currentQuestion->getKey()],
            [
                'submitted_code' => $payload['code'],
                'stdout'         => $execution['stdout'],
                'stderr'         => $execution['stderr'],
                'is_correct'     => $execution['is_correct'],
            ]
        );

        return redirect()->route('mahasiswa.content.show', [
            'module' => $module,
            'view' => 'lab',
            'question' => $selectedIndex,
        ]);
    }

    public function end(Request $request, Module $module, DockerService $docker): RedirectResponse
    {
        $user = $request->user();

        if ($this->findProgress($user, $module) === null) {
            return redirect()->route('mahasiswa.content.index')->with('error', 'Session not found.');
        }

        $this->destroyRuntimeState($request, $module, $docker);

        return redirect()->route('mahasiswa.content.index')->with('success', 'Session ended.');
    }

    public function next(Request $request, Module $module, DockerService $docker): RedirectResponse
    {
        $user = $request->user();
        $module->load(['course', 'labQuestions']);

        $progress = $this->findProgress($user, $module);

        if ($progress === null) {
            return redirect()->route('mahasiswa.content.index')
                ->with('error', 'Session not found. Start the module again.');
        }

        if ($progress->status === 'completed') {
            return redirect()->route('mahasiswa.content.show', $module)
                ->with('success', 'This module is complete.');
        }

        $payload      = $request->validate([
            'selected_question_index' => ['nullable', 'integer', 'min:0'],
            'session_expires_at'      => ['nullable', 'integer', 'min:1'],
        ]);
        $sessionKey   = 'practicum.runtime.' . $module->getKey();
        $runtimeState = $this->normalizeRuntimeState(
            $request->session()->get($sessionKey) ?? [],
            $module
        );
        $request->session()->put($sessionKey, $runtimeState);

        if ($expired = $this->expiredSessionResponse($request, $module, $docker, $runtimeState, $payload['session_expires_at'] ?? null)) {
            return $expired;
        }

        $labQuestions    = $module->labQuestions->values();
        $selectedIndex   = $this->resolveSelectedQuestionIndex(
            (int) ($payload['selected_question_index'] ?? $progress->current_question_index),
            $progress,
            $labQuestions
        );
        $currentQuestion = $labQuestions->get($selectedIndex);

        if (!$currentQuestion instanceof LabQuestion) {
            return redirect()->route('mahasiswa.content.show', $module)
                ->with('success', 'This module is complete.');
        }

        $answer = QuestionProgress::query()
            ->where('user_id', $user->getKey())
            ->where('lab_question_id', $currentQuestion->getKey())
            ->first();

        if (!($answer?->is_correct)) {
            return redirect()->route('mahasiswa.content.show', [
                'module' => $module,
                'view' => 'lab',
                'question' => $selectedIndex,
            ])
                ->with('error', 'Continue is available after a correct answer.');
        }

        $nextIndex = max((int) $progress->current_question_index, $selectedIndex + 1);

        if ($nextIndex >= $labQuestions->count()) {
            $progress->update(['status' => 'completed', 'current_question_index' => $labQuestions->count(), 'completed_at' => now()]);
            $this->destroyRuntimeState($request, $module, $docker);
        } else {
            $progress->update(['status' => 'in_progress', 'current_question_index' => $nextIndex]);
        }

        if ($nextIndex >= $labQuestions->count()) {
            return redirect()->route('mahasiswa.content.show', ['module' => $module, 'view' => 'summary'])
                ->with('success', 'Module completed.');
        }

        return redirect()->route('mahasiswa.content.show', [
            'module' => $module,
            'view' => 'lab',
            'question' => $nextIndex,
        ]);
    }

    public function servePdf(Module $module)
    {
        abort_unless($module->material_pdf_path, 404);
        $path = storage_path('app/public/' . $module->material_pdf_path);
        abort_unless(file_exists($path), 404);

        return response()->make(
            file_get_contents($path),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="materi.pdf"',
            ]
        );
    }
    private function buildModuleState(User $user, Module $module, ModuleProgress $progress, array $runtimeState = [], Collection $labQuestions = new Collection()): array
    {
        $labProgresses = QuestionProgress::query()
            ->where('user_id', $user->getKey())
            ->whereIn('lab_question_id', $labQuestions->pluck('id_question'))
            ->get()
            ->keyBy('lab_question_id');

        $answers = $labProgresses->mapWithKeys(fn(QuestionProgress $qp) => [
            $qp->lab_question_id => [
                'submitted_code' => $qp->submitted_code,
                'stdout'         => $qp->stdout,
                'stderr'         => $qp->stderr,
                'is_correct'     => $qp->is_correct,
                'executed_at'    => optional($qp->updated_at)->toIso8601String(),
            ],
        ])->all();

        return [[
            'runtime'                => $this->resolveRuntime($module),
            'status'                 => $progress->status,
            'current_question_index' => $progress->current_question_index,
            'completed_at'           => optional($progress->completed_at)->toIso8601String(),
            'session_expires_at'     => $runtimeState['expires_at'] ?? null,
            'session_signature'      => $runtimeState['session_key'] ?? ($runtimeState['container_name'] ?? null),
            'answers'                => $answers,
            'container_name'         => $runtimeState['container_name'] ?? null,
            'container_id'           => $runtimeState['container_id'] ?? null,
        ], $labProgresses];
    }

    private function resolveSelectedQuestionIndex(int $requested, ModuleProgress $progress, Collection $questions): int
    {
        if ($questions->isEmpty()) return 0;

        $max = $progress->status === 'completed'
            ? $questions->count() - 1
            : min((int) $progress->current_question_index, $questions->count() - 1);

        return max(0, min($requested, $max));
    }

    private function executeSubmission(
        Module $module,
        LabQuestion $question,
        string $code,
        array &$runtimeState,
        DockerService $docker,
        User $user,
    ): array {
        $runtime = $this->resolveRuntime($module);

        if ($runtime === 'python') {
            if (empty($runtimeState['container_name'])) {
                $runtimeState = $this->prepareRuntimeState($user, $module, $docker);
            }

            $docker->writeFileToContainer($runtimeState['container_name'], '/tmp/main.py', $code);
            $result = $docker->runPythonFile($runtimeState['container_name'], '/tmp/main.py');

            return [
                'exit_code'  => $result['exit_code'],
                'stdout'     => $result['stdout'],
                'stderr'     => $result['stderr'],
                'is_correct' => $result['exit_code'] === 0
                    && $this->normalizeOutput($result['stdout']) === $this->normalizeOutput($question->output),
            ];
        }

        return [
            'exit_code' => 0,
            'stdout'    => $code,
            'stderr'    => '',
            'is_correct' => $this->normalizeOutput($code) === $this->normalizeOutput($question->output),
        ];
    }

    private function findProgress(User $user, Module $module): ?ModuleProgress
    {
        return ModuleProgress::query()
            ->where('user_id', $user->getKey())
            ->where('module_id', $module->getKey())
            ->first();
    }

    private function isModuleUnlocked(User $user, Module $module): bool
    {
        $previousModule = Module::query()
            ->where('id_course', $module->id_course)
            ->where('id_module', '<', $module->getKey())
            ->orderByDesc('id_module')
            ->with(['questions:id_question,id_module'])
            ->withCount(['questions', 'labQuestions'])
            ->first();

        return $previousModule === null || $this->isModuleCompletedForUser($user, $previousModule);
    }

    private function isModuleCompletedForUser(User $user, Module $module): bool
    {
        $progress = ModuleProgress::query()
            ->where('user_id', $user->getKey())
            ->where('module_id', $module->getKey())
            ->first();

        if ($progress?->status === 'completed') {
            return true;
        }

        if ($progress === null || (int) $module->lab_questions_count > 0) {
            return false;
        }

        $questionIds = $module->questions->pluck('id_question');
        if ($questionIds->isEmpty()) {
            return false;
        }

        $correctCount = QuizProgress::query()
            ->where('user_id', $user->getKey())
            ->whereIn('question_id', $questionIds)
            ->where('is_correct', true)
            ->count();

        return $correctCount >= $questionIds->count();
    }

    private function resolveRuntime(Module $module): string
    {
        $image = Str::lower((string) optional($module->course)->docker_image);

        return match (true) {
            Str::contains($image, 'python') => 'python',
            default => 'text',
        };
    }

    private function prepareRuntimeState(User $user, Module $module, DockerService $docker): array
    {
        $runtime = $this->resolveRuntime($module);
        $state   = [
            'runtime'     => $runtime,
            'started_at'  => now()->toIso8601String(),
            'expires_at'  => now()->addMinutes(max(1, (int) $module->time_limit))->getTimestampMs(),
            'session_key' => (string) Str::uuid(),
        ];

        if ($runtime === 'python') {
            $container = $docker->startPythonContainer(
                sprintf('containearn-u%s-m%s-%s', $user->getKey(), $module->getKey(), Str::lower(Str::random(6))),
                $module->course->docker_image,
            );
            $state['container_id']   = $container['container_id'];
            $state['container_name'] = $container['container_name'];
        }

        return $state;
    }

    private function remainingSessionSeconds(array $runtimeState, Module $module): int
    {
        if (!empty($runtimeState['expires_at'])) {
            return max(0, (int) ceil(((int) $runtimeState['expires_at'] - now()->getTimestampMs()) / 1000));
        }

        $elapsed = empty($runtimeState['started_at']) ? 0 : now()->diffInSeconds(Carbon::parse($runtimeState['started_at']));
        return max(0, max(1, (int) $module->time_limit) * 60 - $elapsed);
    }

    private function normalizeRuntimeState(array $state, Module $module): array
    {
        $state['runtime']     ??= $this->resolveRuntime($module);
        $state['started_at']  ??= now()->toIso8601String();
        $state['session_key'] ??= $state['container_name'] ?? (string) Str::uuid();
        $state['expires_at']  ??= Carbon::parse($state['started_at'])
            ->addMinutes(max(1, (int) $module->time_limit))
            ->getTimestampMs();

        return $state;
    }

    private function expiredSessionResponse(
        Request $request,
        Module $module,
        DockerService $docker,
        array $runtimeState,
        ?int $browserExpiresAt = null,
    ): ?RedirectResponse {
        $browserExpired = $browserExpiresAt !== null && now()->getTimestampMs() >= $browserExpiresAt;

        if (!$browserExpired && $this->remainingSessionSeconds($runtimeState, $module) > 0) {
            return null;
        }

        $this->destroyRuntimeState($request, $module, $docker);

        return redirect()->route('mahasiswa.content.index')->with('error', 'Session time is over.');
    }

    private function normalizeOutput(string $value): string
    {
        return preg_replace("/\r\n?/", "\n", trim($value)) ?? trim($value);
    }

    private function resetAllRuntimeSessions(Request $request, DockerService $docker): void
    {
        foreach ((array) $request->session()->get('practicum.runtime', []) as $moduleId => $state) {
            if (!empty($state['container_name'])) $docker->destroyContainer($state['container_name']);
            $request->session()->forget("practicum.runtime.{$moduleId}");
        }
    }

    private function destroyRuntimeState(Request $request, Module $module, DockerService $docker): void
    {
        $key   = 'practicum.runtime.' . $module->getKey();
        $state = (array) $request->session()->get($key, []);

        if (!empty($state['container_name'])) $docker->destroyContainer($state['container_name']);

        $request->session()->forget($key);
    }

    private function decorateCourseModules(Course $course, Collection $progresses, Collection $quizProgresses): Course
    {
        $previousCompleted = true;

        $course->setRelation(
            'modules',
            $course->modules
                ->sortBy('id_module')
                ->values()
                ->map(function (Module $module) use ($progresses, $quizProgresses, &$previousCompleted) {
                    $progress     = $progresses->get($module->getKey());
                    $quizTotal    = (int) $module->questions_count;
                    $labTotal     = (int) $module->lab_questions_count;
                    $correctCount = $module->questions
                        ->filter(fn(Question $question) => $quizProgresses->has($question->id_question))
                        ->count();

                    $quizPercent = $quizTotal > 0 ? (int) round(($correctCount / $quizTotal) * 100) : 0;
                    $quizDone    = $quizTotal > 0 && $correctCount >= $quizTotal;
                    $completed   = $progress?->status === 'completed' || ($progress !== null && $labTotal === 0 && $quizDone);

                    $status   = match (true) {
                        $completed                           => 'completed',
                        $progress?->status === 'in_progress' => 'in_progress',
                        $previousCompleted                   => 'available',
                        default                              => 'locked',
                    };

                    $module->setAttribute('practicum_status', $status);
                    $module->setAttribute('practicum_progress', $progress);
                    $module->setAttribute('learning_progress_percent', $completed ? 100 : $quizPercent);
                    $module->setAttribute('quiz_correct_count', $correctCount);
                    $previousCompleted = $completed;

                    return $module;
                })
        );

        return $course;
    }
}
