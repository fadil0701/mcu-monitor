<?php

namespace App\Support;

use App\Models\Participant;
use Carbon\Carbon;

final class ParticipantMcuScheduleEligibility
{
    public function __construct(
        private readonly Participant $participant,
    ) {}

    public function canRequest(): bool
    {
        return $this->blockingReason() === null;
    }

    public function blockingReason(): ?string
    {
        return $this->ckgReason() ?? $this->intervalReason();
    }

    public function hasCkgScreening(): bool
    {
        return $this->participant->hasCompletedCkgScreening();
    }

    private function ckgReason(): ?string
    {
        if ($this->hasCkgScreening()) {
            return null;
        }

        return 'Anda belum melakukan Skrining Kesehatan Kerja (CKG) di PPKP. Selesaikan skrining terlebih dahulu sebelum mengajukan jadwal MCU.';
    }

    private function intervalReason(): ?string
    {
        if (! $this->participant->tanggal_mcu_terakhir) {
            return null;
        }

        $intervalYears = (int) config('mcu.interval_years', 3);
        $eligibleFrom = Carbon::parse($this->participant->tanggal_mcu_terakhir)->addYears($intervalYears);

        if ($eligibleFrom->lte(Carbon::now())) {
            return null;
        }

        return 'Anda belum memenuhi syarat pendaftaran ulang (belum '.$intervalYears.' tahun sejak MCU terakhir). Silakan hubungi admin jika ada kondisi khusus.';
    }
}
