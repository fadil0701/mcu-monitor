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

    /**
     * @return list<string>
     */
    public function infoNotes(): array
    {
        if ($this->blockingReason() !== null) {
            return [];
        }

        $intervalYears = $this->intervalYears();

        if ($this->participant->tanggal_mcu_terakhir === null) {
            return [
                'Anda belum pernah melakukan MCU. Anda memenuhi syarat pengajuan jadwal MCU setelah menyelesaikan skrining CKG.',
            ];
        }

        return [
            'Anda belum melakukan MCU dalam '.$intervalYears.' tahun terakhir dan memenuhi syarat pengajuan jadwal MCU.',
        ];
    }

    public function hasCkgScreening(): bool
    {
        return $this->participant->hasCompletedCkgScreening();
    }

    public function hasMcuWithinInterval(): bool
    {
        return $this->intervalReason() !== null;
    }

    public function mcuEligibleFrom(): ?Carbon
    {
        if ($this->participant->tanggal_mcu_terakhir === null) {
            return null;
        }

        return Carbon::parse($this->participant->tanggal_mcu_terakhir)
            ->addYears($this->intervalYears());
    }

    private function ckgReason(): ?string
    {
        if ($this->hasCkgScreening()) {
            return null;
        }

        return 'Anda belum menyelesaikan Skrining Kesehatan Kerja (CKG) di PPKP. Selesaikan skrining terlebih dahulu sebelum mengajukan jadwal MCU.';
    }

    private function intervalReason(): ?string
    {
        if (! $this->participant->isWithinMcuInterval()) {
            return null;
        }

        $intervalYears = $this->intervalYears();
        $eligibleFrom = $this->mcuEligibleFrom();

        return 'Anda belum memenuhi syarat pendaftaran ulang (belum '.$intervalYears.' tahun sejak MCU terakhir'
            .($eligibleFrom ? ', dapat mengajukan kembali mulai '.$eligibleFrom->format('d/m/Y') : '')
            .'). Silakan hubungi admin jika ada kondisi khusus.';
    }

    private function intervalYears(): int
    {
        return (int) config('mcu.interval_years', 3);
    }
}
