<?php


use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    return match(auth()->user()->role) {
        'superadmin', 'dosen' => redirect()->route('admin.dashboard'),
        'mahasiswa'           => redirect()->route('mahasiswa.dashboard'),
        default               => abort(403)
    };
});

Route::middleware('auth')->group(function () {

    Route::get('/admin', function () {
        if (!in_array(auth()->user()->role, ['superadmin', 'dosen'])) {
            abort(403, 'Akses ditolak.');
        }
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/admin/monitoring', function () {
        if (!in_array(auth()->user()->role, ['superadmin', 'dosen'])) {
            abort(403, 'Akses ditolak.');
        }
        return view('admin.monitoring');
    })->name('admin.monitoring');

    // Mahasiswa routes
    Route::get('/', function () {
        if (auth()->user()->role !== 'mahasiswa') {
            abort(403, 'Akses ditolak.');
        }
        return view('mahasiswa.dashboard');
    })->name('mahasiswa.dashboard');

});

require __DIR__.'/auth.php';