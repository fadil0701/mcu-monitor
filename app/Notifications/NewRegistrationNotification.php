<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewRegistrationNotification extends Notification
{
	public function __construct(
		public string $type, // 'baru' or 'ulang'
		public array $payload = []
	) {}

	public function via(object $notifiable): array
	{
		return ['database'];
	}

	public function toArray(object $notifiable): array
	{
		return [
			'title' => $this->type === 'baru' ? 'Pendaftaran Peserta Baru' : 'Pendaftaran Ulang Peserta',
			'payload' => $this->payload,
		];
	}
}
