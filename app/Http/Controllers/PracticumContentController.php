<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LabQuestion;
use App\Models\Module;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PracticumContentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdminAccess($request);

        $courses = Course::query()
            ->with([
                'modules' => fn ($query) => $query
                    ->with([
                        'questions' => fn ($questionQuery) => $questionQuery->orderBy('order')->orderBy('id_question'),
                        'labQuestions' => fn ($questionQuery) => $questionQuery->orderBy('id_question'),
                    ])
                    ->orderBy('id_module'),
            ])
            ->withCount('modules')
            ->orderBy('id_course')
            ->get();

        $questionCount = $courses->sum(
            fn (Course $course) => $course->modules->sum(fn ($module) => $module->questions->count())
        );
        $labQuestionCount = $courses->sum(
            fn (Course $course) => $course->modules->sum(fn ($module) => $module->labQuestions->count())
        );

        return view('admin.contents', [
            'courses' => $courses,
            'questionCount' => $questionCount,
            'labQuestionCount' => $labQuestionCount,
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

        $module->update($this->validateModule($request, $module));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Module updated successfully.');
    }

    public function destroyModule(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $this->deleteModuleMaterial($module);
        $module->delete();

        return redirect()->route('admin.contents.index')
            ->with('success', 'Module deleted successfully.');
    }

    public function storeQuestion(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $payload = $this->validateQuestion($request);
        $payload['order'] ??= ((int) $module->questions()->max('order')) + 1;

        $module->questions()->create($payload);

        return redirect()->route('admin.contents.index')
            ->with('success', 'Quiz question created successfully.');
    }

    public function updateQuestion(Request $request, Question $question): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $question->update($this->validateQuestion($request));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Quiz question updated successfully.');
    }

    public function destroyQuestion(Request $request, Question $question): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $question->delete();

        return redirect()->route('admin.contents.index')
            ->with('success', 'Quiz question deleted successfully.');
    }

    public function storeLabQuestion(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $module->labQuestions()->create($this->validateLabQuestion($request));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Lab question created successfully.');
    }

    public function updateLabQuestion(Request $request, LabQuestion $labQuestion): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $labQuestion->update($this->validateLabQuestion($request));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Lab question updated successfully.');
    }

    public function destroyLabQuestion(Request $request, LabQuestion $labQuestion): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $labQuestion->delete();

        return redirect()->route('admin.contents.index')
            ->with('success', 'Lab question deleted successfully.');
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

    private function validateModule(Request $request, ?Module $module = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'material_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'time_limit' => ['required', 'integer', 'min:1', 'max:1440'],
            'form_scope' => ['nullable', 'string'],
            'course_context_id' => ['nullable', 'integer'],
            'module_context_id' => ['nullable', 'integer'],
            'question_context_id' => ['nullable', 'integer'],
        ]);

        $payload = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'time_limit' => $validated['time_limit'],
        ];

        if ($request->hasFile('material_pdf')) {
            if ($module !== null) {
                $this->deleteModuleMaterial($module);
            }

            $payload['material_pdf_path'] = $request
                ->file('material_pdf')
                ->store('module-materials', 'public');
        }

        return $payload;
    }

    private function validateQuestion(Request $request): array
    {
        $validated = $request->validate([
            'question' => ['required', 'string'],
            'option_a' => ['required', 'string', 'max:255'],
            'option_b' => ['required', 'string', 'max:255'],
            'option_c' => ['required', 'string', 'max:255'],
            'option_d' => ['required', 'string', 'max:255'],
            'correct_option' => ['required', 'in:a,b,c,d'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'form_scope' => ['nullable', 'string'],
            'course_context_id' => ['nullable', 'integer'],
            'module_context_id' => ['nullable', 'integer'],
            'question_context_id' => ['nullable', 'integer'],
        ]);

        $payload = [
            'question' => $validated['question'],
            'option_a' => $validated['option_a'],
            'option_b' => $validated['option_b'],
            'option_c' => $validated['option_c'],
            'option_d' => $validated['option_d'],
            'correct_option' => $validated['correct_option'],
        ];

        if (array_key_exists('order', $validated) && $validated['order'] !== null) {
            $payload['order'] = $validated['order'];
        }

        return $payload;
    }

    private function validateLabQuestion(Request $request): array
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

    private function deleteModuleMaterial(Module $module): void
    {
        if ($module->material_pdf_path) {
            Storage::disk('public')->delete($module->material_pdf_path);
        }
    }
}
