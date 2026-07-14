<?php

use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\PracticumContentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentPracticumController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => match (auth()->user()->role) {
        'superadmin', 'dosen' => redirect()->route('admin.dashboard'),
        'mahasiswa' => view('mahasiswa.dashboard'),
        default => abort(403),
    })->name('mahasiswa.dashboard');

    Route::get('/profile', fn () => auth()->user()->role === 'mahasiswa'
        ? view('mahasiswa.profile')
        : redirect()->route('admin.profile')
    )->name('mahasiswa.profile');

    Route::get('/profile/edit', fn () => redirect()->route('mahasiswa.profile'))->name('profile.edit');

    Route::match(['post', 'patch'], '/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:superadmin,dosen')->prefix('admin')->group(function () {
        Route::get('/', fn () => view('admin.dashboard'))->name('admin.dashboard');
        Route::get('/profile', fn () => view('admin.profile'))->name('admin.profile');
        Route::match(['post', 'patch'], '/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('admin.profile.destroy');
        Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports.index');
        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('admin.monitoring.index');
        Route::get('/monitoring/{containerName}/logs', [MonitoringController::class, 'logs'])
            ->where('containerName', '[A-Za-z0-9][A-Za-z0-9_.-]*')
            ->name('admin.monitoring.logs');
        Route::get('/contents', [PracticumContentController::class, 'index'])->name('admin.contents.index');
        Route::post('/contents/course', [PracticumContentController::class, 'storeCourse'])->name('admin.contents.course.store');
        Route::patch('/contents/course/{course}', [PracticumContentController::class, 'updateCourse'])->name('admin.contents.course.update');
        Route::delete('/contents/course/{course}', [PracticumContentController::class, 'destroyCourse'])->name('admin.contents.course.destroy');
        Route::post('/contents/course/{course}/module', [PracticumContentController::class, 'storeModule'])->name('admin.contents.module.store');
        Route::patch('/contents/module/{module}', [PracticumContentController::class, 'updateModule'])->name('admin.contents.module.update');
        Route::delete('/contents/module/{module}', [PracticumContentController::class, 'destroyModule'])->name('admin.contents.module.destroy');

        Route::post('/contents/module/{module}/questions', [PracticumContentController::class, 'storeQuestion'])->name('admin.contents.questions.store');
        Route::patch('/contents/questions/{question}', [PracticumContentController::class, 'updateQuestion'])->name('admin.contents.questions.update');
        Route::delete('/contents/questions/{question}', [PracticumContentController::class, 'destroyQuestion'])->name('admin.contents.questions.destroy');

        Route::post('/contents/module/{module}/lab-questions', [PracticumContentController::class, 'storeLabQuestion'])->name('admin.contents.lab-questions.store');
        Route::patch('/contents/lab-questions/{labQuestion}', [PracticumContentController::class, 'updateLabQuestion'])->name('admin.contents.lab-questions.update');
        Route::delete('/contents/lab-questions/{labQuestion}', [PracticumContentController::class, 'destroyLabQuestion'])->name('admin.contents.lab-questions.destroy');

        Route::get('/user', [UserController::class, 'index'])->name('admin.user.index');
        Route::post('/user', [UserController::class, 'store'])->name('admin.user.store');
        Route::patch('/user/{user}', [UserController::class, 'update'])->name('admin.user.update');
        Route::delete('/user/{user}', [UserController::class, 'destroy'])->name('admin.user.destroy');
    });

    Route::middleware('role:mahasiswa')->group(function () {
        Route::get('/content', [StudentPracticumController::class, 'index'])->name('mahasiswa.content.index');

        Route::whereNumber(['module'])->group(function () {
            Route::get('/content/{module}', [StudentPracticumController::class, 'show'])->name('mahasiswa.content.show');
            Route::post('/content/{module}/start', [StudentPracticumController::class, 'start'])->name('mahasiswa.content.start');
            Route::post('/content/{module}/quiz', [StudentPracticumController::class, 'submitQuiz'])->name('mahasiswa.content.quiz');
            Route::post('/content/{module}/run', [StudentPracticumController::class, 'run'])->name('mahasiswa.content.run');
            Route::post('/content/{module}/end', [StudentPracticumController::class, 'end'])->name('mahasiswa.content.end');
            Route::post('/content/{module}/next', [StudentPracticumController::class, 'next'])->name('mahasiswa.content.next');
            Route::get('/module/{module}/material-pdf', [StudentPracticumController::class, 'servePdf'])->name('mahasiswa.module.pdf');
        });
    });
});

require __DIR__.'/auth.php';
