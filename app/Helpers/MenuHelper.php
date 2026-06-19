<?php

namespace App\Helpers;

use App\Support\NotificationBadgeCounts;
use Illuminate\Support\Facades\Auth;

class MenuHelper
{
    public static function getMainNavItems(): array
    {
        $items = [
            [
                'icon' => 'bx-home-circle',
                'name' => 'Dashboard',
                'path' => Auth::check() && Auth::user()->isAdmin()
                    ? route('dashboard')
                    : route('client.dashboard'),
            ],
        ];

        if (Auth::check() && Auth::user()->isAdmin()) {
            $items[] = [
                'name' => 'Kelola Data',
                'icon' => 'bx-data',
                'subItems' => [
                    ['name' => 'Integrasi CKG', 'path' => route('admin.ckg-bridge.index')],
                    ['name' => 'Data Peserta', 'path' => route('admin.participants.index')],
                    ['name' => 'Jadwal MCU', 'path' => route('admin.schedules.index')],
                    ['name' => 'Hasil MCU', 'path' => route('admin.mcu-results.index')],
                    ['name' => 'Pengguna', 'path' => route('admin.users.index')],
                ],
            ];

            if (Auth::user()->hasRole('super_admin')) {
                if (config('mcu.menu.master_data_enabled', false)) {
                    $items[] = [
                        'name' => 'Master Data',
                        'icon' => 'bx-book-content',
                        'subItems' => [
                            ['name' => 'Diagnosis', 'path' => route('admin.diagnoses.index')],
                            ['name' => 'Dokter Spesialis', 'path' => route('admin.specialist-doctors.index')],
                        ],
                    ];
                }
                $items[] = [
                    'icon' => 'bx-calendar-edit',
                    'name' => 'Permintaan Reschedule',
                    'path' => route('admin.reschedule-center.index'),
                    'badge' => NotificationBadgeCounts::pendingReschedules(),
                ];
                $items[] = [
                    'icon' => 'bx-message-dots',
                    'name' => 'WhatsApp Template',
                    'path' => route('admin.whatsapp-templates.index'),
                ];
                $items[] = [
                    'name' => 'Template Email & PDF',
                    'icon' => 'bx-envelope',
                    'subItems' => [
                        ['name' => 'Email Templates', 'path' => route('admin.email-templates.index')],
                        ['name' => 'PDF Templates', 'path' => route('admin.pdf-templates.index')],
                    ],
                ];
                $items[] = [
                    'icon' => 'bx-data',
                    'name' => 'Backup Database',
                    'path' => route('admin.backup.index'),
                ];
            }

            $items[] = [
                'icon' => 'bx-bell',
                'name' => 'Notifikasi',
                'path' => route('admin.notifications.index'),
                'badge' => NotificationBadgeCounts::unreadFor(),
            ];

            $items[] = [
                'icon' => 'bx-bar-chart-alt-2',
                'name' => 'Laporan',
                'path' => route('admin.reports.index'),
            ];
            $items[] = [
                'icon' => 'bx-cog',
                'name' => 'Pengaturan',
                'path' => route('admin.settings.index'),
            ];
        } else {
            $items[] = ['icon' => 'bx-user', 'name' => 'Profile Saya', 'path' => route('client.profile')];
            $items[] = [
                'icon' => 'bx-bell',
                'name' => 'Notifikasi',
                'path' => route('client.notifications.index'),
                'badge' => NotificationBadgeCounts::unreadFor(),
            ];
            $items[] = ['icon' => 'bx-calendar', 'name' => 'Jadwal MCU', 'path' => route('client.schedules')];
            $items[] = ['icon' => 'bx-file', 'name' => 'Hasil MCU', 'path' => route('client.results')];
        }

        return $items;
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
