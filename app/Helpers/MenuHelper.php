<?php

namespace App\Helpers;

use App\Models\User;
use App\Support\NotificationBadgeCounts;
use App\Support\UserRole;
use Illuminate\Support\Facades\Auth;

class MenuHelper
{
    public static function getMainNavItems(): array
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return [];
        }

        if (request()->is('client*') && UserRole::hasLinkedParticipant($user)) {
            $items = self::participantNavItems();

            if (UserRole::hasStaffAccess($user)) {
                array_unshift($items, self::link('bx-grid-alt', 'Panel Admin', route('dashboard')));
            }

            return $items;
        }

        if (UserRole::hasStaffAccess($user)) {
            return self::staffNavItems($user);
        }

        return self::participantNavItems();
    }

    private static function staffNavItems(User $user): array
    {
        $items = [
            self::link('bx-home-circle', 'Dashboard', route('dashboard')),
        ];

        if (UserRole::isSuperAdmin($user) || UserRole::isAdmin($user)) {
            $mcuSubItems = [
                self::subLink('Data Peserta', route('admin.participants.index')),
                self::subLink('Jadwal MCU', route('admin.schedules.index')),
                self::subLink('Hasil MCU', route('admin.mcu-results.index')),
            ];

            if (UserRole::isSuperAdmin($user)) {
                $mcuSubItems[] = self::subLink(
                    'Permintaan Reschedule',
                    route('admin.reschedule-center.index'),
                    NotificationBadgeCounts::pendingReschedules()
                );
            }

            $items[] = self::group('bx-pulse', 'MCU & Peserta', $mcuSubItems);
        } elseif (UserRole::isPimpinan($user)) {
            $mcuSubItems = [
                self::subLink('Jadwal MCU', route('admin.schedules.index')),
                self::subLink('Hasil MCU', route('admin.mcu-results.index')),
                self::subLink(
                    'Permintaan Reschedule',
                    route('admin.reschedule-center.index'),
                    NotificationBadgeCounts::pendingReschedules()
                ),
            ];
            $items[] = self::group('bx-pulse', 'MCU', $mcuSubItems);
        }

        $items[] = self::group('bx-calendar', 'Kuota & Jadwal', [
            self::subLink('Kuota MCU', route('admin.settings.index', ['tab' => 'schedule_quota'])),
            self::subLink('Kalender Libur', route('admin.mcu-work-calendar.index')),
        ]);

        if (UserRole::isSuperAdmin($user)) {
            $items[] = self::link('bx-link-alt', 'Integrasi CKG', route('admin.ckg-bridge.index'));
        }

        $items[] = self::link('bx-bar-chart-alt-2', 'Laporan', route('admin.reports.index'));

        if (UserRole::isSuperAdmin($user)) {
            $items[] = self::group('bx-message-dots', 'Template Pesan', [
                self::subLink('WhatsApp', route('admin.whatsapp-templates.index')),
                self::subLink('Email', route('admin.email-templates.index')),
                self::subLink('PDF', route('admin.pdf-templates.index')),
            ]);
        }

        if (UserRole::isSuperAdmin($user) || UserRole::isAdmin($user)) {
            $administration = [
                self::subLink('Pengguna', route('admin.users.index')),
                self::subLink('Notifikasi', route('admin.notifications.index'), NotificationBadgeCounts::unreadFor()),
            ];

            if (UserRole::isSuperAdmin($user)) {
                $administration[] = self::subLink('Pengaturan', route('admin.settings.index', ['tab' => 'general']));
                $administration[] = self::subLink('Backup Database', route('admin.backup.index'));

                if (config('mcu.menu.master_data_enabled', false)) {
                    $items[] = self::group('bx-book-content', 'Referensi Medis', [
                        self::subLink('Diagnosis', route('admin.diagnoses.index')),
                        self::subLink('Dokter Spesialis', route('admin.specialist-doctors.index')),
                    ]);
                }
            }

            $items[] = self::group('bx-cog', 'Sistem', $administration);
        } elseif (UserRole::isPimpinan($user)) {
            $items[] = self::link(
                'bx-bell',
                'Notifikasi',
                route('admin.notifications.index'),
                NotificationBadgeCounts::unreadFor()
            );
        }

        if (UserRole::hasStaffAccess($user)) {
            $items[] = self::link('bx-user-circle', 'Portal Peserta', route('client.dashboard'));
        }

        return $items;
    }

    private static function participantNavItems(): array
    {
        return [
            self::link('bx-home-circle', 'Dashboard', route('client.dashboard')),
            self::link('bx-calendar', 'Jadwal MCU', route('client.schedules')),
            self::link('bx-file', 'Hasil MCU', route('client.results')),
            self::link('bx-user', 'Profile Saya', route('client.profile')),
            self::link('bx-bell', 'Notifikasi', route('client.notifications.index'), NotificationBadgeCounts::unreadFor()),
        ];
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

    /** @var list<string> */
    private const SETTINGS_DEDICATED_TABS = [
        'schedule_quota',
    ];

    public static function isActive(string $path): bool
    {
        $parsed = parse_url($path);
        $menuPath = ltrim((string) ($parsed['path'] ?? $path), '/');
        $current = request()->path();

        if ($current !== $menuPath && ! str_starts_with($current, $menuPath.'/')) {
            return false;
        }

        if (! empty($parsed['query'])) {
            parse_str((string) $parsed['query'], $menuQuery);

            foreach ($menuQuery as $key => $value) {
                $currentValue = request()->query($key);

                if ($key === 'tab' && ($currentValue === null || $currentValue === '') && $value === 'general') {
                    continue;
                }

                if ((string) $currentValue !== (string) $value) {
                    return false;
                }
            }

            return true;
        }

        if ($menuPath === 'admin/settings') {
            $tab = (string) (request()->query('tab') ?: 'general');

            return ! in_array($tab, self::SETTINGS_DEDICATED_TABS, true);
        }

        return true;
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
