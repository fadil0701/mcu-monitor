<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PdfTemplate;

class PdfTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // MCU Letter Template based on the official Jakarta government format
        PdfTemplate::create([
            'name' => 'Surat Undangan MCU - Format Resmi',
            'type' => 'mcu_letter',
            'title' => 'Surat Pemberitahuan Pemeriksaan Medical Check Up (MCU)',
            'header_html' => '
                <div class="header">
                    <div class="logo-container">
                        {logo_image}
                        <div class="logo-circle" style="display: none;">
                            <div class="logo-text">JAYA<br>RAYA</div>
                        </div>
                    </div>
                    <div class="organization-info">
                        <div class="organization-name">{organization_name}</div>
                        <div class="organization-subtitle">{organization_subtitle}</div>
                        <div class="organization-subtitle2">{organization_subtitle2}</div>
                        <div class="organization-details">
                            {organization_address}. Telp. {organization_phone} Faks. {organization_fax}, Email: {organization_email}<br>
                            JAKARTA PUSAT
                        </div>
                    </div>
                    <div class="postal-code">
                        Kode Pos: {organization_postal_code}
                    </div>
                </div>
                
                <div class="letter-metadata">
                    <div class="letter-info">
                        <div><strong>Nomor</strong> : {letter_number}</div>
                        <div><strong>Sifat</strong> : Penting</div>
                        <div><strong>Lampiran</strong> : -</div>
                        <div><strong>Hal</strong> : Pemberitahuan Pemeriksaan Medical Check Up (MCU)</div>
                    </div>
                    <div class="letter-destination">
                        <div>Jakarta, {letter_date}</div>
                        <br>
                        <div>Kepada Yth. Bapak/Ibu {participant_name}</div>
                        <div>Pegawai {participant_skpd}</div>
                        <div>di - Jakarta</div>
                    </div>
                </div>
            ',
            'body_html' => '
                <p>Dengan hormat,</p>
                
                <p>Sehubungan dengan tindak lanjut hasil Cek Kesehatan Gratis (CKG) Pegawai Pemerintah Provinsi Daerah Khusus Ibukota Jakarta, disampaikan beberapa hal sebagai berikut:</p>
                
                <p><strong>1. Faktor Risiko</strong></p>
                <p>Terdapat <strong>2 (dua) atau lebih faktor risiko penyakit</strong> dari hasil pemeriksaan CKG peserta dengan data sebagai berikut:</p>
                
                <div class="participant-data">
                    <table class="participant-table">
                        <tr>
                            <td class="label">NIK KTP</td>
                            <td class="colon">:</td>
                            <td class="value">{participant_nik}</td>
                        </tr>
                        <tr>
                            <td class="label">Nama Lengkap</td>
                            <td class="colon">:</td>
                            <td class="value">{participant_name}</td>
                        </tr>
                        <tr>
                            <td class="label">Tanggal Lahir</td>
                            <td class="colon">:</td>
                            <td class="value">{participant_birth_date}</td>
                        </tr>
                        <tr>
                            <td class="label">Jenis Kelamin</td>
                            <td class="colon">:</td>
                            <td class="value">{participant_gender}</td>
                        </tr>
                        <tr>
                            <td class="label">SKPD</td>
                            <td class="colon">:</td>
                            <td class="value">{participant_skpd}</td>
                        </tr>
                        <tr>
                            <td class="label">Unit Kerja</td>
                            <td class="colon">:</td>
                            <td class="value">{participant_unit}</td>
                        </tr>
                    </table>
                </div>
                
                <p><strong>2. Tindak Lanjut MCU</strong></p>
                <p>Untuk tindak lanjut dari hasil CKG tersebut, maka peserta akan dilakukan pemeriksaan Medical Check Up (MCU) dengan ketentuan sebagai berikut:</p>
                
                <div class="examination-schedule">
                    <table class="examination-table">
                        <tr>
                            <td class="label">Hari</td>
                            <td class="colon">:</td>
                            <td class="value">{examination_day}</td>
                        </tr>
                        <tr>
                            <td class="label">Tanggal</td>
                            <td class="colon">:</td>
                            <td class="value">{examination_date}</td>
                        </tr>
                        <tr>
                            <td class="label">Waktu</td>
                            <td class="colon">:</td>
                            <td class="value">{examination_time}</td>
                        </tr>
                        <tr>
                            <td class="label">Tempat</td>
                            <td class="colon">:</td>
                            <td class="value">{examination_location}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="contact-information">
                    <p>Untuk konfirmasi lebih lanjut dapat menghubungi <strong>{contact_person} (Hp. {contact_phone})</strong> selaku PIC kegiatan.</p>
                </div>
                
                <p>Demikian surat pemberitahuan ini disampaikan, atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>
            ',
            'footer_html' => '
                <div class="footer-section">
                    <div class="signature-area">
                        <div class="signature-container">
                            {signature_image}
                            <div class="signature-line" style="display: none;"></div>
                            <div class="signature-name">{signature_name}</div>
                            <div class="signature-title">{signature_title}</div>
                            <div class="signature-nip">NIP. {signature_nip}</div>
                        </div>
                    </div>
                    <div class="official-stamp">
                        {stamp_image}
                        <div class="stamp-circle" style="display: none;">
                            <div class="stamp-text">
                                PEMERINTAH<br>
                                PROVINSI DKI<br>
                                JAKARTA<br>
                                PUSAT PELAYANAN<br>
                                KESEHATAN PEGAWAI
                            </div>
                        </div>
                    </div>
                </div>
            ',
            'variables' => [
                'letter_number' => 'Nomor surat',
                'letter_date' => 'Tanggal surat',
                'participant_name' => 'Nama peserta',
                'participant_nik' => 'NIK peserta',
                'participant_birth_date' => 'Tanggal lahir peserta',
                'participant_gender' => 'Jenis kelamin peserta',
                'participant_skpd' => 'SKPD peserta',
                'participant_unit' => 'Unit kerja peserta',
                'examination_day' => 'Hari pemeriksaan',
                'examination_date' => 'Tanggal pemeriksaan',
                'examination_time' => 'Waktu pemeriksaan',
                'examination_location' => 'Lokasi pemeriksaan',
                'contact_person' => 'Nama PIC',
                'contact_phone' => 'Nomor telepon PIC',
                'organization_name' => 'Nama organisasi',
                'organization_subtitle' => 'Subtitle organisasi',
                'organization_subtitle2' => 'Subtitle organisasi 2',
                'organization_address' => 'Alamat organisasi',
                'organization_phone' => 'Nomor telepon organisasi',
                'organization_fax' => 'Nomor fax organisasi',
                'organization_email' => 'Email organisasi',
                'organization_postal_code' => 'Kode pos',
                'signature_name' => 'Nama penandatangan',
                'signature_title' => 'Jabatan penandatangan',
                'signature_nip' => 'NIP penandatangan',
            ],
            'settings' => [
                'paper' => 'A4',
                'orientation' => 'portrait',
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 20,
                'margin_right' => 20,
                'font_family' => 'DejaVu Sans',
                'font_size' => 11,
            ],
            'logo_path' => null,
            'signature_image_path' => null,
            'stamp_image_path' => null,
            'image_settings' => [
                'logo' => [
                    'width' => 60,
                    'height' => 60,
                    'position' => 'header-left'
                ],
                'signature' => [
                    'width' => 120,
                    'height' => 80,
                    'position' => 'footer-left'
                ],
                'stamp' => [
                    'width' => 80,
                    'height' => 80,
                    'position' => 'footer-right'
                ]
            ],
            'is_active' => true,
            'is_default' => true,
            'description' => 'Template surat undangan MCU berdasarkan format resmi Pemerintah Provinsi DKI Jakarta',
        ]);

        // Simple MCU Letter Template
        PdfTemplate::create([
            'name' => 'Surat Undangan MCU - Format Sederhana',
            'type' => 'mcu_letter',
            'title' => 'Surat Undangan Medical Check Up',
            'header_html' => '
                <div class="header">
                    <div class="organization-name">{organization_name}</div>
                    <div class="organization-subtitle">{organization_subtitle}</div>
                    <div class="organization-info">
                        {organization_address}<br>
                        Telp. {organization_phone} | Email: {organization_email}
                    </div>
                </div>
                
                <div style="margin: 20px 0; padding: 10px; border: 1px solid #000;">
                    <div><strong>Nomor</strong> : {letter_number}</div>
                    <div><strong>Tanggal</strong> : {letter_date}</div>
                    <div><strong>Hal</strong> : Undangan Medical Check Up</div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <p>Kepada Yth. Bapak/Ibu <strong>{participant_name}</strong></p>
                    <p>{participant_skpd}</p>
                    <p>di Jakarta</p>
                </div>
            ',
            'body_html' => '
                <p>Dengan hormat,</p>
                
                <p>Berdasarkan hasil pemeriksaan kesehatan sebelumnya, Anda diundang untuk mengikuti kegiatan Medical Check Up (MCU) dengan detail sebagai berikut:</p>
                
                <div class="participant-info">
                    <table>
                        <tr><td>Nama Lengkap</td><td>: {participant_name}</td></tr>
                        <tr><td>NIK</td><td>: {participant_nik}</td></tr>
                        <tr><td>SKPD</td><td>: {participant_skpd}</td></tr>
                        <tr><td>Unit Kerja</td><td>: {participant_unit}</td></tr>
                    </table>
                </div>
                
                <div class="examination-details">
                    <table>
                        <tr><td>Hari</td><td>: {examination_day}</td></tr>
                        <tr><td>Tanggal</td><td>: {examination_date}</td></tr>
                        <tr><td>Waktu</td><td>: {examination_time}</td></tr>
                        <tr><td>Lokasi</td><td>: {examination_location}</td></tr>
                    </table>
                </div>
                
                <p><strong>Mohon untuk:</strong></p>
                <ul>
                    <li>Hadir tepat waktu sesuai jadwal yang telah ditentukan</li>
                    <li>Membawa dokumen identitas (KTP/NRK)</li>
                    <li>Berpuasa 8-12 jam sebelum pemeriksaan (jika diperlukan)</li>
                    <li>Menghubungi kami jika ada halangan atau perubahan jadwal</li>
                </ul>
                
                <div class="contact-info">
                    <p>Untuk informasi lebih lanjut, dapat menghubungi <strong>{contact_person} ({contact_phone})</strong>.</p>
                </div>
                
                <p>Terima kasih atas perhatian dan kerjasamanya.</p>
            ',
            'footer_html' => '
                <div class="footer">
                    <div class="signature">
                        <div class="signature-name">{signature_name}</div>
                        <div class="signature-title">{signature_title}</div>
                        <div class="signature-nip">NIP. {signature_nip}</div>
                    </div>
                </div>
            ',
            'variables' => [
                'letter_number' => 'Nomor surat',
                'letter_date' => 'Tanggal surat',
                'participant_name' => 'Nama peserta',
                'participant_nik' => 'NIK peserta',
                'participant_skpd' => 'SKPD peserta',
                'participant_unit' => 'Unit kerja peserta',
                'examination_day' => 'Hari pemeriksaan',
                'examination_date' => 'Tanggal pemeriksaan',
                'examination_time' => 'Waktu pemeriksaan',
                'examination_location' => 'Lokasi pemeriksaan',
                'contact_person' => 'Nama PIC',
                'contact_phone' => 'Nomor telepon PIC',
                'organization_name' => 'Nama organisasi',
                'organization_subtitle' => 'Subtitle organisasi',
                'organization_address' => 'Alamat organisasi',
                'organization_phone' => 'Nomor telepon organisasi',
                'organization_email' => 'Email organisasi',
                'signature_name' => 'Nama penandatangan',
                'signature_title' => 'Jabatan penandatangan',
                'signature_nip' => 'NIP penandatangan',
            ],
            'settings' => [
                'paper' => 'A4',
                'orientation' => 'portrait',
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_left' => 20,
                'margin_right' => 20,
                'font_family' => 'DejaVu Sans',
                'font_size' => 12,
            ],
            'is_active' => true,
            'is_default' => false,
            'description' => 'Template surat undangan MCU format sederhana',
        ]);
    }
}
