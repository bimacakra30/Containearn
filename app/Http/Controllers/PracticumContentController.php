<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Module;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PracticumContentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdminAccess($request);

        $courses = Course::query()
            ->with([
                'modules' => fn ($query) => $query
                    ->with(['questions' => fn ($questionQuery) => $questionQuery->orderBy('id_question')])
                    ->orderBy('id_module'),
            ])
            ->withCount('modules')
            ->orderBy('id_course')
            ->get();

        $questionCount = $courses->sum(
            fn (Course $course) => $course->modules->sum(fn ($module) => $module->questions->count())
        );

        return view('admin.contents', [
            'courses' => $courses,
            'questionCount' => $questionCount,
        ]);
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        Course::create($this->validateCourse($request));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Course created successfully.');
    }

    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $course->update($this->validateCourse($request));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroyCourse(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $course->delete();

        return redirect()->route('admin.contents.index')
            ->with('success', 'Course deleted successfully.');
    }

    public function storeModule(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $course->modules()->create($this->validateModule($request));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Module created successfully.');
    }

    public function updateModule(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $module->update($this->validateModule($request));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Module updated successfully.');
    }

    public function destroyModule(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $module->delete();

        return redirect()->route('admin.contents.index')
            ->with('success', 'Module deleted successfully.');
    }

    public function storeQuestion(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $module->questions()->create($this->validateQuestion($request));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Question created successfully.');
    }

    public function updateQuestion(Request $request, Question $question): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $question->update($this->validateQuestion($request));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Question updated successfully.');
    }

    public function destroyQuestion(Request $request, Question $question): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $question->delete();

        return redirect()->route('admin.contents.index')
            ->with('success', 'Question deleted successfully.');
    }

    private function authorizeAdminAccess(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor?->isAdmin(), 403, 'You do not have access to this page.');

        return $actor;
    }

    private function validateCourse(Request $request): array
    {
        $validated = $request->validate([
            'course_title' => ['required', 'string', 'max:255'],
            'docker_image' => ['required', 'string', 'max:255'],
            'form_scope' => ['nullable', 'string'],
            'course_context_id' => ['nullable', 'integer'],
            'module_context_id' => ['nullable', 'integer'],
            'question_context_id' => ['nullable', 'integer'],
        ]);

        return [
            'course_title' => $validated['course_title'],
            'docker_image' => $validated['docker_image'],
        ];
    }

    private function validateModule(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'time_limit' => ['required', 'integer', 'min:1', 'max:1440'],
            'form_scope' => ['nullable', 'string'],
            'course_context_id' => ['nullable', 'integer'],
            'module_context_id' => ['nullable', 'integer'],
            'question_context_id' => ['nullable', 'integer'],
        ]);

        return [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'time_limit' => $validated['time_limit'],
        ];
    }

    private function validateQuestion(Request $request): array
    {
        $validated = $request->validate([
            'question' => ['required', 'string'],
            'output' => ['required', 'string'],
            'form_scope' => ['nullable', 'string'],
            'course_context_id' => ['nullable', 'integer'],
            'module_context_id' => ['nullable', 'integer'],
            'question_context_id' => ['nullable', 'integer'],
        ]);

        return [
            'question' => $validated['question'],
            'output' => $validated['output'],
        ];
    }
}
