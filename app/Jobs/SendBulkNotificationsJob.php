<?php

namespace App\Jobs;

use App\Models\Schedule;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [30, 60, 120]; // Retry after 30s, 1m, 2m

    protected array $scheduleIds;
    protected string $notificationType;
    protected int $batchSize;

    /**
     * Create a new job instance.
     */
    public function __construct(array $scheduleIds, string $notificationType = 'both', int $batchSize = 10)
    {
        $this->scheduleIds = $scheduleIds;
        $this->notificationType = $notificationType; // 'email', 'whatsapp', 'both'
        $this->batchSize = $batchSize;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting bulk notification job for {$this->notificationType}", [
            'schedule_count' => count($this->scheduleIds),
            'batch_size' => $this->batchSize
        ]);

        $schedules = Schedule::whereIn('id', $this->scheduleIds)
            ->where('status', 'Terjadwal')
            ->get();

        if ($schedules->isEmpty()) {
            Log::warning('No valid schedules found for bulk notification');
            return;
        }

        $emailService = new EmailService();
        $whatsappService = new WhatsAppService();
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Process in batches to avoid memory issues
        $schedules->chunk($this->batchSize)->each(function ($batch) use ($emailService, $whatsappService, &$successCount, &$errorCount, &$errors) {
            foreach ($batch as $schedule) {
                try {
                    $success = true;

                    // Send email if requested
                    if (in_array($this->notificationType, ['email', 'both']) && !$schedule->email_sent) {
                        $emailSuccess = $emailService->sendMcuInvitation($schedule);
                        if (!$emailSuccess) {
                            $success = false;
                            $errors[] = "Failed to send email to {$schedule->nama_lengkap} ({$schedule->email})";
                        }
                    }

                    // Send WhatsApp if requested
                    if (in_array($this->notificationType, ['whatsapp', 'both']) && !$schedule->whatsapp_sent) {
                        $whatsappSuccess = $whatsappService->sendMcuInvitation($schedule);
                        if (!$whatsappSuccess) {
                            $success = false;
                            $errors[] = "Failed to send WhatsApp to {$schedule->nama_lengkap} ({$schedule->no_telp})";
                        }
                    }

                    if ($success) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Error processing {$schedule->nama_lengkap}: " . $e->getMessage();
                    Log::error("Bulk notification error for schedule {$schedule->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                // Small delay to avoid overwhelming external services
                usleep(100000); // 0.1 second delay
            }
        });

        Log::info("Bulk notification job completed", [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'total_errors' => count($errors)
        ]);

        // Log errors for debugging
        if (!empty($errors)) {
            Log::warning("Bulk notification errors", ['errors' => $errors]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Bulk notification job failed", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'schedule_ids' => $this->scheduleIds
        ]);
    }
}

