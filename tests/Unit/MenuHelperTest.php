<?php

namespace Tests\Unit;

use App\Helpers\MenuHelper;
use Illuminate\Http\Request;
use Tests\TestCase;

class MenuHelperTest extends TestCase
{
    public function test_settings_quota_tab_only_highlights_quota_menu(): void
    {
        $this->app->instance('request', Request::create('/admin/settings', 'GET', ['tab' => 'schedule_quota']));

        $this->assertTrue(MenuHelper::isActive(route('admin.settings.index', ['tab' => 'schedule_quota'])));
        $this->assertFalse(MenuHelper::isActive(route('admin.settings.index', ['tab' => 'general'])));
        $this->assertFalse(MenuHelper::isActive(route('admin.mcu-work-calendar.index')));
    }

    public function test_work_calendar_highlights_kalender_libur_menu(): void
    {
        $this->app->instance('request', Request::create('/admin/mcu-work-calendar', 'GET'));

        $this->assertTrue(MenuHelper::isActive(route('admin.mcu-work-calendar.index')));
        $this->assertFalse(MenuHelper::isActive(route('admin.settings.index', ['tab' => 'schedule_quota'])));
    }

    public function test_settings_general_tab_highlights_pengaturan_menu(): void
    {
        $this->app->instance('request', Request::create('/admin/settings', 'GET', ['tab' => 'general']));

        $this->assertTrue(MenuHelper::isActive(route('admin.settings.index', ['tab' => 'general'])));
        $this->assertFalse(MenuHelper::isActive(route('admin.settings.index', ['tab' => 'schedule_quota'])));
    }

    public function test_settings_without_tab_defaults_to_pengaturan_menu(): void
    {
        $this->app->instance('request', Request::create('/admin/settings', 'GET'));

        $this->assertTrue(MenuHelper::isActive(route('admin.settings.index', ['tab' => 'general'])));
        $this->assertFalse(MenuHelper::isActive(route('admin.settings.index', ['tab' => 'schedule_quota'])));
    }
}
