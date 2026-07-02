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
		$name = (string) ($this->payload['participant_name'] ?? $this->payload['nama_lengkap'] ?? 'Peserta');
		$nik = (string) ($this->payload['nik_ktp'] ?? '-');

		[$title, $message] = match ($this->type) {
			'baru' => [
				'Pendaftaran Peserta Baru',
				"{$name} mendaftar sebagai peserta baru (NIK {$nik}).",
			],
			'batal' => [
				'Pembatalan Jadwal MCU',
				"{$name} membatalkan jadwal MCU (NIK {$nik}).",
			],
			'menunggu_konfirmasi' => [
				'Pengajuan Jadwal MCU (Perlu Konfirmasi)',
				$this->formatUlangMessage($name, $nik).' Belum CKG tahun berjalan — perlu konfirmasi admin.',
			],
			default => [
				'Pendaftaran Ulang Peserta',
				$this->formatUlangMessage($name, $nik),
			],
		};

		return [
			'title' => $title,
			'message' => $message,
			'type' => $this->type,
			'payload' => $this->payload,
		];
	}

	private function formatUlangMessage(string $name, string $nik): string
	{
		if (($this->payload['type'] ?? '') === 'reschedule_request') {
			$date = (string) ($this->payload['new_date'] ?? '-');
			$time = (string) ($this->payload['new_time'] ?? '-');

			return "{$name} mengajukan reschedule MCU ({$date} {$time}, NIK {$nik}).";
		}

		$date = (string) ($this->payload['tanggal_pemeriksaan'] ?? '-');
		$time = (string) ($this->payload['jam_pemeriksaan'] ?? '-');

		return "{$name} mengajukan pendaftaran ulang MCU ({$date} {$time}, NIK {$nik}).";
	}
}
