<?php

use App\Http\Controllers\StaffActivationController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffDeactivationController;
use App\Http\Controllers\StaffPasswordResetController;
use App\Http\Controllers\TemporaryPasswordController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware('auth')->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('change-password', [TemporaryPasswordController::class, 'edit'])->name('password.change');
    Route::put('change-password', [TemporaryPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.change.update');

    // Halaman fitur yang belum dibangun. Masing-masing diganti controller
    // sungguhan pada fasenya (lihat PROMPTS.md).
    Route::inertia('schedule', 'coming-soon', ['title' => 'Jadwal Armada'])->name('schedule.index');
    Route::inertia('bookings', 'coming-soon', ['title' => 'Peminjaman'])->name('bookings.index');
    Route::inertia('history', 'coming-soon', ['title' => 'Riwayat'])->name('history.index');
    Route::inertia('cars', 'coming-soon', ['title' => 'Data Mobil'])->name('cars.index');
    Route::inertia('employees', 'coming-soon', ['title' => 'Data Karyawan'])->name('employees.index');

    // Kelola Petugas (khusus Pengelola; otorisasi per aksi lewat UserPolicy).
    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
    Route::put('staff/{user}', [StaffController::class, 'update'])->name('staff.update');
    Route::post('staff/{user}/deactivate', StaffDeactivationController::class)
        ->can('deactivate', 'user')
        ->name('staff.deactivate');
    Route::post('staff/{user}/activate', StaffActivationController::class)
        ->can('activate', 'user')
        ->name('staff.activate');
    Route::post('staff/{user}/reset-password', StaffPasswordResetController::class)
        ->can('resetPassword', 'user')
        ->name('staff.reset-password');

    Route::inertia('activity-log', 'coming-soon', ['title' => 'Log Aktivitas'])
        ->middleware('can:view-activity-log')
        ->name('activity-log.index');
});

require __DIR__.'/settings.php';
