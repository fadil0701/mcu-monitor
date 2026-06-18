# Referensi WhatsApp — Api.co.id (template, commit ke Git)

> Salin ke `docs/API_WA.MD` di server/lokal dan isi credential asli.  
> **Jangan commit** `API_WA.MD` jika berisi API Key (lihat `.gitignore`).

## Koneksi API

| Item | Nilai |
|------|--------|
| Penyedia | api.co.id |
| BASE URL | `https://chat.api.co.id/api/v1/public` |
| Headers | `Authorization: Bearer YOUR_API_KEY` · `Content-Type: application/json` |
| API_KEY | *(Developers → API Keys)* |
| PHONE NUMBER ID | *(GET `/phone-numbers` → field **`id`**, bukan `phone_number_id`)* |
| PHONE NUMBER | `628xxxxxxxxxx` |
| Bahasa template | `id` |

Postman: [`Api.co.id-Chat-Gateway.postman_collection.json`](./Api.co.id-Chat-Gateway.postman_collection.json)

---

## Pemetaan → Admin MCU (Pengaturan → WhatsApp)

| API_WA.MD / Api.co.id | Field di admin |
|----------------------|----------------|
| API_KEY | API Token / API Key |
| PHONE NUMBER ID | Phone Number ID |
| PHONE NUMBER | Nomor WhatsApp Pengirim |
| Bahasa `id` | Bahasa Template WA |
| `mcu_undangan` | Nama Template WA (Undangan) |
| `mcu_hasil` | Nama Template WA (Hasil MCU) |
| Variabel undangan | `nama_lengkap,tanggal_pemeriksaan,jam_pemeriksaan,lokasi_pemeriksaan,queue_number` |
| Variabel hasil | `participant_name,tanggal_pemeriksaan,hasil_url` |

**Bukan Phone Number ID:** User ID (`cmqj0glo8…`), nomor HP saja, WABA ID Meta (kecuali sama dengan `id` dari List Phone Numbers).

---

## Dapatkan Phone Number ID

```
GET https://chat.api.co.id/api/v1/public/phone-numbers
Authorization: Bearer <API_KEY>
```

Postman: **Account → List Phone Numbers** → salin **`data[].id`** (bukan `phone_number_id`).

Contoh response Api.co.id:

```json
{
  "id": "cmqj83dp7ebifo8dyegp1b6ki",
  "phone_number_id": "1081054025088559",
  "display_phone_number": "+62 819-7960-786"
}
```

| Field response | Isi di admin Phone Number ID |
|----------------|------------------------------|
| **`id`** | ✅ `cmqj83dp7ebifo8dyegp1b6ki` |
| `phone_number_id` | ❌ Jangan (ID Meta internal) |
| `waba_id` | ❌ Jangan |

---

## Template Meta (harus APPROVED + sync Api.co.id)

| Template | Variabel body |
|----------|----------------|
| `mcu_undangan` | {{1}} nama · {{2}} tanggal · {{3}} jam · {{4}} lokasi · {{5}} antrian |
| `mcu_hasil` | {{1}} nama · {{2}} tanggal · {{3}} URL hasil |

Kirim: `POST /messages/send` dengan `message_type: "template"` — lihat Postman collection.

Implementasi aplikasi: `app/Services/WhatsAppService.php` (provider `apico`).

---

## Troubleshooting

| Error | Penyebab | Tindakan |
|-------|----------|----------|
| `WhatsApp account not found or not owned by user` | Salah field: pakai **`id`** bukan **`phone_number_id`** dari `/phone-numbers` | Ganti Phone Number ID ke `cmqj83…` (bukan `108105…`) |
| API Key salah / token lama | Field token dikosongkan saat simpan (token lama tetap) | Pengaturan → WhatsApp → isi ulang API Token → Simpan |
| Nomor hanya di Meta, belum di Api.co.id | Akun WA belum connect ke Chat Gateway | Dashboard Api.co.id → hubungkan nomor WA (Cloud API) |
| `Template not found` | Nama template salah / belum APPROVED | Sync template di Api.co.id, cocokkan `mcu_undangan` / `mcu_hasil` |

### Verifikasi cepat (Postman)

1. Set `api_key` = API Key dari admin MCU (bukan key akun lain).
2. **Account → List Phone Numbers** — response harus `success: true` dan ada `data[]`.
3. Salin `data[0].id` (atau yang `is_primary: true`) → **Phone Number ID** di admin.
4. **Customers → Create Customer** — body pakai `whatsapp_phone_number_id` yang sama. Harus sukses sebelum kirim dari MCU.
5. **Send Template** — uji kirim ke nomor tes.

Jika langkah 2 kosong atau error → nomor belum terhubung ke akun Api.co.id yang memiliki API Key tersebut.
