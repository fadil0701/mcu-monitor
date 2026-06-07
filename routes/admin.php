<?php

use App\Http\Controllers\Admin\AdminNotificationsController;
use App\Http\Controllers\Admin\DiagnosisController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\McuResultController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\PdfTemplateController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RescheduleCenterController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SpecialistDoctorController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WhatsAppTemplatesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\McuResultDownloadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('participants', ParticipantController::class);
    Route::post('participants/import', [ParticipantController::class, 'import'])->name('participants.import');
    Route::post('participants/bulk-destroy', [ParticipantController::class, 'bulkDestroy'])->name('participants.bulk-destroy');

    Route::resource('schedules', ScheduleController::class);
    Route::post('schedules/bulk-destroy', [ScheduleController::class, 'bulkDestroy'])->name('schedules.bulk-destroy');
    Route::post('schedules/{schedule}/quick-status', [ScheduleController::class, 'quickStatus'])->name('schedules.quick-status');
    Route::post('schedules/{schedule}/send-email', [ScheduleController::class, 'sendEmail'])->name('schedules.send-email');
    Route::post('schedules/{schedule}/send-whatsapp', [ScheduleController::class, 'sendWhatsApp'])->name('schedules.send-whatsapp');

    Route::get('mcu-results/{record}/download-all', [McuResultDownloadController::class, 'downloadAll'])->name('mcu-results.downloadAll');
    Route::resource('mcu-results', McuResultController::class)->parameters(['mcu-results' => 'mcu_result']);
    Route::post('mcu-results/{mcu_result}/update', [McuResultController::class, 'update'])->name('mcu-results.update-post');
    Route::post('mcu-results/{mcu_result}/send-email', [McuResultController::class, 'sendEmail'])->name('mcu-results.send-email');
    Route::post('mcu-results/{mcu_result}/send-whatsapp', [McuResultController::class, 'sendWhatsApp'])->name('mcu-results.send-whatsapp');

    Route::resource('users', UserController::class);

    Route::middleware('super_admin')->group(function () {
        Route::get('diagnoses/template/download', [DiagnosisController::class, 'downloadTemplate'])->name('diagnoses.template');
        Route::post('diagnoses/import', [DiagnosisController::class, 'import'])->name('diagnoses.import');
        Route::resource('diagnoses', DiagnosisController::class);

        Route::resource('specialist-doctors', SpecialistDoctorController::class)->parameters(['specialist-doctors' => 'specialist_doctor']);

        Route::get('reschedule-center', [RescheduleCenterController::class, 'index'])->name('reschedule-center.index');
        Route::post('reschedule-center/{schedule}/approve', [RescheduleCenterController::class, 'approve'])->name('reschedule-center.approve');
        Route::post('reschedule-center/{schedule}/reject', [RescheduleCenterController::class, 'reject'])->name('reschedule-center.reject');

        Route::get('notifications', [AdminNotificationsController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{id}/read', [AdminNotificationsController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('notifications/mark-all-read', [AdminNotificationsController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

        Route::get('whatsapp-templates', [WhatsAppTemplatesController::class, 'index'])->name('whatsapp-templates.index');
        Route::post('whatsapp-templates', [WhatsAppTemplatesController::class, 'update'])->name('whatsapp-templates.update');
        Route::post('whatsapp-templates/result', [WhatsAppTemplatesController::class, 'updateResult'])->name('whatsapp-templates.update-result');
        Route::post('whatsapp-templates/reset', [WhatsAppTemplatesController::class, 'reset'])->name('whatsapp-templates.reset');
        Route::post('whatsapp-templates/reset-result', [WhatsAppTemplatesController::class, 'resetResult'])->name('whatsapp-templates.reset-result');

        Route::resource('email-templates', EmailTemplateController::class)->parameters(['email-templates' => 'email_template']);
        Route::resource('pdf-templates', PdfTemplateController::class)->parameters(['pdf-templates' => 'pdf_template']);

        Route::get('settings/email-result-template', [SettingController::class, 'emailResultTemplate'])->name('settings.email-result-template');
        Route::post('settings/email-result-template', [SettingController::class, 'updateEmailResultTemplate'])->name('settings.update-email-result-template');
    });

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/download/{type}', [ReportController::class, 'download'])->name('reports.download');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings/section/{section}', [SettingController::class, 'updateSection'])->name('settings.update-section');
});
