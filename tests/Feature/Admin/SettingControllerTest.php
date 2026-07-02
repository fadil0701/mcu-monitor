<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_smtp_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::setValue('smtp_host', 'smtp.gmail.com', 'string', 'smtp');
        Setting::setValue('smtp_port', '465', 'string', 'smtp');
        Setting::setValue('smtp_encryption', 'ssl', 'string', 'smtp');
        Setting::setValue('smtp_from_address', 'mcu@example.com', 'string', 'smtp');
        Setting::setValue('smtp_from_name', 'MCU', 'string', 'smtp');

        $this->actingAs($admin)
            ->put(route('admin.settings.update-section', 'email'), [
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => '465',
                'smtp_encryption' => 'ssl',
                'smtp_username' => 'mcu@example.com',
                'smtp_password' => 'app-password-16chars',
                'smtp_from_address' => 'mcu@example.com',
                'smtp_from_name' => 'MCU',
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'email']));

        $this->assertSame('app-password-16chars', Setting::getValue('smtp_password'));
    }

    public function test_admin_can_update_smtp_without_changing_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::setValue('smtp_host', 'smtp.gmail.com', 'string', 'smtp');
        Setting::setValue('smtp_port', '465', 'string', 'smtp');
        Setting::setValue('smtp_encryption', 'ssl', 'string', 'smtp');
        Setting::setValue('smtp_password', 'existing-secret', 'string', 'smtp');
        Setting::setValue('smtp_from_address', 'mcu@example.com', 'string', 'smtp');
        Setting::setValue('smtp_from_name', 'MCU', 'string', 'smtp');

        $this->actingAs($admin)
            ->put(route('admin.settings.update-section', 'email'), [
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => '587',
                'smtp_encryption' => 'tls',
                'smtp_username' => 'mcu@example.com',
                'smtp_password' => '',
                'smtp_from_address' => 'mcu@example.com',
                'smtp_from_name' => 'MCU Baru',
            ])
            ->assertRedirect();

        $this->assertSame('existing-secret', Setting::getValue('smtp_password'));
        $this->assertSame('587', Setting::getValue('smtp_port'));
    }

    public function test_admin_can_save_schedule_quota_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.settings.update-section', 'schedule_quota'), [
                'mcu_daily_quota' => '75',
                'mcu_default_location' => 'Klinik Utama Balaikota',
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'schedule_quota']));

        $this->assertSame('75', Setting::getValue('mcu_daily_quota'));
    }
}
