<?php

namespace App\Notifications;

use App\Models\Schedule;
use Illuminate\Notifications\Notification;

class ParticipantScheduleNotification extends Notification
{
    public function __construct(
        public string $type,
        public Schedule $schedule,
        public array $extra = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $date = $this->schedule->tanggal_pemeriksaan?->format('d/m/Y') ?? '-';
        $time = $this->schedule->jam_pemeriksaan
            ? \Carbon\Carbon::parse($this->schedule->jam_pemeriksaan)->format('H:i')
            : '-';
        $queue = $this->schedule->queue_number ? (string) $this->schedule->queue_number : null;

        [$title, $message] = match ($this->type) {
            'reschedule_approved' => [
                'Reschedule Disetujui',
                "Permintaan reschedule Anda disetujui. Jadwal baru: {$date} pukul {$time}.",
            ],
            'reschedule_rejected' => [
                'Reschedule Ditolak',
                'Permintaan reschedule Anda ditolak. Jadwal tetap pada tanggal semula.',
            ],
            'schedule_created' => [
                'Jadwal MCU Baru',
                "Admin menjadwalkan MCU Anda pada {$date} pukul {$time}.",
            ],
            'schedule_confirmed' => [
                'Jadwal MCU Dikonfirmasi',
                $queue
                    ? "Jadwal MCU Anda: {$date} pukul {$time} (No. antrian {$queue})."
                    : "Jadwal MCU Anda: {$date} pukul {$time}.",
            ],
            'schedule_rejected' => [
                'Jadwal MCU Ditolak',
                "Jadwal MCU pada {$date} ditolak/dibatalkan oleh admin.",
            ],
            default => [
                'Pembaruan Jadwal MCU',
                "Status jadwal MCU Anda diperbarui menjadi {$this->schedule->status}.",
            ],
        };

        return [
            'title' => $title,
            'message' => $message,
            'type' => $this->type,
            'schedule_id' => $this->schedule->id,
            'payload' => array_merge([
                'tanggal_pemeriksaan' => $date,
                'jam_pemeriksaan' => $time,
                'status' => $this->schedule->status,
                'queue_number' => $queue,
            ], $this->extra),
        ];
    }
}
