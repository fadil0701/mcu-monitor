<?php

namespace App\Console\Commands;

use App\Services\EmailService;
use Illuminate\Console\Command;

class TestSmtpCommand extends Command
{
    protected $signature = 'smtp:test {email : Alamat email penerima tes}';

    protected $description = 'Kirim email tes menggunakan pengaturan SMTP dari database';

    public function handle(EmailService $emailService): int
    {
        $email = (string) $this->argument('email');

        $this->info("Mengirim email tes ke {$email}...");

        try {
            $emailService->sendTestMessage($email);
            $this->info('Email tes berhasil dikirim.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal mengirim email tes: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
