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
            ->with(['modules' => fn ($query) => $query
                ->with(['questions:id_question,id_module', 'labQuestions:id_question,id_module'])
                ->withCount(['questions', 'labQuestions'])
                ->orderBy('id_module')])
            ->orderBy('id_course');

        if ($selectedCourse > 0) {
            $coursesQuery->where('id_course', $selectedCourse);
        }

        $courses = $coursesQuery->get();
        $allCourses = Course::query()->orderBy('id_course')->get(['id_course', 'course_title']);

        $students = User::query()
            ->where('role', 'mahasiswa')
            ->when($selectedClass, fn ($query) => $query->where('class', $selectedClass))
            ->orderByRaw('LENGTH(identity_id)')
            ->orderBy('identity_id')
            ->orderBy('name')
            ->get();

        $moduleIds = $courses->flatMap(fn (Course $course) => $course->modules->pluck('id_module'))->values();
        $quizQuestionIds = $courses
            ->flatMap(fn (Course $course) => $course->modules)
            ->flatMap(fn (Module $module) => $module->questions->pluck('id_question'))
            ->values();
        $labQuestionIds = $courses
            ->flatMap(fn (Course $course) => $course->modules)
            ->flatMap(fn (Module $module) => $module->labQuestions->pluck('id_question'))
            ->values();
        $studentIds = $students->pluck('id');

        $moduleProgresses = ModuleProgress::query()
            ->whereIn('user_id', $studentIds)
            ->whereIn('module_id', $moduleIds)
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $items) => $items->keyBy('module_id'));

        $quizProgresses = QuizProgress::query()
            ->whereIn('user_id', $studentIds)
            ->whereIn('question_id', $quizQuestionIds)
            ->where('is_correct', true)
            ->get()
            ->groupBy('user_id');

        $labProgresses = QuestionProgress::query()
            ->whereIn('user_id', $studentIds)
            ->whereIn('lab_question_id', $labQuestionIds)
            ->where('is_correct', true)
            ->get()
            ->groupBy('user_id');

        $reports = $students->map(function (User $student) use ($courses, $moduleProgresses, $quizProgresses, $labProgresses): array {
            $studentModuleProgresses = $moduleProgresses->get($student->getKey(), collect());
            $studentQuizProgresses = $quizProgresses->get($student->getKey(), collect())->keyBy('question_id');
            $studentLabProgresses = $labProgresses->get($student->getKey(), collect())->keyBy('lab_question_id');

            $courseReports = $courses->map(function (Course $course) use ($studentModuleProgresses, $studentQuizProgresses, $studentLabProgresses): array {
                $moduleReports = $course->modules->map(function (Module $module) use ($studentModuleProgresses, $studentQuizProgresses, $studentLabProgresses): array {
                    $progress = $studentModuleProgresses->get($module->getKey());
                    $quizTotal = (int) $module->questions_count;
                    $labTotal = (int) $module->lab_questions_count;
                    $quizCorrect = $module->questions
                        ->filter(fn (Question $question) => $studentQuizProgresses->has($question->id_question))
                        ->count();
                    $labCorrect = $module->labQuestions
                        ->filter(fn (LabQuestion $question) => $studentLabProgresses->has($question->id_question))
                        ->count();

                    $quizDone = $quizTotal > 0 && $quizCorrect >= $quizTotal;
                    $completed = $progress?->status === 'completed' || ($progress !== null && $labTotal === 0 && $quizDone);
                    $hasActivity = $progress !== null || $quizCorrect > 0 || $labCorrect > 0;

                    return [
                        'module' => $module,
                        'percent' => $hasActivity
                            ? $this->calculateLearningProgress((bool) $module->material_pdf_path, $quizDone, $completed, $labTotal > 0)
                            : 0,
                        'status' => $completed ? 'completed' : ($progress?->status ?? 'not_started'),
                        'quiz_correct' => $quizCorrect,
                        'quiz_total' => $quizTotal,
                        'lab_correct' => $labCorrect,
                        'lab_total' => $labTotal,
                    ];
                })->values();

                return [
                    'course' => $course,
                    'percent' => (int) round($moduleReports->avg('percent') ?? 0),
                    'modules' => $moduleReports,
                ];
            })->values();

            return [
                'student' => $student,
                'overall_percent' => (int) round($courseReports->avg('percent') ?? 0),
                'courses' => $courseReports,
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
