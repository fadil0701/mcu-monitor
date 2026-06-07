<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendBulkMcuInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcu:send-bulk-invitations 
                            {--schedule-id= : Specific schedule ID to send invitation}
                            {--skpd= : Send to all scheduled participants from specific SKPD}
                            {--date= : Send to all scheduled participants on specific date (Y-m-d)}
                            {--email-only : Send email invitations only}
                            {--whatsapp-only : Send WhatsApp invitations only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send bulk MCU invitations via email and/or WhatsApp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting bulk MCU invitation process...');

        $query = Schedule::where('status', 'Terjadwal');

        // Filter by specific schedule ID
        if ($scheduleId = $this->option('schedule-id')) {
            $query->where('id', $scheduleId);
        }

        // Filter by SKPD
        if ($skpd = $this->option('skpd')) {
            $query->whereHas('participant', function ($q) use ($skpd) {
                $q->where('skpd', $skpd);
            });
        }

        // Filter by date
        if ($date = $this->option('date')) {
            $query->whereDate('tanggal_pemeriksaan', $date);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->warn('No scheduled MCU found matching the criteria.');
            return 1;
        }

        $this->info("Found {$schedules->count()} scheduled MCU(s) to process.");

        $emailService = new EmailService();
        $whatsappService = new WhatsAppService();

        $emailOnly = $this->option('email-only');
        $whatsappOnly = $this->option('whatsapp-only');

        $successCount = 0;
        $errorCount = 0;

        $progressBar = $this->output->createProgressBar($schedules->count());
        $progressBar->start();

        foreach ($schedules as $schedule) {
            try {
                $updated = false;

                // Send email invitation
                if (!$whatsappOnly) {
                    try {
                        $emailService->sendMcuInvitation($schedule);
                        $schedule->email_sent = true;
                        $schedule->email_sent_at = now();
                        $updated = true;
                        $this->line("\nEmail sent to: {$schedule->email}");
                    } catch (\Exception $e) {
                        $this->error("\nEmail failed for {$schedule->email}: " . $e->getMessage());
                    }
                }

                // Send WhatsApp invitation
                if (!$emailOnly) {
                    try {
                        $whatsappService->sendMcuInvitation($schedule);
                        $schedule->whatsapp_sent = true;
                        $schedule->whatsapp_sent_at = now();
                        $updated = true;
                        $this->line("\nWhatsApp sent to: {$schedule->no_telp}");
                    } catch (\Exception $e) {
                        $this->error("\nWhatsApp failed for {$schedule->no_telp}: " . $e->getMessage());
                    }
                }

                if ($updated) {
                    $schedule->save();
                    $successCount++;
                }

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("\nError processing schedule ID {$schedule->id}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Bulk invitation process completed!");
        $this->info("Successfully processed: {$successCount}");
        $this->info("Errors: {$errorCount}");

        return 0;
    }
}
