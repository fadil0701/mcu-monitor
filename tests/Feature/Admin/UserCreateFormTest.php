<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserCreateFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'user'] as $role) {
            Role::findOrCreate($role);
        }
    }

    public function test_create_user_form_renders_password_policy_ui(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('id="password-policy-checklist"', false)
            ->assertSee('password-toggle-btn', false)
            ->assertSee('Minimal 8 karakter', false);
    }
}
