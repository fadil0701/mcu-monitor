# Bridge CKG ↔ MCU setelah migrasi PostgreSQL

Bridging **tidak** memakai koneksi database silang. Alur tetap HTTP:

```
dashboard-skrining (sikerja_ppkp)          mcu-monitor (mcu_monitor)
  GET /api/bridge/mcu/participants  ◄──────  CkgParticipantSyncService
  mcu_bridge_configs (API key)               ckg_bridge_configs (base URL + key)
```

Migrasi PG **per aplikasi** harus menyertakan tabel bridge agar integrasi tetap jalan.

## API key — lewat UI (disarankan)

**Jangan** mengandalkan `MCU_API_KEY` / `CKG_API_KEY` di `.env` untuk operasional harian.

### 1. Dashboard CKG (sumber API)

1. Login **super admin** → **Integrasi → Bridging Monitoring MCU**
2. Klik **Generate API key baru** — key ditampilkan sekali; bridge otomatis aktif
3. Salin key tersebut

### 2. MCU Monitor (konsumen)

1. Login admin → **Integrasi CKG**
2. Isi **URL Portal CKG** (lokal: `http://host.docker.internal:9006`)
3. **Tempel API key** dari langkah 1
4. Header tetap **`X-Mcu-Api-Key`** (readonly)
5. Centang **Aktifkan konfigurasi database** → **Simpan**
6. **Tes koneksi CKG** → **Jalankan sinkron sekarang**

Rotasi key: ulangi generate di CKG, tempel key baru di MCU, simpan.

## Tabel yang ikut migrasi

| Aplikasi | Tabel bridge | Command migrasi data |
|----------|--------------|----------------------|
| dashboard-skrining | `mcu_bridge_configs`, `mcu_bridge_access_logs` | `sikerja:migrate-mysql-to-pgsql` |
| mcu-monitor | `ckg_bridge_configs`, `ckg_bridge_sync_logs` | `mcu:migrate-mysql-to-pgsql` |

Setelah migrasi, jika key lama tidak bisa didekripsi (`APP_KEY` berubah), **generate ulang** lewat UI CKG lalu tempel ke MCU.

## Checklist setelah migrasi

### Dashboard (sumber API)

```powershell
cd E:\laragon\www\dashboard-skrining
docker compose exec app php artisan tinker --execute="echo App\Models\McuBridgeConfig::current()?->is_active ? 'bridge aktif' : 'nonaktif';"
```

- Menu **Bridging MCU**: generate atau isi API key, pastikan aktif
- Endpoint: `http://<host>:9006/api/bridge/mcu/health`

### MCU (konsumen)

`.env` hanya untuk **base URL** fallback (opsional):

```env
CKG_API_BASE_URL=http://host.docker.internal:9006
CKG_BRIDGE_DISABLE_PROXY=true
```

API key utama: form **Integrasi CKG** (database).

```powershell
cd E:\laragon\www\mcu-monitor
docker compose exec app php artisan ckg-bridge:verify
docker compose exec app php artisan ckg:sync-participants-from-ckg --no-interaction
```

## Verifikasi cepat

```powershell
docker compose exec app php artisan ckg-bridge:verify
docker compose exec app php artisan tinker --execute="echo App\Models\Participant::count();"
```

## Docker lokal (Windows)

| Dari | Ke | URL |
|------|-----|-----|
| `monitoring-mcu-app` | dashboard | `http://host.docker.internal:9006` |
| Browser | dashboard | `http://127.0.0.1:9006` |
| Browser | MCU | `http://127.0.0.1:9002` (sesuaikan `APP_PORT`) |

## Troubleshooting

| Gejala | Penyebab | Solusi |
|--------|----------|--------|
| HTTP 401 dari CKG | Key tidak cocok / header salah | Generate ulang di CKG, tempel ke MCU, header `X-Mcu-Api-Key` |
| HTTP 500 dari CKG | Key DB tidak terdekripsi | Generate API key baru di UI CKG |
| Connection refused | URL salah / dashboard down | Cek URL di Integrasi CKG, `docker ps` port 9006 |
| Proxy timeout | Request lewat FortiGate | `CKG_BRIDGE_DISABLE_PROXY=true` |
| Peserta 0 setelah sync | Filter eligibility CKG | Hanya ASN DKI + pns/cpns/pppk |
| `duplicate key` pada `ckg_bridge_sync_logs_pkey` | Sequence PG tidak sinkron setelah migrasi MySQL | `php artisan mcu:fix-pgsql-sequences` lalu ulangi sync |

Spesifikasi lengkap: `dashboard-skrining/docs/BRIDGE-CKG-MCU.md`
