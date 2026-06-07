<?php

use App\Http\Controllers\ClientController;
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

    Route::get('/schedule/request', [ClientController::class, 'requestScheduleForm'])->name('client.schedule.request');
    Route::post('/schedule/request', [ClientController::class, 'storeScheduleRequest'])->name('client.schedule.request.store');

    Route::post('/schedule/{id}/confirm', [ClientController::class, 'confirmAttendance'])->name('client.schedule.confirm');
    Route::post('/schedule/{id}/reschedule', [ClientController::class, 'requestReschedule'])->name('client.schedule.reschedule');
    Route::post('/schedule/{id}/cancel', [ClientController::class, 'cancelSchedule'])->name('client.schedule.cancel');
});

Route::middleware(['auth'])->get('/participants/template', function () {
    $headers = [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    $columns = [
        ['NIK', 'Nama', 'Jenis Kelamin', 'No Telp', 'NRK', 'Tempat Lahir', 'Tanggal Lahir', 'SKPD', 'UKPD', 'Email', 'Status Pegawai', 'Status MCU', 'Tanggal MCU Terakhir', 'Catatan'],
        ['3173012345678901', 'Budi Santoso', 'L', '081234567890', '', '', '', '', '', '', '', '', '', ''],
    ];

    $tmp = tempnam(sys_get_temp_dir(), 'tpl_').'.xlsx';
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $textColumns = [1, 4, 5];

    foreach ($columns as $rowIndex => $row) {
        foreach ($row as $colIndex => $value) {
            $col = $colIndex + 1;
            $rowNum = $rowIndex + 1;

            if ($rowIndex > 0 && in_array($col, $textColumns, true)) {
                $sheet->setCellValueExplicitByColumnAndRow($col, $rowNum, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            } else {
                $sheet->setCellValueByColumnAndRow($col, $rowNum, $value);
            }
        }
    }

    foreach ($textColumns as $col) {
        $sheet->getStyleByColumnAndRow($col, 1, $col, 1000)
            ->getNumberFormat()
            ->setFormatCode('@');
    }

    $sheet->setCellValue('A1000', 'Kolom wajib: NIK, Nama, Jenis Kelamin (L/P), No Telp. Kolom lain opsional.');
    $sheet->getStyle('A1000')->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF666666'));

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($tmp);

    return response()->download($tmp, 'participants_template.xlsx', $headers)->deleteFileAfterSend(true);
})->name('participants.template');

Route::middleware('guest')->group(function () {
    Route::get('/peserta/aktivasi-akun', [\App\Http\Controllers\PesertaActivationController::class, 'showVerificationForm'])->name('peserta.aktivasi');
    Route::post('/peserta/aktivasi-akun', [\App\Http\Controllers\PesertaActivationController::class, 'verifyParticipant']);
    Route::get('/peserta/aktivasi-akun/register', [\App\Http\Controllers\PesertaActivationController::class, 'showRegisterForm'])->name('peserta.aktivasi.register');
    Route::post('/peserta/aktivasi-akun/register', [\App\Http\Controllers\PesertaActivationController::class, 'registerAccount']);
});

require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
