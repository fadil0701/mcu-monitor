<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendMcuInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcu:send-invitations {--type=both : Type of invitation (email, whatsapp, both)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send MCU invitations via email and/or WhatsApp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        
        // Get schedules that haven't been sent invitations
        $schedules = Schedule::where('status', 'Terjadwal')
            ->where(function ($query) use ($type) {
                if (in_array($type, ['email', 'both'])) {
                    $query->where('email_sent', false);
                }
                if (in_array($type, ['whatsapp', 'both'])) {
                    $query->where('whatsapp_sent', false);
                }
            })
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No pending invitations to send.');
            return;
        }

        $this->info("Found {$schedules->count()} pending invitations.");

        $emailService = new EmailService();
        $whatsappService = new WhatsAppService();

        $successCount = 0;
        $errorCount = 0;

        foreach ($schedules as $schedule) {
            $this->info("Processing invitation for {$schedule->nama_lengkap}...");

            try {
                if (in_array($type, ['email', 'both']) && !$schedule->email_sent) {
                    if ($emailService->sendMcuInvitation($schedule)) {
                        $this->info("✓ Email sent to {$schedule->email}");
                        $successCount++;
                    } else {
                        $this->error("✗ Failed to send email to {$schedule->email}");
                        $errorCount++;
                    }
                }

                if (in_array($type, ['whatsapp', 'both']) && !$schedule->whatsapp_sent) {
                    if ($whatsappService->sendMcuInvitation($schedule)) {
                        $this->info("✓ WhatsApp sent to {$schedule->no_telp}");
                        $successCount++;
                    } else {
                        $this->error("✗ Failed to send WhatsApp to {$schedule->no_telp}");
                        $errorCount++;
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error processing {$schedule->nama_lengkap}: " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->info("\nInvitation sending completed!");
        $this->info("Success: {$successCount}");
        $this->info("Errors: {$errorCount}");
    }
}
