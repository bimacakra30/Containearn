<?php


use Illuminate\Support\Facades\Route;

Route::get('/admin', function () {
    return view('admin.dashboard');
})->middleware('auth');

Route::get('/', function () {
    return view('mahasiswa.dashboard');
})->middleware('auth');

require __DIR__.'/auth.php';
