<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendMcuReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcu:send-reminders {--days=1 : Days before MCU to send reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send MCU reminders to participants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $targetDate = Carbon::now()->addDays($days)->toDateString();

        // Get schedules for the target date
        $schedules = Schedule::where('status', 'Terjadwal')
            ->where('tanggal_pemeriksaan', $targetDate)
            ->get();

        if ($schedules->isEmpty()) {
            $this->info("No MCU schedules for {$targetDate}.");
            return;
        }

        $this->info("Found {$schedules->count()} MCU schedules for {$targetDate}.");

        $emailService = new EmailService();
        $whatsappService = new WhatsAppService();

        $successCount = 0;
        $errorCount = 0;

        foreach ($schedules as $schedule) {
            $this->info("Sending reminder to {$schedule->nama_lengkap}...");

            try {
                // Send email reminder
                if ($emailService->sendMcuInvitation($schedule)) {
                    $this->info("✓ Email reminder sent to {$schedule->email}");
                    $successCount++;
                } else {
                    $this->error("✗ Failed to send email reminder to {$schedule->email}");
                    $errorCount++;
                }

                // Send WhatsApp reminder
                if ($whatsappService->sendMcuInvitation($schedule)) {
                    $this->info("✓ WhatsApp reminder sent to {$schedule->no_telp}");
                    $successCount++;
                } else {
                    $this->error("✗ Failed to send WhatsApp reminder to {$schedule->no_telp}");
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $this->error("Error sending reminder to {$schedule->nama_lengkap}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->info("\nReminder sending completed!");
        $this->info("Success: {$successCount}");
        $this->info("Errors: {$errorCount}");
    }
}
