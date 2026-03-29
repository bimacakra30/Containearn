<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
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
                    ->with('questions')
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

    private function authorizeAdminAccess(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor?->isAdmin(), 403, 'You do not have access to this page.');

        return $actor;
    }
}
