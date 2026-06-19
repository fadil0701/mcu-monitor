<?php

namespace App\Support;

use App\Models\Setting;

final class WhatsAppSendSettings
{
    public static function buttonsEnabled(): bool
    {
        return (bool) Setting::getValue('whatsapp_send_enabled', false);
    }
}
