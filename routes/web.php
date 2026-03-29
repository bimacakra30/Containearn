<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PracticumContentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    return match (auth()->user()->role) {
        'superadmin', 'dosen' => redirect()->route('admin.dashboard'),
        'mahasiswa'           => redirect()->route('mahasiswa.dashboard'),
        default               => abort(403)
    };
});

Route::middleware('auth')->group(function () {
    Route::prefix('admin')->group(function () {

        Route::get('/', function () {
            abort_unless(in_array(auth()->user()->role, ['superadmin', 'dosen']), 403);
            return view('admin.dashboard');
        })->name('admin.dashboard');

        Route::get('/profile', function () {
            abort_unless(in_array(auth()->user()->role, ['superadmin', 'dosen']), 403);
            return view('admin.profile');
        })->name('admin.profile');

        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        Route::get('/contents', [PracticumContentController::class, 'index'])->name('admin.contents.index');

    });

    Route::prefix('mahasiswa')->group(function () {

        Route::get('/', function () {
            abort_unless(auth()->user()->role === 'mahasiswa', 403);
            return view('mahasiswa.dashboard');
        })->name('mahasiswa.dashboard');

        Route::get('/profile', function () {
            abort_unless(auth()->user()->role === 'mahasiswa', 403);
            return view('mahasiswa.profile');
        })->name('mahasiswa.profile');

    });
    
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__ . '/auth.php';
