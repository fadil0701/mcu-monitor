<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientNotificationsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('client')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [ClientController::class, 'dashboard'])->name('client.dashboard');
    Route::get('/profile', [ClientController::class, 'profile'])->name('client.profile');
    Route::put('/profile', [ClientController::class, 'updateProfile'])->name('client.profile.update');
    Route::get('/schedules', [ClientController::class, 'schedules'])->name('client.schedules');
    Route::get('/results', [ClientController::class, 'results'])->name('client.results');
    Route::get('/results/{result}/download', [ClientController::class, 'downloadResult'])->name('client.results.download');
    Route::get('/results/{result}/download-all', [ClientController::class, 'downloadAllResult'])->name('client.results.downloadAll');

    Route::get('/notifications', [ClientNotificationsController::class, 'index'])->name('client.notifications.index');
    Route::post('/notifications/{id}/read', [ClientNotificationsController::class, 'markAsRead'])->name('client.notifications.mark-read');
    Route::post('/notifications/mark-all-read', [ClientNotificationsController::class, 'markAllAsRead'])->name('client.notifications.mark-all-read');

    Route::get('/schedule/request', [ClientController::class, 'requestScheduleForm'])->name('client.schedule.request');
    Route::get('/schedule/quota', [ClientController::class, 'scheduleQuota'])->name('client.schedule.quota');
    Route::post('/schedule/request', [ClientController::class, 'storeScheduleRequest'])->name('client.schedule.request.store');

    Route::post('/schedule/{id}/confirm', [ClientController::class, 'confirmAttendance'])->name('client.schedule.confirm');
    Route::post('/schedule/{id}/reschedule', [ClientController::class, 'requestReschedule'])->name('client.schedule.reschedule');
    Route::post('/schedule/{id}/cancel', [ClientController::class, 'cancelSchedule'])->name('client.schedule.cancel');
});

Route::middleware('guest')->group(function () {
    Route::get('/peserta/aktivasi-akun', [\App\Http\Controllers\PesertaActivationController::class, 'showVerificationForm'])->name('peserta.aktivasi');
    Route::post('/peserta/aktivasi-akun', [\App\Http\Controllers\PesertaActivationController::class, 'verifyParticipant']);
    Route::get('/peserta/aktivasi-akun/register', [\App\Http\Controllers\PesertaActivationController::class, 'showRegisterForm'])->name('peserta.aktivasi.register');
    Route::post('/peserta/aktivasi-akun/register', [\App\Http\Controllers\PesertaActivationController::class, 'registerAccount']);
});

require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
