<?php

namespace App\Notifications;

use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewScheduleRequest extends Notification implements ShouldQueue
{
	use Queueable;

	public function __construct(public Schedule $schedule) {}

	public function via(object $notifiable): array
	{
		return ['mail', 'database'];
	}

	public function toMail(object $notifiable): MailMessage
	{
		return (new MailMessage)
			->subject('Permintaan Jadwal MCU Baru')
			->greeting('Halo Admin,')
			->line('Ada permintaan jadwal MCU baru dari peserta: ' . $this->schedule->nama_lengkap)
			->line('Tanggal: ' . $this->schedule->tanggal_pemeriksaan->format('d/m/Y') . ' ' . $this->schedule->jam_pemeriksaan->format('H:i'))
			->line('Lokasi: ' . $this->schedule->lokasi_pemeriksaan)
			->action('Tinjau Jadwal', url('/admin'))
			->line('Terima kasih.');
	}

	public function toArray(object $notifiable): array
	{
		return [
			'type' => 'new_schedule_request',
			'schedule_id' => $this->schedule->id,
			'participant_id' => $this->schedule->participant_id,
			'name' => $this->schedule->nama_lengkap,
			'tanggal' => $this->schedule->tanggal_pemeriksaan,
			'jam' => $this->schedule->jam_pemeriksaan,
		];
	}
}

