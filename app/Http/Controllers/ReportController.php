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
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $selectedClass = $request->query('class');
        $selectedCourse = $request->integer('course');

        $classOptions = collect(['A', 'B', 'C', 'D'])->merge(User::query()
            ->where('role', 'mahasiswa')
            ->whereNotNull('class')
            ->distinct()
            ->orderBy('class')
            ->pluck('class'))
            ->unique()
            ->values();

        $coursesQuery = Course::query()
            ->with(['modules' => fn($query) => $query
                ->with(['quizQuestions:id_quiz,id_module', 'labQuestions:id_lab,id_module'])
                ->withCount(['quizQuestions', 'labQuestions'])
                ->orderBy('id_module')])
            ->orderBy('id_course');

        if ($selectedCourse > 0) {
            $coursesQuery->where('id_course', $selectedCourse);
        }

        $courses = $coursesQuery->get();
        $allCourses = Course::query()->orderBy('id_course')->get(['id_course', 'course_title']);

        $students = User::query()
            ->where('role', 'mahasiswa')
            ->when($selectedClass, fn($query) => $query->where('class', $selectedClass))
            ->orderByRaw('LENGTH(identity_id)')
            ->orderBy('identity_id')
            ->get();

        $moduleIds = $courses->flatMap(fn(Course $course) => $course->modules->pluck('id_module'))->values();
        $quizQuestionIds = $courses
            ->flatMap(fn(Course $course) => $course->modules)
            ->flatMap(fn(Module $module) => $module->quizQuestions->pluck('id_quiz'))
            ->values();
        $labQuestionIds = $courses
            ->flatMap(fn(Course $course) => $course->modules)
            ->flatMap(fn(Module $module) => $module->labQuestions->pluck('id_lab'))
            ->values();
        $studentIds = $students->pluck('id_user');

        $moduleProgresses = ModuleProgress::query()
            ->whereIn('id_user', $studentIds)
            ->whereIn('id_module', $moduleIds)
            ->get()
            ->groupBy('id_user')
            ->map(fn(Collection $items) => $items->keyBy('id_module'));

        $latestAttemptIds = \App\Models\QuizAttempt::query()
            ->whereIn('id_user', $studentIds)
            ->whereIn('id_module', $moduleIds)
            ->orderByDesc('attempt_number')
            ->get()
            ->groupBy(fn($attempt) => $attempt->id_user . '-' . $attempt->id_module)
            ->map(function ($attempts) {
                $active = $attempts->firstWhere(fn($a) => is_null($a->submitted_at));
                return $active ? $active->id_attempt : $attempts->first()->id_attempt;
            })
            ->values();

        $quizProgresses = QuizProgress::query()
            ->whereIn('id_attempt', $latestAttemptIds)
            ->whereIn('id_quiz', $quizQuestionIds)
            ->where('is_correct', true)
            ->get()
            ->groupBy('id_user');

        $labProgresses = QuestionProgress::query()
            ->whereIn('id_user', $studentIds)
            ->whereIn('id_lab', $labQuestionIds)
            ->where('is_correct', true)
            ->get()
            ->groupBy('id_user');

        $completedAttempts = \App\Models\QuizAttempt::query()
            ->whereIn('id_user', $studentIds)
            ->whereIn('id_module', $moduleIds)
            ->whereNotNull('submitted_at')
            ->get()
            ->groupBy('id_user')
            ->map(fn(Collection $items) => $items->keyBy('id_module'));

        $reports = $students->map(function (User $student) use ($courses, $moduleProgresses, $quizProgresses, $labProgresses, $completedAttempts): array {
            $studentModuleProgresses = $moduleProgresses->get($student->getKey(), collect());
            $studentQuizProgresses = $quizProgresses->get($student->getKey(), collect())->keyBy('id_quiz');
            $studentLabProgresses = $labProgresses->get($student->getKey(), collect())->keyBy('id_lab');
            $studentCompletedAttempts = $completedAttempts->get($student->getKey(), collect());

            $courseReports = $courses->map(function (Course $course) use ($studentModuleProgresses, $studentQuizProgresses, $studentLabProgresses, $studentCompletedAttempts): array {
                $moduleReports = $course->modules->map(function (Module $module) use ($studentModuleProgresses, $studentQuizProgresses, $studentLabProgresses, $studentCompletedAttempts): array {
                    $progress = $studentModuleProgresses->get($module->getKey());
                    $quizTotal = (int) $module->quiz_questions_count;
                    $labTotal = (int) $module->lab_questions_count;
                    $quizCorrect = $module->quizQuestions
                        ->filter(fn(QuizQuestion $question) => $studentQuizProgresses->has($question->id_quiz))
                        ->count();
                    $labCorrect = $module->labQuestions
                        ->filter(fn(LabQuestion $question) => $studentLabProgresses->has($question->id_lab))
                        ->count();

                    $hasCompletedAttempt = $studentCompletedAttempts->has($module->getKey());
                    $quizDone = $quizTotal === 0 || $hasCompletedAttempt;
                    $completed = $progress?->status === 'completed' || ($progress !== null && $labTotal === 0 && $quizDone);
                    $hasActivity = $progress !== null || $hasCompletedAttempt || $labCorrect > 0;

                    return [
                        'module' => $module,
                        'percent' => $hasActivity
                            ? $this->calculateLearningProgress((bool) $module->module_pdf_path, $quizDone, $completed, $labTotal > 0)
                            : 0,
                        'status' => $completed ? 'completed' : ($progress?->status ?? 'not_started'),
                        'quiz_correct' => $quizCorrect,
                        'quiz_total' => $quizTotal,
                        'quiz_percent' => $quizTotal > 0 ? (int) round(($quizCorrect / $quizTotal) * 100) : 0,
                        'quiz_wrong_percent' => $quizTotal > 0 ? (int) round((($quizTotal - $quizCorrect) / $quizTotal) * 100) : 0,
                        'lab_correct' => $labCorrect,
                        'lab_total' => $labTotal,
                    ];
                })->values();

                return [
                    'course' => $course,
                    'percent' => (int) round($moduleReports->avg('percent') ?? 0),
                    'module' => $moduleReports,
                ];
            })->values();

            return [
                'student' => $student,
                'overall_percent' => (int) round($courseReports->avg('percent') ?? 0),
                'course' => $courseReports,
            ];
        })->values();

        return view('admin.reports', [
            'allCourses' => $allCourses,
            'classOptions' => $classOptions,
            'courses' => $courses,
            'reports' => $reports,
            'selectedClass' => $selectedClass,
            'selectedCourse' => $selectedCourse,
            'studentCount' => $students->count(),
        ]);
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
