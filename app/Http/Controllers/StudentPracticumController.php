<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\Question;
use App\Models\QuestionProgress;
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
            ->with(['modules' => fn ($q) => $q->withCount('questions')->orderBy('id_module')])
            ->orderBy('id_course')
            ->get();

        $progresses = ModuleProgress::query()
            ->where('user_id', $user->getKey())
            ->get()
            ->keyBy('module_id');

        $courses = $courses
            ->map(fn (Course $c) => $this->decorateCourseModules($c, $progresses))
            ->values();

        return view('mahasiswa.practicum.index', [
            'courses'      => $courses
        ]);
    }

    public function start(Request $request, Module $module, DockerService $docker): RedirectResponse
    {
        $user = $request->user();
        $module->load(['course', 'questions' => fn ($q) => $q->orderBy('id_question')]);

        abort_if($module->questions->isEmpty(), 404, 'Module does not have any questions yet.');

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

        if ($progress->status !== 'completed') {
            $existing = $request->session()->get('practicum.runtime.' . $module->getKey());
            $existing = is_array($existing) ? $existing : null;

            if ($existing && $this->remainingSessionSeconds($existing, $module) > 0) {
                return redirect()->route('mahasiswa.content.show', $module)
                    ->with('success', 'Progress resumed.');
            }
        }

        $this->resetAllRuntimeSessions($request, $docker);

        if ($progress->status !== 'completed') {
            try {
                $state = $this->prepareRuntimeState($user, $module, $docker);
                $request->session()->put('practicum.runtime.' . $module->getKey(), $state);
            } catch (Throwable $e) {
                return redirect()->route('mahasiswa.content.index')
                    ->with('error', 'Failed to prepare the lab container: ' . $e->getMessage());
            }
        }

        return redirect()->route('mahasiswa.content.show', $module)
            ->with('success', $progress->wasRecentlyCreated ? 'Module started.' : 'Progress resumed.');
    }

    public function show(Request $request, Module $module, DockerService $docker): View|RedirectResponse
    {
        $user = $request->user();
        $module->load(['course', 'questions' => fn ($q) => $q->orderBy('id_question')]);

        $progress = $this->findProgress($user, $module);

        if ($progress === null) {
            if (!$this->isModuleUnlocked($user, $module)) {
                return redirect()->route('mahasiswa.content.index')
                    ->with('error', 'Complete the previous module first.');
            }
            return redirect()->route('mahasiswa.content.index')
                ->with('error', 'Start a module first.');
        }

        $runtimeState = [];

        if ($progress->status !== 'completed') {
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

        [$state, $questionProgresses] = $this->buildModuleState($user, $module, $progress, $runtimeState);

        $questions             = $module->questions->values();
        $rawIndex              = (int) ($progress->current_question_index ?? 0);
        $checkpointIndex       = min($rawIndex, max($questions->count() - 1, 0));
        $isCompleted           = $progress->status === 'completed' || $questions->isEmpty() || $rawIndex >= $questions->count();
        $selectedIndex         = $this->resolveSelectedQuestionIndex($request->integer('question', $rawIndex), $progress, $questions);
        $currentQuestion       = $questions->get($selectedIndex);
        $currentAnswer         = $currentQuestion instanceof Question
            ? (array) data_get($state, 'answers.' . $currentQuestion->id_question, [])
            : [];

        return view('mahasiswa.practicum.show', [
            'module'                => $module,
            'questions'             => $questions,
            'currentIndex'          => $selectedIndex,
            'checkpointIndex'       => $checkpointIndex,
            'currentQuestion'       => $currentQuestion,
            'currentAnswer'         => $currentAnswer,
            'selectedQuestionIndex' => $selectedIndex,
            'codeDraft'             => old('code', $currentAnswer['submitted_code'] ?? ''),
            'state'                 => $state,
            'isCompleted'           => $isCompleted,
            'correctCount'          => $questionProgresses->where('is_correct', true)->count(),
            'canContinue'           => !$isCompleted && $selectedIndex === $checkpointIndex && ($currentAnswer['is_correct'] ?? false),
            'sessionExpiresAt'      => $isCompleted ? null : data_get($state, 'session_expires_at'),
            'sessionSignature'      => data_get($state, 'session_signature'),
        ]);
    }

    public function run(Request $request, Module $module, DockerService $docker): RedirectResponse
    {
        $user = $request->user();
        $payload = $request->validate([
            'code'                    => ['required', 'string', 'max:20000'],
            'selected_question_index' => ['nullable', 'integer', 'min:0'],
            'session_expires_at'      => ['nullable', 'integer', 'min:1'],
        ]);

        $module->load(['course', 'questions' => fn ($q) => $q->orderBy('id_question')]);
        $progress = $this->findProgress($user, $module);

        if ($progress === null) {
            return redirect()->route('mahasiswa.content.index')
                ->with('error', 'Session not found. Start the module again.');
        }

        if ($progress->status === 'completed') {
            return redirect()->route('mahasiswa.content.show', $module)
                ->with('success', 'This module is complete.');
        }

        $questions       = $module->questions->values();
        $selectedIndex   = $this->resolveSelectedQuestionIndex(
            (int) ($payload['selected_question_index'] ?? $progress->current_question_index),
            $progress, $questions
        );
        $currentQuestion = $questions->get($selectedIndex);

        if (!$currentQuestion instanceof Question) {
            return redirect()->route('mahasiswa.content.show', $module)
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

        $existing = QuestionProgress::query()
            ->where('user_id', $user->getKey())
            ->where('question_id', $currentQuestion->getKey())
            ->first();

        QuestionProgress::query()->updateOrCreate(
            ['user_id' => $user->getKey(), 'question_id' => $currentQuestion->getKey()],
            [
                'submitted_code' => $payload['code'],
                'stdout'         => $execution['stdout'],
                'stderr'         => $execution['stderr'],
                'is_correct'     => ($existing?->is_correct ?? false) || $execution['is_correct'],
            ]
        );

        return redirect()->route('mahasiswa.content.show', ['module' => $module, 'question' => $selectedIndex])
            ->with(
                $execution['is_correct'] ? 'success' : 'error',
                $execution['is_correct'] ? 'Correct. Review your output, then continue.' : 'Output does not match yet.'
            );
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
        $module->load(['course', 'questions' => fn ($q) => $q->orderBy('id_question')]);

        $progress = $this->findProgress($user, $module);

        if ($progress === null) {
            return redirect()->route('mahasiswa.content.index')
                ->with('error', 'Session not found. Start the module again.');
        }

        if ($progress->status === 'completed') {
            return redirect()->route('mahasiswa.content.show', $module)
                ->with('success', 'This module is complete.');
        }

        $payload      = $request->validate(['session_expires_at' => ['nullable', 'integer', 'min:1']]);
        $sessionKey   = 'practicum.runtime.' . $module->getKey();
        $runtimeState = $this->normalizeRuntimeState(
            $request->session()->get($sessionKey) ?? [],
            $module
        );
        $request->session()->put($sessionKey, $runtimeState);

        if ($expired = $this->expiredSessionResponse($request, $module, $docker, $runtimeState, $payload['session_expires_at'] ?? null)) {
            return $expired;
        }

        $questions       = $module->questions->values();
        $currentQuestion = $questions->get((int) $progress->current_question_index);

        if (!$currentQuestion instanceof Question) {
            return redirect()->route('mahasiswa.content.show', $module)
                ->with('success', 'This module is complete.');
        }

        $answer = QuestionProgress::query()
            ->where('user_id', $user->getKey())
            ->where('question_id', $currentQuestion->getKey())
            ->first();

        if (!($answer?->is_correct)) {
            return redirect()->route('mahasiswa.content.show', $module)
                ->with('error', 'Continue is available after a correct answer.');
        }

        $nextIndex = $progress->current_question_index + 1;

        if ($nextIndex >= $questions->count()) {
            $progress->update(['status' => 'completed', 'current_question_index' => $questions->count(), 'completed_at' => now()]);
            $this->destroyRuntimeState($request, $module, $docker);
        } else {
            $progress->update(['status' => 'in_progress', 'current_question_index' => $nextIndex]);
        }

        return redirect()->route('mahasiswa.content.show', $module)
            ->with('success', $nextIndex >= $questions->count() ? 'Module completed.' : 'Moved to the next question.');
    }

    private function decorateCourseModules(Course $course, Collection $progresses): Course
    {
        $previousCompleted = true;

        $course->setRelation('modules', $course->modules
            ->sortBy('id_module')
            ->values()
            ->map(function (Module $module) use ($progresses, &$previousCompleted) {
                $progress = $progresses->get($module->getKey());
                $status   = match (true) {
                    $progress?->status === 'completed'   => 'completed',
                    $progress?->status === 'in_progress' => 'in_progress',
                    $previousCompleted                   => 'available',
                    default                              => 'locked',
                };

                $module->setAttribute('practicum_status', $status);
                $module->setAttribute('practicum_progress', $progress);
                $previousCompleted = $progress?->status === 'completed';

                return $module;
            })
        );

        return $course;
    }

    private function buildModuleState(User $user, Module $module, ModuleProgress $progress, array $runtimeState = []): array
    {
        $questionProgresses = QuestionProgress::query()
            ->where('user_id', $user->getKey())
            ->whereIn('question_id', $module->questions->pluck('id_question'))
            ->get()
            ->keyBy('question_id');

        $answers = $questionProgresses->mapWithKeys(fn (QuestionProgress $qp) => [
            $qp->question_id => [
                'submitted_code' => $qp->submitted_code,
                'stdout'         => $qp->stdout,
                'stderr'         => $qp->stderr,
                'is_correct'     => $qp->is_correct,
                'executed_at'    => optional($qp->updated_at)->toIso8601String(),
            ],
        ])->all();

        return [[
            'runtime'              => $this->resolveRuntime($module),
            'status'               => $progress->status,
            'current_question_index' => $progress->current_question_index,
            'completed_at'         => optional($progress->completed_at)->toIso8601String(),
            'session_expires_at'   => $runtimeState['expires_at'] ?? null,
            'session_signature'    => $runtimeState['session_key'] ?? ($runtimeState['container_name'] ?? null),
            'answers'              => $answers,
            'container_name'       => $runtimeState['container_name'] ?? null,
            'container_id'         => $runtimeState['container_id'] ?? null,
        ], $questionProgresses];
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
        Module $module, Question $question,
        string $code, array &$runtimeState,
        DockerService $docker, User $user,
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
            'stdout' => $code,
            'stderr' => '',
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
        $previousId = Module::query()
            ->where('id_course', $module->id_course)
            ->where('id_module', '<', $module->getKey())
            ->orderByDesc('id_module')
            ->value('id_module');

        return $previousId === null || ModuleProgress::query()
            ->where('user_id', $user->getKey())
            ->where('module_id', $previousId)
            ->where('status', 'completed')
            ->exists();
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
            'runtime'    => $runtime,
            'started_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes(max(1, (int) $module->time_limit))->getTimestampMs(),
            'session_key'=> (string) Str::uuid(),
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
        Request $request, Module $module, DockerService $docker,
        array $runtimeState, ?int $browserExpiresAt = null,
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
}
