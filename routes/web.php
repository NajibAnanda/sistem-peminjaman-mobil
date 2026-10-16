<?php

use App\Http\Controllers\BookingCancellationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingExtensionController;
use App\Http\Controllers\BookingPhotoController;
use App\Http\Controllers\BookingPickupController;
use App\Http\Controllers\BookingReturnController;
use App\Http\Controllers\CarActivationController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CarDeactivationController;
use App\Http\Controllers\CarOperationalStatusController;
use App\Http\Controllers\CarPhotoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeActivationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDeactivationController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StaffActivationController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffDeactivationController;
use App\Http\Controllers\StaffPasswordResetController;
use App\Http\Controllers\TemporaryPasswordController;
use App\Models\Car;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('change-password', [TemporaryPasswordController::class, 'edit'])->name('password.change');
    Route::put('change-password', [TemporaryPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.change.update');

    // Jadwal Armada (otorisasi lewat ScheduleIndexRequest → BookingPolicy).
    Route::get('schedule', ScheduleController::class)->name('schedule.index');
    // Peminjaman (Pengelola & Admin; otorisasi lewat BookingPolicy).
    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('bookings/{booking}/edit', [BookingController::class, 'edit'])->name('bookings.edit');
    Route::put('bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
    Route::post('bookings/{booking}/pickup', [BookingPickupController::class, 'store'])->name('bookings.pickup');
    Route::post('bookings/{booking}/return', [BookingReturnController::class, 'store'])->name('bookings.return');
    Route::post('bookings/{booking}/extend', [BookingExtensionController::class, 'store'])->name('bookings.extend');
    Route::post('bookings/{booking}/cancel', [BookingCancellationController::class, 'store'])->name('bookings.cancel');
    Route::get('bookings/{booking}/photos/{stage}', [BookingPhotoController::class, 'show'])->name('bookings.photos.show');
    // Halaman yang belum dibangun; diganti controller sungguhan di Fase 6 (PROMPTS.md).
    Route::inertia('history', 'coming-soon', ['title' => 'Riwayat'])->name('history.index');
    // Data Mobil (Pengelola & Admin; otorisasi lewat CarPolicy).
    Route::get('cars', [CarController::class, 'index'])->name('cars.index');
    Route::post('cars', [CarController::class, 'store'])->name('cars.store');
    Route::get('cars/{car}', [CarController::class, 'show'])->name('cars.show');
    Route::put('cars/{car}', [CarController::class, 'update'])->name('cars.update');
    Route::put('cars/{car}/operational-status', CarOperationalStatusController::class)
        ->name('cars.operational-status');
    Route::post('cars/{car}/photos/{position}', CarPhotoController::class)
        ->whereIn('position', array_map('strval', Car::PHOTO_POSITIONS))
        ->name('cars.photos.store');
    Route::post('cars/{car}/deactivate', CarDeactivationController::class)
        ->can('deactivate', 'car')
        ->name('cars.deactivate');
    Route::post('cars/{car}/activate', CarActivationController::class)
        ->can('activate', 'car')
        ->name('cars.activate');

    // Data Karyawan (Pengelola & Admin; otorisasi lewat EmployeePolicy).
    Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::post('employees/{employee}/deactivate', EmployeeDeactivationController::class)
        ->can('deactivate', 'employee')
        ->name('employees.deactivate');
    Route::post('employees/{employee}/activate', EmployeeActivationController::class)
        ->can('activate', 'employee')
        ->name('employees.activate');

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
