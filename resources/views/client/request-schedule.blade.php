@extends('layouts.sneat.app')

@section('title', 'Daftar Ulang MCU')
@section('breadcrumb', 'Portal Peserta')
@section('pageTitle', 'Pendaftaran Ulang MCU')

@section('content')
<div class="row">
    <div class="col-lg-8 mb-4">
        <x-common.component-card title="Form Permintaan Jadwal">
            @unless($eligible)
                <div class="alert alert-warning">
                    <i class="bx bx-error me-1"></i>{{ $reason }}
                </div>
            @endunless

            <form method="POST" action="{{ route('client.schedule.request.store') }}" id="schedule-request-form">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Tanggal Pemeriksaan *</label>
                        <p class="text-muted small mb-2">Angka di bawah tanggal = sisa kuota tersedia</p>

                        <div
                            id="mcu-quota-calendar"
                            class="mcu-quota-calendar @error('tanggal_pemeriksaan') is-invalid @enderror"
                            aria-label="Kalender pemilihan tanggal MCU"
                            @unless($eligible) data-disabled="1" @endunless
                        >
                            <div class="mcu-quota-calendar__header">
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary mcu-quota-calendar__nav" id="calendar-prev" aria-label="Bulan sebelumnya" @unless($eligible) disabled @endunless>
                                    <i class="bx bx-chevron-left"></i>
                                </button>
                                <div class="mcu-quota-calendar__title" id="calendar-month-label">—</div>
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary mcu-quota-calendar__nav" id="calendar-next" aria-label="Bulan berikutnya" @unless($eligible) disabled @endunless>
                                    <i class="bx bx-chevron-right"></i>
                                </button>
                            </div>

                            <div class="mcu-quota-calendar__weekdays">
                                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
                                    <div class="mcu-quota-calendar__weekday">{{ $weekday }}</div>
                                @endforeach
                            </div>

                            <div class="mcu-quota-calendar__grid" id="calendar-grid">
                                <div class="mcu-quota-calendar__loading text-muted">Memuat kalender...</div>
                            </div>
                        </div>

                        <input
                            type="hidden"
                            name="tanggal_pemeriksaan"
                            id="tanggal_pemeriksaan"
                            value="{{ old('tanggal_pemeriksaan') }}"
                            {{ $eligible ? 'required' : 'disabled' }}
                        >
                        <div id="quota-info" class="form-text mt-2"></div>
                        @error('tanggal_pemeriksaan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Jam Pemeriksaan *</label>
                        <input
                            type="time"
                            name="jam_pemeriksaan"
                            class="form-control @error('jam_pemeriksaan') is-invalid @enderror"
                            value="{{ old('jam_pemeriksaan', config('mcu.examination_hours.start', '07:30')) }}"
                            min="{{ config('mcu.examination_hours.start', '07:30') }}"
                            max="{{ config('mcu.examination_hours.end', '10:00') }}"
                            step="60"
                            {{ $eligible ? '' : 'disabled' }}
                            required
                        >
                        <div class="form-text">Jam pendaftaran: {{ config('mcu.examination_hours.start', '07:30') }} – {{ config('mcu.examination_hours.end', '10:00') }} WIB.</div>
                        @error('jam_pemeriksaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Lokasi Pemeriksaan *</label>
                        <input
                            type="text"
                            class="form-control bg-light"
                            value="{{ \App\Support\ScheduleExaminationTime::defaultLocation() }}"
                            readonly
                            tabindex="-1"
                            aria-readonly="true"
                        >
                        <div class="form-text">Lokasi pemeriksaan ditetapkan sistem dan tidak dapat diubah.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="catatan" class="form-control" rows="3" {{ $eligible ? '' : 'disabled' }}>{{ old('catatan') }}</textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" id="schedule-submit-btn" {{ $eligible ? '' : 'disabled' }}>
                        <i class="bx bx-send me-1"></i> Ajukan Jadwal
                    </button>
                    <a href="{{ route('client.schedules') }}" class="btn btn-outline-secondary ms-2">Kembali</a>
                </div>
            </form>
        </x-common.component-card>
    </div>

    <div class="col-lg-4 mb-4">
        <x-common.component-card title="Status Kelayakan">
            <dl class="row mb-0">
                <dt class="col-sm-5">Nama</dt>
                <dd class="col-sm-7">{{ $participant->nama_lengkap }}</dd>
                <dt class="col-sm-5">NIK</dt>
                <dd class="col-sm-7">{{ $participant->nik_ktp }}</dd>
                <dt class="col-sm-5">SKPD</dt>
                <dd class="col-sm-7">{{ $participant->skpd }}</dd>
                <dt class="col-sm-5">Skrining CKG</dt>
                <dd class="col-sm-7">
                    <span class="badge bg-label-{{ $hasCkgScreening ? 'success' : 'danger' }}">
                        {{ $hasCkgScreening ? 'Sudah' : 'Belum' }}
                    </span>
                </dd>
                <dt class="col-sm-5">CKG {{ now()->year }}</dt>
                <dd class="col-sm-7">
                    <span class="badge {{ $participant->ckgStatusBadgeClass() }}">
                        {{ $participant->ckgStatusLabel() }}
                    </span>
                </dd>
                <dt class="col-sm-5">Konfirmasi jadwal</dt>
                <dd class="col-sm-7">
                    @if($eligible)
                        <span class="badge bg-label-{{ $requiresAdminConfirmation ? 'warning' : 'success' }}">
                            {{ $requiresAdminConfirmation ? 'Menunggu admin' : 'Otomatis' }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </dd>
                <dt class="col-sm-5">MCU Terakhir</dt>
                <dd class="col-sm-7">{{ $participant->tanggal_mcu_terakhir_formatted }}</dd>
                @if($participant->isWithinMcuInterval())
                    <dt class="col-sm-5">Pengajuan ulang</dt>
                    <dd class="col-sm-7">
                        <span class="text-muted">Mulai {{ $participant->mcuEligibleFrom()?->format('d/m/Y') ?? '-' }}</span>
                    </dd>
                @endif
                @if($dailyQuota > 0)
                    <dt class="col-sm-5">Kuota / hari kerja</dt>
                    <dd class="col-sm-7">Maks. {{ number_format($dailyQuota, 0, ',', '.') }} peserta</dd>
                @endif
                <dt class="col-sm-5">Hari pemeriksaan</dt>
                <dd class="col-sm-7"><span class="text-muted">Senin–Jumat, di luar libur & cuti bersama</span></dd>
                <dt class="col-sm-5">Kelayakan</dt>
                <dd class="col-sm-7">
                    <span class="badge bg-label-{{ $eligible ? 'success' : 'warning' }}">
                        {{ $eligible ? 'Memenuhi syarat' : 'Belum memenuhi' }}
                    </span>
                </dd>
            </dl>
        </x-common.component-card>
    </div>
</div>
@endsection

@push('page-css')
<style>
.mcu-quota-calendar {
    border: 1px solid #e7e9ed;
    border-radius: 0.5rem;
    padding: 0.65rem 0.75rem 0.75rem;
    background: #fff;
    max-width: 360px;
}
.mcu-quota-calendar.is-invalid {
    border-color: var(--bs-danger);
}
.mcu-quota-calendar__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}
.mcu-quota-calendar__title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #566a7f;
}
.mcu-quota-calendar__nav {
    width: 1.75rem;
    height: 1.75rem;
    padding: 0;
}
.mcu-quota-calendar__weekdays,
.mcu-quota-calendar__grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.2rem;
}
.mcu-quota-calendar__weekdays {
    margin-bottom: 0.25rem;
}
.mcu-quota-calendar__weekday {
    text-align: center;
    font-size: 0.68rem;
    font-weight: 600;
    color: #a1acb8;
    padding: 0.1rem 0;
}
.mcu-quota-calendar__cell {
    border: 1.5px solid transparent;
    border-radius: 0.4rem;
    min-height: 2.35rem;
    padding: 0.15rem 0.1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: transparent;
    cursor: default;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}
.mcu-quota-calendar__cell--empty {
    visibility: hidden;
    min-height: 0;
    padding: 0;
}
.mcu-quota-calendar__cell--selectable {
    cursor: pointer;
}
.mcu-quota-calendar__cell--selectable:hover {
    background: #f8f9fa;
}
.mcu-quota-calendar__cell--selected {
    border-color: #20c997;
    background: #f0fdf9;
}
.mcu-quota-calendar__cell--selected .mcu-quota-calendar__day,
.mcu-quota-calendar__cell--selected .mcu-quota-calendar__quota {
    color: #20c997;
}
.mcu-quota-calendar__cell--past .mcu-quota-calendar__day {
    color: #c7cdd4;
}
.mcu-quota-calendar__cell--past .mcu-quota-calendar__quota {
    color: #d0d6dc;
}
.mcu-quota-calendar__day {
    font-weight: 700;
    font-size: 0.8rem;
    line-height: 1.1;
    color: #566a7f;
}
.mcu-quota-calendar__quota {
    font-size: 0.65rem;
    line-height: 1.1;
    color: #a1acb8;
    margin-top: 0.05rem;
}
.mcu-quota-calendar__quota--closed {
    color: #ff3e1d;
    font-weight: 600;
}
.mcu-quota-calendar__quota--full {
    color: #ff3e1d;
}
.mcu-quota-calendar__loading {
    grid-column: 1 / -1;
    text-align: center;
    padding: 1rem 0;
    font-size: 0.8rem;
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    const calendarEl = document.getElementById('mcu-quota-calendar');
    const gridEl = document.getElementById('calendar-grid');
    const monthLabelEl = document.getElementById('calendar-month-label');
    const prevBtn = document.getElementById('calendar-prev');
    const nextBtn = document.getElementById('calendar-next');
    const dateInput = document.getElementById('tanggal_pemeriksaan');
    const quotaInfo = document.getElementById('quota-info');
    const submitBtn = document.getElementById('schedule-submit-btn');
    const monthUrl = @json(route('client.schedule.quota-month'));
    const formEnabled = @json($eligible);
    const initialDate = @json(old('tanggal_pemeriksaan'));

    if (!calendarEl || !gridEl || !formEnabled) {
        return;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let viewYear = today.getFullYear();
    let viewMonth = today.getMonth() + 1;
    let selectedDate = initialDate || '';
    let monthData = null;

    if (initialDate) {
        const parts = initialDate.split('-');
        if (parts.length === 3) {
            viewYear = parseInt(parts[0], 10);
            viewMonth = parseInt(parts[1], 10);
        }
    }

    function monthKey(year, month) {
        return year * 100 + month;
    }

    function earliestMonthKey() {
        return monthKey(today.getFullYear(), today.getMonth() + 1);
    }

    function updateNavButtons() {
        const current = monthKey(viewYear, viewMonth);
        if (prevBtn) {
            prevBtn.disabled = current <= earliestMonthKey();
        }
    }

    function isoWeekday(year, month, day) {
        const date = new Date(year, month - 1, day);
        const dow = date.getDay();
        return dow === 0 ? 7 : dow;
    }

    function formatQuotaLabel(day) {
        if (day.is_closed) {
            return { text: '—', className: 'mcu-quota-calendar__quota--closed' };
        }
        if (day.unlimited) {
            return { text: '∞', className: '' };
        }
        if (day.is_past) {
            return { text: String(day.remaining ?? 0), className: '' };
        }
        if (!day.available && day.bookable) {
            return { text: '0', className: 'mcu-quota-calendar__quota--full' };
        }
        return { text: String(day.remaining ?? 0), className: '' };
    }

    function renderCalendar(data) {
        monthData = data;
        monthLabelEl.textContent = data.month_label;
        gridEl.innerHTML = '';

        const daysByDate = {};
        data.days.forEach((day) => {
            daysByDate[day.date] = day;
        });

        const firstWeekday = isoWeekday(data.year, data.month, 1);
        for (let i = 1; i < firstWeekday; i++) {
            const empty = document.createElement('div');
            empty.className = 'mcu-quota-calendar__cell mcu-quota-calendar__cell--empty';
            empty.setAttribute('aria-hidden', 'true');
            gridEl.appendChild(empty);
        }

        data.days.forEach((day) => {
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'mcu-quota-calendar__cell';
            cell.dataset.date = day.date;

            const dayNum = document.createElement('span');
            dayNum.className = 'mcu-quota-calendar__day';
            dayNum.textContent = String(day.day);

            const quota = document.createElement('span');
            const quotaLabel = formatQuotaLabel({ ...day, unlimited: data.unlimited });
            quota.className = 'mcu-quota-calendar__quota' + (quotaLabel.className ? ' ' + quotaLabel.className : '');
            quota.textContent = quotaLabel.text;

            cell.appendChild(dayNum);
            cell.appendChild(quota);

            if (day.is_past) {
                cell.classList.add('mcu-quota-calendar__cell--past');
                cell.disabled = true;
            } else if (day.available) {
                cell.classList.add('mcu-quota-calendar__cell--selectable');
            } else {
                cell.disabled = true;
                if (day.is_closed) {
                    cell.classList.add('mcu-quota-calendar__cell--past');
                }
            }

            if (selectedDate === day.date) {
                cell.classList.add('mcu-quota-calendar__cell--selected');
            }

            cell.addEventListener('click', () => selectDate(day));
            gridEl.appendChild(cell);
        });

        updateNavButtons();
        updateQuotaInfo();
    }

    function selectDate(day) {
        if (!day.available) {
            return;
        }

        selectedDate = day.date;
        dateInput.value = day.date;

        gridEl.querySelectorAll('.mcu-quota-calendar__cell--selected').forEach((el) => {
            el.classList.remove('mcu-quota-calendar__cell--selected');
        });

        const cell = gridEl.querySelector(`[data-date="${day.date}"]`);
        if (cell) {
            cell.classList.add('mcu-quota-calendar__cell--selected');
        }

        updateQuotaInfo(day);
    }

    function updateQuotaInfo(day) {
        if (!quotaInfo || !submitBtn) {
            return;
        }

        if (!selectedDate) {
            quotaInfo.textContent = 'Pilih tanggal pemeriksaan pada kalender.';
            quotaInfo.classList.remove('text-danger', 'text-success');
            submitBtn.disabled = true;
            return;
        }

        const info = day || (monthData?.days || []).find((d) => d.date === selectedDate);

        if (!info) {
            quotaInfo.textContent = '';
            submitBtn.disabled = false;
            return;
        }

        if (!info.available) {
            quotaInfo.textContent = info.bookable_reason || 'Tanggal tidak tersedia.';
            quotaInfo.classList.add('text-danger');
            quotaInfo.classList.remove('text-success');
            submitBtn.disabled = true;
            return;
        }

        if (monthData?.unlimited) {
            quotaInfo.textContent = `Tanggal dipilih: ${formatDisplayDate(selectedDate)}. Kuota harian tidak dibatasi.`;
        } else {
            quotaInfo.innerHTML = `Tanggal dipilih: <strong>${formatDisplayDate(selectedDate)}</strong>. Sisa kuota: <strong>${info.remaining}</strong> dari <strong>${monthData.limit}</strong> peserta.`;
        }

        quotaInfo.classList.remove('text-danger');
        quotaInfo.classList.add('text-success');
        submitBtn.disabled = false;
    }

    function formatDisplayDate(isoDate) {
        const [y, m, d] = isoDate.split('-');
        return `${d}/${m}/${y}`;
    }

    async function loadMonth(year, month) {
        gridEl.innerHTML = '<div class="mcu-quota-calendar__loading text-muted">Memuat kalender...</div>';
        updateNavButtons();

        try {
            const response = await fetch(`${monthUrl}?year=${year}&month=${month}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                throw new Error('Gagal memuat kalender');
            }

            const data = await response.json();
            viewYear = data.year;
            viewMonth = data.month;
            renderCalendar(data);

            if (selectedDate) {
                const stillValid = data.days.find((d) => d.date === selectedDate && d.available);
                if (!stillValid) {
                    selectedDate = '';
                    dateInput.value = '';
                }
            }
        } catch (error) {
            gridEl.innerHTML = '<div class="mcu-quota-calendar__loading text-danger">Gagal memuat kalender. Muat ulang halaman.</div>';
        }
    }

    prevBtn?.addEventListener('click', () => {
        let month = viewMonth - 1;
        let year = viewYear;
        if (month < 1) {
            month = 12;
            year -= 1;
        }
        if (monthKey(year, month) < earliestMonthKey()) {
            return;
        }
        loadMonth(year, month);
    });

    nextBtn?.addEventListener('click', () => {
        let month = viewMonth + 1;
        let year = viewYear;
        if (month > 12) {
            month = 1;
            year += 1;
        }
        loadMonth(year, month);
    });

    loadMonth(viewYear, viewMonth);
})();
</script>
@endpush
