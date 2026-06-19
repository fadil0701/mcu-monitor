<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Support\WhatsAppSendSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppSendSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_buttons_disabled_by_default(): void
    {
        $this->assertFalse(WhatsAppSendSettings::buttonsEnabled());
    }

    public function test_buttons_enabled_when_setting_on(): void
    {
        Setting::setValue('whatsapp_send_enabled', '1', 'boolean', 'whatsapp', 'WA send toggle');

        $this->assertTrue(WhatsAppSendSettings::buttonsEnabled());
    }
}
