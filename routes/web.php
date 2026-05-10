<?php

use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PracticumContentController;
use App\Http\Controllers\StudentPracticumController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!auth()->check()) return redirect()->route('login');

    return match (auth()->user()->role) {
        'superadmin', 'dosen' => redirect()->route('admin.dashboard'),
        'mahasiswa'           => view('mahasiswa.dashboard'),
        default               => abort(403),
    };
})->name('mahasiswa.dashboard');

Route::middleware(['auth', 'role:superadmin,dosen'])->prefix('admin')->group(function () {
    Route::get('/',        fn() => view('admin.dashboard'))->name('admin.dashboard');
    Route::get('/profile', fn() => view('admin.profile'))->name('admin.profile');
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('admin.monitoring.index');
    Route::get('/contents', [PracticumContentController::class, 'index'])->name('admin.contents.index');
    Route::post('/contents/courses', [PracticumContentController::class, 'storeCourse'])->name('admin.contents.courses.store');
    Route::patch('/contents/courses/{course}', [PracticumContentController::class, 'updateCourse'])->name('admin.contents.courses.update');
    Route::delete('/contents/courses/{course}', [PracticumContentController::class, 'destroyCourse'])->name('admin.contents.courses.destroy');
    Route::post('/contents/courses/{course}/modules', [PracticumContentController::class, 'storeModule'])->name('admin.contents.modules.store');
    Route::patch('/contents/modules/{module}', [PracticumContentController::class, 'updateModule'])->name('admin.contents.modules.update');
    Route::delete('/contents/modules/{module}', [PracticumContentController::class, 'destroyModule'])->name('admin.contents.modules.destroy');

    Route::post('/contents/modules/{module}/questions', [PracticumContentController::class, 'storeQuestion'])->name('admin.contents.questions.store');
    Route::patch('/contents/questions/{question}', [PracticumContentController::class, 'updateQuestion'])->name('admin.contents.questions.update');
    Route::delete('/contents/questions/{question}', [PracticumContentController::class, 'destroyQuestion'])->name('admin.contents.questions.destroy');

    Route::post('/contents/modules/{module}/lab-questions', [PracticumContentController::class, 'storeLabQuestion'])->name('admin.contents.lab-questions.store');
    Route::patch('/contents/lab-questions/{labQuestion}', [PracticumContentController::class, 'updateLabQuestion'])->name('admin.contents.lab-questions.update');
    Route::delete('/contents/lab-questions/{labQuestion}', [PracticumContentController::class, 'destroyLabQuestion'])->name('admin.contents.lab-questions.destroy');

    Route::get('/users',           [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/users',          [UserController::class, 'store'])->name('admin.users.store');
    Route::patch('/users/{user}',  [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
});

Route::middleware(['auth', 'role:mahasiswa'])->group(function () {
    Route::get('/profile',  fn() => view('mahasiswa.profile'))->name('mahasiswa.profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/content', [StudentPracticumController::class, 'index'])->name('mahasiswa.content.index');

    Route::whereNumber(['module'])->group(function () {
        Route::get('/content/{module}',            [StudentPracticumController::class, 'show'])->name('mahasiswa.content.show');
        Route::post('/content/{module}/start',     [StudentPracticumController::class, 'start'])->name('mahasiswa.content.start');
        Route::post('/content/{module}/quiz',      [StudentPracticumController::class, 'submitQuiz'])->name('mahasiswa.content.quiz');
        Route::post('/content/{module}/run',       [StudentPracticumController::class, 'run'])->name('mahasiswa.content.run');
        Route::post('/content/{module}/end',       [StudentPracticumController::class, 'end'])->name('mahasiswa.content.end');
        Route::post('/content/{module}/next',      [StudentPracticumController::class, 'next'])->name('mahasiswa.content.next');
        Route::get('/modules/{module}/material-pdf', [StudentPracticumController::class, 'servePdf'])->name('mahasiswa.module.pdf');
    });
});

require __DIR__ . '/auth.php';
