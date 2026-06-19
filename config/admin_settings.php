<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Definisi form Pengaturan Admin (label ramah pengguna)
    |--------------------------------------------------------------------------
    */

    'sections' => [

        'general' => [
            'label' => 'Umum',
            'icon' => 'bx-cog',
            'description' => 'Informasi dasar aplikasi monitoring MCU.',
            'fields' => [
                'app_name' => [
                    'label' => 'Nama Aplikasi',
                    'type' => 'text',
                    'group' => 'general',
                    'storage_type' => 'string',
                    'rules' => 'required|string|max:255',
                    'help' => 'Nama yang ditampilkan di sistem.',
                ],
                'app_description' => [
                    'label' => 'Deskripsi Aplikasi',
                    'type' => 'text',
                    'group' => 'general',
                    'storage_type' => 'string',
                    'rules' => 'nullable|string|max:500',
                ],
                'mcu_interval_years' => [
                    'label' => 'Interval MCU (Tahun)',
                    'type' => 'number',
                    'group' => 'general',
                    'storage_type' => 'string',
                    'rules' => 'required|integer|min:1|max:10',
                    'help' => 'Jarak waktu minimum antar pemeriksaan MCU.',
                ],
            ],
        ],

        'email' => [
            'label' => 'Email (SMTP)',
            'icon' => 'bx-envelope',
            'description' => 'Konfigurasi server email untuk undangan dan notifikasi.',
            'fields' => [
                'smtp_host' => [
                    'label' => 'Host SMTP',
                    'type' => 'text',
                    'group' => 'smtp',
                    'storage_type' => 'string',
                    'rules' => 'required|string|max:255',
                    'placeholder' => 'mail.example.com',
                ],
                'smtp_port' => [
                    'label' => 'Port',
                    'type' => 'number',
                    'group' => 'smtp',
                    'storage_type' => 'string',
                    'rules' => 'required|string|max:10',
                    'placeholder' => '465',
                ],
                'smtp_encryption' => [
                    'label' => 'Enkripsi',
                    'type' => 'select',
                    'group' => 'smtp',
                    'storage_type' => 'string',
                    'rules' => 'required|in:ssl,tls,none',
                    'options' => ['ssl' => 'SSL', 'tls' => 'TLS', 'none' => 'Tanpa enkripsi'],
                ],
                'smtp_username' => [
                    'label' => 'Username / Email SMTP',
                    'type' => 'text',
                    'group' => 'smtp',
                    'storage_type' => 'string',
                    'rules' => 'nullable|string|max:255',
                ],
                'smtp_password' => [
                    'label' => 'Password SMTP',
                    'type' => 'password',
                    'group' => 'smtp',
                    'storage_type' => 'string',
                    'rules' => 'nullable|string|max:255',
                    'help' => 'Kosongkan jika tidak ingin mengubah password.',
                ],
                'smtp_from_address' => [
                    'label' => 'Email Pengirim',
                    'type' => 'email',
                    'group' => 'smtp',
                    'storage_type' => 'string',
                    'rules' => 'required|email|max:255',
                ],
                'smtp_from_name' => [
                    'label' => 'Nama Pengirim',
                    'type' => 'text',
                    'group' => 'smtp',
                    'storage_type' => 'string',
                    'rules' => 'required|string|max:255',
                ],
            ],
        ],

        'whatsapp' => [
            'label' => 'WhatsApp',
            'icon' => 'bxl-whatsapp',
            'description' => 'Koneksi API WhatsApp untuk pengiriman undangan dan hasil MCU.',
            'fields' => [
                'whatsapp_send_enabled' => [
                    'label' => 'Aktifkan Tombol Kirim WhatsApp',
                    'type' => 'boolean',
                    'group' => 'whatsapp',
                    'storage_type' => 'boolean',
                    'rules' => 'nullable|boolean',
                    'default' => false,
                    'help' => 'Jika aktif, tombol kirim WA muncul di Jadwal MCU dan Hasil MCU. Jika nonaktif, tombol disembunyikan (pengiriman otomatis bulk tidak terpengaruh).',
                ],
                'whatsapp_provider' => [
                    'label' => 'Penyedia Layanan',
                    'type' => 'select',
                    'group' => 'whatsapp',
                    'storage_type' => 'string',
                    'rules' => 'required|in:fonnte,wablas,meta,apico',
                    'options' => [
                        'fonnte' => 'Fonnte',
                        'wablas' => 'Wablas',
                        'meta' => 'Meta (WhatsApp Business API)',
                        'apico' => 'Api.co.id Chat Gateway',
                    ],
                ],
                'whatsapp_token' => [
                    'label' => 'API Token / API Key',
                    'type' => 'password',
                    'group' => 'whatsapp',
                    'storage_type' => 'string',
                    'rules' => 'nullable|string|max:500',
                    'help' => 'Bearer token dari penyedia. Untuk Api.co.id: Developers → API Keys. Kosongkan jika tidak ingin mengubah.',
                ],
                'whatsapp_instance_id' => [
                    'label' => 'Phone Number ID',
                    'type' => 'text',
                    'group' => 'whatsapp',
                    'storage_type' => 'string',
                    'rules' => 'nullable|string|max:255',
                    'help' => 'Api.co.id: salin field **id** (bukan phone_number_id) dari GET /phone-numbers. Contoh: cmqj83dp7ebifo8dyegp1b6ki. Bukan WABA ID Meta.',
                ],
                'whatsapp_phone_number' => [
                    'label' => 'Nomor WhatsApp Pengirim',
                    'type' => 'text',
                    'group' => 'whatsapp',
                    'storage_type' => 'string',
                    'rules' => 'nullable|string|max:20',
                    'placeholder' => '628xxxxxxxxxx',
                ],
                'whatsapp_apico_template_name' => [
                    'label' => 'Nama Template WA (Undangan)',
                    'type' => 'text',
                    'group' => 'whatsapp',
                    'storage_type' => 'string',
                    'rules' => 'nullable|string|max:255',
                    'help' => 'Wajib untuk Api.co.id: nama template Meta yang APPROVED & sudah di-sync. Contoh isi body template: Halo {{1}}, undangan MCU {{2}} jam {{3}} di {{4}}, antrian {{5}}.',
                    'placeholder' => 'mcu_undangan',
                ],
                'whatsapp_apico_template_language' => [
                    'label' => 'Bahasa Template WA (Undangan)',
                    'type' => 'text',
                    'group' => 'whatsapp',
                    'storage_type' => 'string',
                    'rules' => 'nullable|string|max:10',
                    'default' => 'id',
                    'placeholder' => 'id',
                    'help' => 'Harus sama persis dengan kolom Language template undangan di Api.co.id (biasanya id).',
                ],
                'whatsapp_apico_invitation_param_keys' => [
                    'label' => 'Variabel Template Undangan',
                    'type' => 'text',
                    'group' => 'whatsapp',
                    'storage_type' => 'string',
                    'rules' => 'nullable|string|max:500',
                    'placeholder' => 'nama_lengkap,tanggal_pemeriksaan,hari_pemeriksaan,jam_pemeriksaan,queue_number,lokasi_pemeriksaan',
                    'help' => 'Urutan {{1}}–{{6}} untuk template undangan_mcu_baru: nama, tanggal, hari, jam, nomor urut, tempat. Kosongkan {{1}}–{{6}} juga didukung.',
                ],
                'whatsapp_apico_result_template_name' => [
                    'label' => 'Nama Template WA (Hasil MCU)',
                    'type' => 'text',
                    'group' => 'whatsapp',
                    'storage_type' => 'string',
                    'rules' => 'nullable|string|max:255',
                    'placeholder' => 'mcu_hasil',
                    'help' => 'Nama template Meta APPROVED untuk notifikasi hasil MCU.',
                ],
                'whatsapp_apico_result_template_language' => [
                    'label' => 'Bahasa Template WA (Hasil MCU)',
                    'type' => 'text',
                    'group' => 'whatsapp',
                    'storage_type' => 'string',
                    'rules' => 'nullable|string|max:10',
                    'default' => 'en_US',
                    'placeholder' => 'en_US',
                    'help' => 'Harus sama dengan Language template hasil di Api.co.id. Jika template didaftarkan en_US, isi en_US (bukan id).',
                ],
                'whatsapp_apico_result_param_keys' => [
                    'label' => 'Variabel Template Hasil MCU',
                    'type' => 'text',
                    'group' => 'whatsapp',
                    'storage_type' => 'string',
                    'rules' => 'nullable|string|max:500',
                    'placeholder' => 'participant_name,tanggal_pemeriksaan,hasil_url',
                    'help' => 'Urutan {{1}}, {{2}}, {{3}}. Default: participant_name,tanggal_pemeriksaan,hasil_url.',
                ],
            ],
        ],

        'invitation' => [
            'label' => 'Template Undangan',
            'icon' => 'bx-mail-send',
            'description' => 'Pesan undangan MCU yang dikirim otomatis ke peserta.',
            'fields' => [
                'email_invitation_subject' => [
                    'label' => 'Subject Email Undangan',
                    'type' => 'text',
                    'group' => 'email_template',
                    'storage_type' => 'string',
                    'rules' => 'required|string|max:255',
                ],
                'email_invitation_template' => [
                    'label' => 'Isi Email Undangan',
                    'type' => 'textarea',
                    'group' => 'email_template',
                    'storage_type' => 'text',
                    'rules' => 'required|string',
                    'rows' => 10,
                    'help' => 'Variabel: {nama_lengkap}, {tanggal_pemeriksaan}, {jam_pemeriksaan}, {lokasi_pemeriksaan}, {queue_number}, {nik_ktp}, {skpd}',
                ],
                'whatsapp_invitation_template' => [
                    'label' => 'Pesan WhatsApp Undangan',
                    'type' => 'textarea',
                    'group' => 'whatsapp_template',
                    'storage_type' => 'text',
                    'rules' => 'required|string',
                    'rows' => 10,
                    'help' => 'Format Meta: {{1}}, {{2}}, dst. Pemetaan: {{1}}=nama_lengkap, {{2}}=tanggal_pemeriksaan, {{3}}=jam_pemeriksaan, {{4}}=lokasi_pemeriksaan, {{5}}=queue_number. Salin isi ini ke template Meta di Api.co.id.',
                ],
            ],
        ],

    ],

    'links' => [
        [
            'label' => 'Template Email Lanjutan',
            'description' => 'Kelola template email HTML untuk berbagai keperluan.',
            'icon' => 'bx-envelope-open',
            'route' => 'admin.email-templates.index',
            'super_admin_only' => true,
        ],
        [
            'label' => 'Template WhatsApp Hasil MCU',
            'description' => 'Atur pesan WhatsApp saat hasil MCU dikirim ke peserta.',
            'icon' => 'bxl-whatsapp',
            'route' => 'admin.whatsapp-templates.index',
            'super_admin_only' => true,
        ],
        [
            'label' => 'Template Email Hasil MCU',
            'description' => 'Fallback email plain-text untuk pengiriman hasil MCU.',
            'icon' => 'bx-file',
            'route' => 'admin.settings.email-result-template',
            'super_admin_only' => true,
        ],
    ],

];
