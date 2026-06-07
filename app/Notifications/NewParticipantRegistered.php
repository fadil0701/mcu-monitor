<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewParticipantRegistered extends Notification implements ShouldQueue
{
	use Queueable;

	public function __construct(public User $user, public ?Participant $participant = null) {}

	public function via(object $notifiable): array
	{
		return ['mail', 'database'];
	}

	public function toMail(object $notifiable): MailMessage
	{
		$nama = $this->participant?->nama_lengkap ?? $this->user->name;
		return (new MailMessage)
			->subject('Pendaftaran Peserta Baru')
			->greeting('Halo Admin,')
			->line("Peserta baru mendaftar: {$nama} ({$this->user->email})")
			->line('Silakan tinjau data peserta dan proses penjadwalan MCU jika diperlukan.')
			->action('Buka Admin Panel', url('/admin'))
			->line('Terima kasih.');
	}

	public function toArray(object $notifiable): array
	{
		return [
			'type' => 'new_participant',
			'user_id' => $this->user->id,
			'participant_id' => $this->participant?->id,
			'name' => $this->participant?->nama_lengkap ?? $this->user->name,
			'email' => $this->user->email,
		];
	}
}

