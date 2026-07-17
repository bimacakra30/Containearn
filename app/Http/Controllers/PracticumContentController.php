<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LabQuestion;
use App\Models\Module;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
                        'quizQuestions' => fn ($questionQuery) => $questionQuery->orderBy('id_quiz'),
                        'labQuestions' => fn ($questionQuery) => $questionQuery->orderBy('id_lab'),
                    ])
                    ->orderBy('id_module'),
            ])
            ->withCount('modules')
            ->orderBy('id_course')
            ->get();

        $questionCount = $courses->sum(
            fn (Course $course) => $course->modules->sum(fn ($module) => $module->quizQuestions->count())
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

        $this->deleteModuleFiles($module);
        $module->delete();

        return redirect()->route('admin.contents.index')
            ->with('success', 'Module deleted successfully.');
    }

    public function storeQuestion(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $module->quizQuestions()->create($this->validateQuizQuestion($request));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Quiz question created successfully.');
    }

    public function updateQuestion(Request $request, QuizQuestion $question): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $question->update($this->validateQuizQuestion($request));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Quiz question updated successfully.');
    }

    public function destroyQuestion(Request $request, QuizQuestion $question): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $question->delete();

        return redirect()->route('admin.contents.index')
            ->with('success', 'Quiz question deleted successfully.');
    }

    public function storeLabQuestion(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $module->load('course');
        $module->labQuestions()->create($this->validateLabQuestion($request, $module));

        return redirect()->route('admin.contents.index')
            ->with('success', 'Lab question created successfully.');
    }

    public function updateLabQuestion(Request $request, LabQuestion $labQuestion): RedirectResponse
    {
        $this->authorizeAdminAccess($request);

        $labQuestion->load('module.course');
        $labQuestion->update($this->validateLabQuestion($request, $labQuestion->module));

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
            'course_title' => ['required', 'string', 'max:50'],
            'docker_image' => ['required', 'string', 'max:25'],
        ]);

        return [
            'course_title' => $validated['course_title'],
            'docker_image' => $validated['docker_image'],
        ];
    }

    private function validateModule(Request $request, ?Module $module = null): array
    {
        $validated = $request->validate([
            'title'              => ['required', 'string', 'max:50'],
            'description'        => ['required', 'string'],
            'material_pdf'       => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'file_exe'           => ['nullable', 'file', 'max:10240'],
            'time_limit'         => ['required', 'integer', 'min:1', 'max:1440'],
            'quiz_time_limit'    => ['nullable', 'integer', 'min:1', 'max:300'],
            'quiz_max_attempts'  => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $payload = [
            'module_title'      => $validated['title'],
            'description'       => $validated['description'],
            'time_limit'        => $validated['time_limit'],
            'quiz_time_limit'   => $validated['quiz_time_limit'] ?? null,
            'quiz_max_attempts' => $validated['quiz_max_attempts'],
        ];

        if ($request->hasFile('material_pdf')) {
            if ($module !== null) {
                $this->deleteModuleMaterial($module);
            }

            $payload['module_pdf_path'] = $request
                ->file('material_pdf')
                ->storeAs('m', Str::random(12).'.pdf', 'public');
        }

        if ($request->hasFile('file_exe')) {
            if ($module !== null) {
                $this->deleteModuleExecutable($module);
            }

            $payload['file_exe'] = $request
                ->file('file_exe')
                ->store('module-files', 'public');
        }

        return $payload;
    }

    private function validateQuizQuestion(Request $request): array
    {
        $validated = $request->validate([
            'question' => ['required', 'string',],
            'option_a' => ['required', 'string', 'max:200'],
            'option_b' => ['required', 'string', 'max:200'],
            'option_c' => ['required', 'string', 'max:200'],
            'option_d' => ['required', 'string', 'max:200'],
            'correct_option' => ['required', 'in:a,b,c,d'],
        ]);

        $payload = [
            'question' => $validated['question'],
            'option_a' => $validated['option_a'],
            'option_b' => $validated['option_b'],
            'option_c' => $validated['option_c'],
            'option_d' => $validated['option_d'],
            'correct_option' => $validated['correct_option'],
        ];

        return $payload;
    }

    private function validateLabQuestion(Request $request, ?Module $module = null): array
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:150'],
            'output' => ['required', 'string'],
            'sql_mode' => ['nullable', 'in:direct_result,validation_query'],
            'validation_query' => ['nullable', 'string'],
            'order_sensitive' => ['nullable', 'boolean'],
        ]);

        if ($this->isMysqlModule($module)) {
            return [
                'question' => $validated['question'],
                'output' => $this->buildSqlValidationOutput($validated),
            ];
        }

        return [
            'question' => $validated['question'],
            'output' => $validated['output'],
        ];
    }

    private function isMysqlModule(?Module $module): bool
    {
        return str_contains(strtolower((string) optional($module?->course)->docker_image), 'mysql');
    }

    private function buildSqlValidationOutput(array $validated): string
    {
        $mode = $validated['sql_mode'] ?? 'direct_result';

        if ($mode === 'validation_query' && trim((string) ($validated['validation_query'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'validation_query' => 'Validation query wajib diisi untuk mode validation query.',
            ]);
        }

        return json_encode([
            'mode' => $mode,
            'validation_query' => $mode === 'validation_query'
                ? trim((string) $validated['validation_query'])
                : null,
            'expected_result' => $this->parseSqlExpectedRows($validated['output']),
            'order_sensitive' => (bool) ($validated['order_sensitive'] ?? false),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function parseSqlExpectedRows(string $value): array
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', trim($value)) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->values();

        if ($lines->count() < 2) {
            throw ValidationException::withMessages([
                'output' => 'Expected result MySQL minimal berisi header dan satu baris data.',
            ]);
        }

        $headers = $this->parseSqlExpectedLine($lines->shift());

        if ($headers === []) {
            throw ValidationException::withMessages([
                'output' => 'Header expected result MySQL tidak boleh kosong.',
            ]);
        }

        return $lines
            ->map(function (string $line) use ($headers): array {
                $values = $this->parseSqlExpectedLine($line);

                if (count($values) !== count($headers)) {
                    throw ValidationException::withMessages([
                        'output' => 'Jumlah kolom pada setiap baris expected result harus sama dengan header.',
                    ]);
                }

                return collect($headers)
                    ->mapWithKeys(fn (string $header, int $index) => [$header => $values[$index]])
                    ->all();
            })
            ->values()
            ->all();
    }

    private function parseSqlExpectedLine(string $line): array
    {
        $delimiter = str_contains($line, '|') ? '|' : ',';

        return collect(str_getcsv($line, $delimiter))
            ->map(fn (?string $value) => trim((string) $value))
            ->all();
    }

    private function deleteModuleMaterial(Module $module): void
    {
        if ($module->module_pdf_path) {
            Storage::disk('public')->delete($module->module_pdf_path);
        }
    }

    private function deleteModuleExecutable(Module $module): void
    {
        if ($module->file_exe) {
            Storage::disk('public')->delete($module->file_exe);
        }
    }

    private function deleteModuleFiles(Module $module): void
    {
        $this->deleteModuleMaterial($module);
        $this->deleteModuleExecutable($module);
    }
}
