<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LabQuestion;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\QuizQuestion;
use App\Models\QuestionProgress;
use App\Models\QuizProgress;
use App\Models\User;
use App\Services\DockerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class StudentPracticumController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $courses = Course::query()
            ->with(['modules' => fn ($q) => $q
                ->withCount(['quizQuestions', 'labQuestions'])
                ->with('quizQuestions:id_quiz,id_module')
                ->with('labQuestions:id_lab,id_module')
                ->orderBy('id_module')])
            ->orderBy('id_course')
            ->get();

        $progresses = ModuleProgress::query()
            ->where('id_user', $user->getKey())
            ->get()
            ->keyBy('id_module');

        $quizProgresses = QuizProgress::query()
            ->where('id_user', $user->getKey())
            ->where('is_correct', true)
            ->get()
            ->keyBy('id_quiz');

        $labProgresses = QuestionProgress::query()
            ->where('id_user', $user->getKey())
            ->where('is_correct', true)
            ->get()
            ->keyBy('id_lab');

        $courses = $courses
            ->map(fn (Course $c) => $this->decorateCourseModules($c, $progresses, $quizProgresses, $labProgresses))
            ->values();

        return view('mahasiswa.practicum.index', [
            'courses' => $courses,
        ]);
    }

    public function start(Request $request, Module $module): RedirectResponse
    {
        $user = $request->user();
        $module->load(['course', 'quizQuestions', 'labQuestions']);

        $progress = $this->findProgress($user, $module);

        if ($progress === null && ! $this->isModuleUnlocked($user, $module)) {
            return redirect()->route('mahasiswa.content.index')
                ->with('error', 'Complete the previous module first.');
        }

        $progress ??= ModuleProgress::query()->create([
            'id_user' => $user->getKey(),
            'id_module' => $module->getKey(),
            'status' => 'in_progress',
            'current_question_index' => 0,
        ]);

        return redirect()->route('mahasiswa.content.show', ['module' => $module, 'view' => 'material'])
            ->with('success', $progress->wasRecentlyCreated ? 'Module started.' : 'Progress resumed.');
    }

    public function show(Request $request, Module $module, DockerService $docker): View|RedirectResponse
    {
        $user = $request->user();
        $module->load(['course', 'quizQuestions', 'labQuestions']);

        $progress = $this->findProgress($user, $module);

        if ($progress === null) {
            if (! $this->isModuleUnlocked($user, $module)) {
                return redirect()->route('mahasiswa.content.index')
                    ->with('error', 'Complete the previous module first.');
            }

            return redirect()->route('mahasiswa.content.index')
                ->with('error', 'Start a module first.');
        }

        $activeView = $request->query('view', 'material');
        $validViews = ['material', 'quiz', 'lab', 'summary'];
        $activeView = in_array($activeView, $validViews, true) ? $activeView : 'material';

        $quizQuestions = $module->quizQuestions->values();
        $quizProgresses = QuizProgress::query()
            ->where('id_user', $user->getKey())
            ->whereIn('id_quiz', $quizQuestions->pluck('id_quiz'))
            ->get()
            ->keyBy('id_quiz');

        $quizAnswers = $quizQuestions->mapWithKeys(fn (QuizQuestion $q) => [
            $q->id_quiz => [
                'selected_option' => $quizProgresses->get($q->id_quiz)?->selected_option,
                'is_correct' => $quizProgresses->get($q->id_quiz)?->is_correct ?? false,
            ],
        ])->all();

        $correctCount = $quizProgresses->where('is_correct', true)->count();
        $quizTotal = $quizQuestions->count();
        $quizAllCorrect = $quizTotal === 0 || $correctCount >= $quizTotal;
        $labQuestions = $module->labQuestions->values();
        $hasLab = $labQuestions->isNotEmpty();
        $runtimeState = [];

        $shouldPrepareRuntime = $hasLab && $progress->status !== 'completed' && $activeView === 'lab';

        if ($shouldPrepareRuntime) {
            $sessionKey = 'practicum.runtime.'.$module->getKey();
            $runtimeState = $request->session()->get($sessionKey);
            $runtimeState = is_array($runtimeState) ? $runtimeState : null;

            if ($runtimeState === null) {
                try {
                    $this->resetAllRuntimeSessions($request, $docker);
                    $runtimeState = $this->prepareRuntimeState($user, $module, $docker);
                    $request->session()->put($sessionKey, $runtimeState);
                } catch (Throwable $e) {
                    return redirect()->route('mahasiswa.content.index')
                        ->with('error', 'Failed to prepare the lab container: '.$e->getMessage());
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

        $rawIndex = (int) ($progress->current_question_index ?? 0);
        $checkpointIndex = min($rawIndex, max($labQuestions->count() - 1, 0));
        $isCompleted = $progress->status === 'completed'
            || ($quizAllCorrect && (! $hasLab || $rawIndex >= $labQuestions->count()));

        $selectedIndex = $this->resolveSelectedQuestionIndex($request->integer('question', $rawIndex), $progress, $labQuestions);
        $currentQuestion = $labQuestions->get($selectedIndex);
        $currentAnswer = $currentQuestion instanceof LabQuestion
            ? (array) data_get($state, 'answers.'.$currentQuestion->id_lab, [])
            : [];

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
        $canOpenSummary = $isCompleted || ($quizAllCorrect && ! $hasLab);
        $moduleProgress = $this->calculateLearningProgress(
            (bool) $module->module_pdf_path,
            $quizAllCorrect,
            $isCompleted,
            $hasLab,
        );

        return view('mahasiswa.practicum.show', [
            'module' => $module,
            'quizQuestions' => $quizQuestions,
            'quizAnswers' => $quizAnswers,
            'quizAllCorrect' => $quizAllCorrect,
            'correctCount' => $correctCount,
            'questions' => $labQuestions,
            'currentIndex' => $selectedIndex,
            'checkpointIndex' => $checkpointIndex,
            'currentQuestion' => $currentQuestion,
            'currentAnswer' => $currentAnswer,
            'selectedQuestionIndex' => $selectedIndex,
            'codeDraft' => old('code', $currentAnswer['submitted_code'] ?? ''),
            'state' => $state,
            'isCompleted' => $isCompleted,
            'hasLab' => $hasLab,
            'canOpenSummary' => $canOpenSummary,
            'moduleProgress' => $moduleProgress,
            'editorLanguage' => $editorLanguage,
            'editorFilename' => $editorFilename,
            'canContinue' => ! $isCompleted && $selectedIndex <= $checkpointIndex && ($currentAnswer['is_correct'] ?? false),
            'sessionExpiresAt' => $isCompleted ? null : data_get($state, 'session_expires_at'),
            'sessionSignature' => data_get($state, 'session_signature'),
        ]);
    }

    public function submitQuiz(Request $request, Module $module): RedirectResponse
    {
        $user = $request->user();
        $module->load(['quizQuestions']);

        $progress = $this->findProgress($user, $module);
        if ($progress === null) {
            return redirect()->route('mahasiswa.content.index')
                ->with('error', 'Start the module first.');
        }

        $payload = $request->validate([
            'id_quiz' => ['required', 'integer'],
            'selected_option' => ['required', 'in:a,b,c,d'],
        ]);

        $question = $module->quizQuestions->firstWhere('id_quiz', $payload['id_quiz']);
        if (! $question instanceof QuizQuestion) {
            return back()->with('error', 'Question not found.');
        }

        $isCorrect = $payload['selected_option'] === $question->correct_option;

        QuizProgress::query()->updateOrCreate(
            ['id_user' => $user->getKey(), 'id_quiz' => $question->id_quiz],
            ['selected_option' => $payload['selected_option'], 'is_correct' => $isCorrect]
        );

        return redirect()->to(route('mahasiswa.content.show', ['module' => $module, 'view' => 'quiz']).'#q'.$question->id_quiz)
            ->with($isCorrect ? 'success' : 'error', $isCorrect ? 'Jawaban benar!' : 'Jawaban salah, coba lagi.');
    }

    public function run(Request $request, Module $module, DockerService $docker): RedirectResponse
    {
        $user = $request->user();
        $payload = $request->validate([
            'code' => ['required', 'string', 'max:20000'],
            'selected_question_index' => ['nullable', 'integer', 'min:0'],
            'session_expires_at' => ['nullable', 'integer', 'min:1'],
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

        $labQuestions = $module->labQuestions->values();
        $selectedIndex = $this->resolveSelectedQuestionIndex(
            (int) ($payload['selected_question_index'] ?? $progress->current_question_index),
            $progress,
            $labQuestions
        );
        $currentQuestion = $labQuestions->get($selectedIndex);

        if (! $currentQuestion instanceof LabQuestion) {
            return redirect()->route('mahasiswa.content.show', ['module' => $module, 'view' => 'summary'])
                ->with('success', 'This module is complete.');
        }

        $sessionKey = 'practicum.runtime.'.$module->getKey();
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
            ['id_user' => $user->getKey(), 'id_lab' => $currentQuestion->getKey()],
            [
                'submitted_code' => $payload['code'],
                'stdout' => $execution['stdout'],
                'stderr' => $execution['stderr'],
                'is_correct' => $execution['is_correct'],
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

        if ($request->input('reason') === 'timeout') {
            return $this->sessionEndedRedirect($module);
        }

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

        $payload = $request->validate([
            'selected_question_index' => ['nullable', 'integer', 'min:0'],
            'session_expires_at' => ['nullable', 'integer', 'min:1'],
        ]);
        $sessionKey = 'practicum.runtime.'.$module->getKey();
        $runtimeState = $this->normalizeRuntimeState(
            $request->session()->get($sessionKey) ?? [],
            $module
        );
        $request->session()->put($sessionKey, $runtimeState);

        if ($expired = $this->expiredSessionResponse($request, $module, $docker, $runtimeState, $payload['session_expires_at'] ?? null)) {
            return $expired;
        }

        $labQuestions = $module->labQuestions->values();
        $selectedIndex = $this->resolveSelectedQuestionIndex(
            (int) ($payload['selected_question_index'] ?? $progress->current_question_index),
            $progress,
            $labQuestions
        );
        $currentQuestion = $labQuestions->get($selectedIndex);

        if (! $currentQuestion instanceof LabQuestion) {
            return redirect()->route('mahasiswa.content.show', $module)
                ->with('success', 'This module is complete.');
        }

        $answer = QuestionProgress::query()
            ->where('id_user', $user->getKey())
            ->where('id_lab', $currentQuestion->getKey())
            ->first();

        if (! ($answer?->is_correct)) {
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
        abort_unless($module->module_pdf_path, 404);
        $path = storage_path('app/public/'.$module->module_pdf_path);
        abort_unless(file_exists($path), 404);

        return response()->make(
            file_get_contents($path),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="materi.pdf"',
            ]
        );
    }

    private function buildModuleState(User $user, Module $module, ModuleProgress $progress, array $runtimeState = [], Collection $labQuestions = new Collection): array
    {
        $labProgresses = QuestionProgress::query()
            ->where('id_user', $user->getKey())
            ->whereIn('id_lab', $labQuestions->pluck('id_lab'))
            ->get()
            ->keyBy('id_lab');

        $answers = $labProgresses->mapWithKeys(fn (QuestionProgress $qp) => [
            $qp->id_lab => [
                'submitted_code' => $qp->submitted_code,
                'stdout' => $qp->stdout,
                'stderr' => $qp->stderr,
                'is_correct' => $qp->is_correct,
                'executed_at' => optional($qp->updated_at)->toIso8601String(),
            ],
        ])->all();

        return [[
            'runtime' => $this->resolveRuntime($module),
            'status' => $progress->status,
            'current_question_index' => $progress->current_question_index,
            'completed_at' => optional($progress->completed_at)->toIso8601String(),
            'session_expires_at' => $runtimeState['expires_at'] ?? null,
            'session_signature' => $runtimeState['session_key'] ?? ($runtimeState['container_name'] ?? null),
            'answers' => $answers,
            'container_name' => $runtimeState['container_name'] ?? null,
            'container_id' => $runtimeState['container_id'] ?? null,
        ], $labProgresses];
    }

    private function resolveSelectedQuestionIndex(int $requested, ModuleProgress $progress, Collection $questions): int
    {
        if ($questions->isEmpty()) {
            return 0;
        }

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

            $this->copyModuleFileToPythonContainer($module, $runtimeState['container_name'], $docker);

            $docker->writeFileToContainer($runtimeState['container_name'], '/tmp/main.py', $code);
            $result = $docker->runPythonFile($runtimeState['container_name'], '/tmp/main.py');

            return [
                'exit_code' => $result['exit_code'],
                'stdout' => $result['stdout'],
                'stderr' => $result['stderr'],
                'is_correct' => $result['exit_code'] === 0
                    && $this->normalizeOutput($result['stdout']) === $this->normalizeOutput($question->output),
            ];
        }

        if ($runtime === 'mysql') {
            if (empty($runtimeState['container_name'])) {
                $runtimeState = $this->prepareRuntimeState($user, $module, $docker);
            }

            $config = $this->decodeSqlExpectation($question);
            $database = $runtimeState['database'] ?? 'practicum';
            $password = $runtimeState['password'] ?? '';

            $this->resetMysqlDatabase($docker, $runtimeState['container_name'], $database, $password);

            if ($module->file_exe) {
                $schema = $docker->runMysqlScript(
                    $runtimeState['container_name'],
                    $database,
                    $password,
                    Storage::disk('public')->get($module->file_exe),
                );

                if ($schema['exit_code'] !== 0) {
                    return [
                        'exit_code' => $schema['exit_code'],
                        'stdout' => '',
                        'stderr' => 'Schema file failed: '.$schema['stderr'],
                        'is_correct' => false,
                    ];
                }
            }

            if (! empty($config['setup_sql'])) {
                $setup = $docker->runMysqlScript($runtimeState['container_name'], $database, $password, $config['setup_sql']);

                if ($setup['exit_code'] !== 0) {
                    return [
                        'exit_code' => $setup['exit_code'],
                        'stdout' => '',
                        'stderr' => 'Setup SQL failed: '.$setup['stderr'],
                        'is_correct' => false,
                    ];
                }
            }

            if (($config['mode'] ?? 'direct_result') === 'validation_query') {
                $submitted = $docker->runMysqlScript($runtimeState['container_name'], $database, $password, $code);

                if ($submitted['exit_code'] !== 0) {
                    return [
                        'exit_code' => $submitted['exit_code'],
                        'stdout' => $submitted['stdout'],
                        'stderr' => $submitted['stderr'],
                        'is_correct' => false,
                    ];
                }

                $actualRows = $docker->queryMysqlRows(
                    $runtimeState['container_name'],
                    $database,
                    $password,
                    $config['validation_query'] ?? '',
                );
            } else {
                $actualRows = $docker->queryMysqlRows($runtimeState['container_name'], $database, $password, $code);
            }

            $actualRows = $this->normalizeSqlRows($actualRows);
            $expectedRows = $this->normalizeSqlRows($config['expected_result'] ?? []);
            $isCorrect = $this->compareSqlRows($actualRows, $expectedRows, (bool) ($config['order_sensitive'] ?? true));

            return [
                'exit_code' => 0,
                'stdout' => json_encode($actualRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'stderr' => '',
                'is_correct' => $isCorrect,
            ];
        }

        return [
            'exit_code' => 0,
            'stdout' => $code,
            'stderr' => '',
            'is_correct' => $this->normalizeOutput($code) === $this->normalizeOutput($question->output),
        ];
    }

    private function copyModuleFileToPythonContainer(Module $module, string $containerName, DockerService $docker): void
    {
        if (! $module->file_exe || ! Storage::disk('public')->exists($module->file_exe)) {
            return;
        }

        $content = Storage::disk('public')->get($module->file_exe);
        $extension = strtolower(pathinfo($module->file_exe, PATHINFO_EXTENSION));

        $docker->writeFileToContainer($containerName, '/tmp/module-file', $content);

        if ($extension !== '') {
            $docker->writeFileToContainer($containerName, "/tmp/module-file.{$extension}", $content);
            $docker->writeFileToContainer($containerName, "/tmp/module_file.{$extension}", $content);
        }
    }

    private function findProgress(User $user, Module $module): ?ModuleProgress
    {
        return ModuleProgress::query()
            ->where('id_user', $user->getKey())
            ->where('id_module', $module->getKey())
            ->first();
    }

    private function isModuleUnlocked(User $user, Module $module): bool
    {
        $previousModule = Module::query()
            ->where('id_course', $module->id_course)
            ->where('id_module', '<', $module->getKey())
            ->orderByDesc('id_module')
            ->with(['quizQuestions:id_quiz,id_module'])
            ->withCount(['quizQuestions', 'labQuestions'])
            ->first();

        return $previousModule === null || $this->isModuleCompletedForUser($user, $previousModule);
    }

    private function isModuleCompletedForUser(User $user, Module $module): bool
    {
        $progress = ModuleProgress::query()
            ->where('id_user', $user->getKey())
            ->where('id_module', $module->getKey())
            ->first();

        if ($progress?->status === 'completed') {
            return true;
        }

        if ($progress === null || (int) $module->lab_questions_count > 0) {
            return false;
        }

        $questionIds = $module->quizQuestions->pluck('id_quiz');
        if ($questionIds->isEmpty()) {
            return false;
        }

        $correctCount = QuizProgress::query()
            ->where('id_user', $user->getKey())
            ->whereIn('id_quiz', $questionIds)
            ->where('is_correct', true)
            ->count();

        return $correctCount >= $questionIds->count();
    }

    private function resolveRuntime(Module $module): string
    {
        $image = Str::lower((string) optional($module->course)->docker_image);

        return match (true) {
            Str::contains($image, 'python') => 'python',
            Str::contains($image, 'mysql') => 'mysql',
            default => 'text',
        };
    }

    private function prepareRuntimeState(User $user, Module $module, DockerService $docker): array
    {
        $runtime = $this->resolveRuntime($module);
        $state = [
            'runtime' => $runtime,
            'started_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes(max(1, (int) $module->time_limit))->getTimestampMs(),
            'session_key' => (string) Str::uuid(),
        ];

        if ($runtime === 'python') {
            $container = $docker->startPythonContainer(
                sprintf('containearn-u%s-m%s-%s', $user->getKey(), $module->getKey(), Str::lower(Str::random(6))),
                $module->course->docker_image,
            );
            $state['container_id'] = $container['container_id'];
            $state['container_name'] = $container['container_name'];
        }

        if ($runtime === 'mysql') {
            $container = $docker->startMysqlContainer(
                sprintf('containearn-u%s-m%s-%s', $user->getKey(), $module->getKey(), Str::lower(Str::random(6))),
                $module->course->docker_image,
            );
            $state['container_id'] = $container['container_id'];
            $state['container_name'] = $container['container_name'];
            $state['database'] = $container['database'];
            $state['password'] = $container['password'];
        }

        return $state;
    }

    private function remainingSessionSeconds(array $runtimeState, Module $module): int
    {
        if (! empty($runtimeState['expires_at'])) {
            return max(0, (int) ceil(((int) $runtimeState['expires_at'] - now()->getTimestampMs()) / 1000));
        }

        $elapsed = empty($runtimeState['started_at']) ? 0 : now()->diffInSeconds(Carbon::parse($runtimeState['started_at']));

        return max(0, max(1, (int) $module->time_limit) * 60 - $elapsed);
    }

    private function normalizeRuntimeState(array $state, Module $module): array
    {
        $state['runtime'] ??= $this->resolveRuntime($module);
        $state['started_at'] ??= now()->toIso8601String();
        $state['session_key'] ??= $state['container_name'] ?? (string) Str::uuid();
        $state['expires_at'] ??= Carbon::parse($state['started_at'])
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

        if (! $browserExpired && $this->remainingSessionSeconds($runtimeState, $module) > 0) {
            return null;
        }

        $this->destroyRuntimeState($request, $module, $docker);

        return $this->sessionEndedRedirect($module);
    }

    private function sessionEndedRedirect(Module $module): RedirectResponse
    {
        return redirect()
            ->route('mahasiswa.content.show', ['module' => $module, 'view' => 'material'])
            ->with('swal', [
                'icon' => 'warning',
                'title' => 'Session End',
                'text' => 'Session has ended due to time limit. Please start again.',
            ]);
    }

    private function normalizeOutput(string $value): string
    {
        return preg_replace("/\r\n?/", "\n", trim($value)) ?? trim($value);
    }

    private function decodeSqlExpectation(LabQuestion $question): array
    {
        $config = json_decode($question->output, true);

        if (! is_array($config)) {
            throw new \InvalidArgumentException('Expected Output untuk MySQL harus berupa JSON config yang valid.');
        }

        $mode = $config['mode'] ?? (isset($config['validation_query']) ? 'validation_query' : 'direct_result');

        if (! in_array($mode, ['direct_result', 'validation_query'], true)) {
            throw new \InvalidArgumentException('Mode SQL harus direct_result atau validation_query.');
        }

        if ($mode === 'validation_query' && empty($config['validation_query'])) {
            throw new \InvalidArgumentException('validation_query wajib diisi untuk mode validation_query.');
        }

        if (! isset($config['expected_result']) || ! is_array($config['expected_result'])) {
            throw new \InvalidArgumentException('expected_result wajib berupa array JSON.');
        }

        $config['mode'] = $mode;

        return $config;
    }

    private function resetMysqlDatabase(DockerService $docker, string $containerName, string $database, string $password): void
    {
        $quoted = str_replace('`', '``', $database);

        $docker->runMysqlScript(
            $containerName,
            'mysql',
            $password,
            "DROP DATABASE IF EXISTS `{$quoted}`; CREATE DATABASE `{$quoted}`;",
        );
    }

    private function normalizeSqlRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $normalized = collect($row)
                    ->mapWithKeys(fn ($value, string $key) => [$key => $value === null ? null : (string) $value])
                    ->all();

                ksort($normalized);

                return $normalized;
            })
            ->values()
            ->all();
    }

    private function compareSqlRows(array $actualRows, array $expectedRows, bool $orderSensitive): bool
    {
        if (! $orderSensitive) {
            $actualRows = $this->sortSqlRows($actualRows);
            $expectedRows = $this->sortSqlRows($expectedRows);
        }

        return $actualRows === $expectedRows;
    }

    private function sortSqlRows(array $rows): array
    {
        usort($rows, fn (array $a, array $b) => json_encode($a) <=> json_encode($b));

        return $rows;
    }

    private function resetAllRuntimeSessions(Request $request, DockerService $docker): void
    {
        foreach ((array) $request->session()->get('practicum.runtime', []) as $moduleId => $state) {
            if (! empty($state['container_name'])) {
                $docker->destroyContainer($state['container_name']);
            }
            $request->session()->forget("practicum.runtime.{$moduleId}");
        }
    }

    private function destroyRuntimeState(Request $request, Module $module, DockerService $docker): void
    {
        $key = 'practicum.runtime.'.$module->getKey();
        $state = (array) $request->session()->get($key, []);

        if (! empty($state['container_name'])) {
            $docker->destroyContainer($state['container_name']);
        }

        $request->session()->forget($key);
    }

    private function decorateCourseModules(Course $course, Collection $progresses, Collection $quizProgresses, Collection $labProgresses): Course
    {
        $previousCompleted = true;

        $course->setRelation(
            'modules',
            $course->modules
                ->sortBy('id_module')
                ->values()
                ->map(function (Module $module) use ($progresses, $quizProgresses, &$previousCompleted) {
                    $progress = $progresses->get($module->getKey());
                    $quizTotal = (int) $module->quiz_questions_count;
                    $labTotal = (int) $module->lab_questions_count;
                    $correctCount = $module->quizQuestions
                        ->filter(fn (QuizQuestion $question) => $quizProgresses->has($question->id_quiz))
                        ->count();
                    $quizDone = $quizTotal > 0 && $correctCount >= $quizTotal;
                    $completed = $progress?->status === 'completed' || ($progress !== null && $labTotal === 0 && $quizDone);

                    $status = match (true) {
                        $completed => 'completed',
                        $progress?->status === 'in_progress' => 'in_progress',
                        $previousCompleted => 'available',
                        default => 'locked',
                    };

                    $module->setAttribute('practicum_status', $status);
                    $module->setAttribute('practicum_progress', $progress);
                    $module->setAttribute('learning_progress_percent', $this->calculateLearningProgress(
                        $progress !== null && (bool) $module->module_pdf_path,
                        $quizDone,
                        $completed,
                        $labTotal > 0,
                    ));
                    $module->setAttribute('quiz_correct_count', $correctCount);
                    $previousCompleted = $completed;

                    return $module;
                })
        );

        return $course;
    }

    private function calculateLearningProgress(
        bool $materialDone,
        bool $quizDone,
        bool $isCompleted,
        bool $hasLab,
    ): int {
        $steps = [
            $materialDone,
            $quizDone,
        ];

        if ($hasLab) {
            $steps[] = $isCompleted;
        }

        return (int) round((collect($steps)->filter()->count() / count($steps)) * 100);
    }
}