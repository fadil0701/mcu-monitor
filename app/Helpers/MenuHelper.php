<?php

namespace App\Helpers;

use App\Models\User;
use App\Support\NotificationBadgeCounts;
use Illuminate\Support\Facades\Auth;

class MenuHelper
{
    public static function getMainNavItems(): array
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return [];
        }

        return $user->isAdmin()
            ? self::adminNavItems($user)
            : self::participantNavItems();
    }

    private static function adminNavItems(User $user): array
    {
        $mcuSubItems = [
            self::subLink('Data Peserta', route('admin.participants.index')),
            self::subLink('Jadwal MCU', route('admin.schedules.index')),
            self::subLink('Hasil MCU', route('admin.mcu-results.index')),
        ];

        if ($user->isSuperAdmin()) {
            $mcuSubItems[] = self::subLink(
                'Permintaan Reschedule',
                route('admin.reschedule-center.index'),
                NotificationBadgeCounts::pendingReschedules()
            );
        }

        $items = [
            self::header('Utama'),
            self::link('bx-home-circle', 'Dashboard', route('dashboard')),
            self::header('Operasional MCU'),
            self::group('bx-pulse', 'MCU & Peserta', $mcuSubItems),
            self::header('Integrasi'),
            self::link('bx-link-alt', 'Integrasi CKG', route('admin.ckg-bridge.index')),
            self::header('Laporan'),
            self::link('bx-bar-chart-alt-2', 'Laporan', route('admin.reports.index')),
        ];

        if ($user->isSuperAdmin()) {
            $items[] = self::header('Komunikasi');
            $items[] = self::group('bx-message-dots', 'Template Pesan', [
                self::subLink('WhatsApp', route('admin.whatsapp-templates.index')),
                self::subLink('Email', route('admin.email-templates.index')),
                self::subLink('PDF', route('admin.pdf-templates.index')),
            ]);
        }

        $administration = [
            self::subLink('Pengguna', route('admin.users.index')),
            self::subLink('Notifikasi', route('admin.notifications.index'), NotificationBadgeCounts::unreadFor()),
            self::subLink('Pengaturan', route('admin.settings.index')),
        ];

        if ($user->isSuperAdmin()) {
            $administration[] = self::subLink('Backup Database', route('admin.backup.index'));

            if (config('mcu.menu.master_data_enabled', false)) {
                $items[] = self::header('Master Data');
                $items[] = self::group('bx-book-content', 'Referensi Medis', [
                    self::subLink('Diagnosis', route('admin.diagnoses.index')),
                    self::subLink('Dokter Spesialis', route('admin.specialist-doctors.index')),
                ]);
            }
        }

        $items[] = self::header('Administrasi');
        $items[] = self::group('bx-cog', 'Sistem', $administration);

        return $items;
    }

    private static function participantNavItems(): array
    {
        return [
            self::header('Utama'),
            self::link('bx-home-circle', 'Dashboard', route('client.dashboard')),
            self::header('Layanan MCU'),
            self::link('bx-calendar', 'Jadwal MCU', route('client.schedules')),
            self::link('bx-file', 'Hasil MCU', route('client.results')),
            self::header('Akun'),
            self::link('bx-user', 'Profile Saya', route('client.profile')),
            self::link('bx-bell', 'Notifikasi', route('client.notifications.index'), NotificationBadgeCounts::unreadFor()),
        ];
    }

    private static function header(string $name): array
    {
        return ['type' => 'header', 'name' => $name];
    }

    private static function link(string $icon, string $name, string $path, int $badge = 0): array
    {
        $item = compact('icon', 'name', 'path');

        if ($badge > 0) {
            $item['badge'] = $badge;
        }

        return $item;
    }

    private static function group(string $icon, string $name, array $subItems): array
    {
        return compact('icon', 'name', 'subItems');
    }

    private static function subLink(string $name, string $path, int $badge = 0): array
    {
        $item = compact('name', 'path');

        if ($badge > 0) {
            $item['badge'] = $badge;
        }

        return $item;
    }

    public static function isActive(string $path): bool
    {
        if (str_starts_with($path, 'http')) {
            $path = parse_url($path, PHP_URL_PATH) ?? $path;
        }

        $path = ltrim($path, '/');
        $current = request()->path();

        return $current === $path || str_starts_with($current, $path.'/');
    }

    public static function isSubmenuActive(array $subItems): bool
    {
        foreach ($subItems as $sub) {
            if (self::isActive($sub['path'])) {
                return true;
            }
        }

        return false;
    }
}
