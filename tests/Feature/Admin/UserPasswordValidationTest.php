<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserPasswordValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'user'] as $role) {
            Role::findOrCreate($role);
        }
    }

    public function test_admin_cannot_create_user_with_weak_password(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'User Baru',
                'email' => 'baru@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'peserta',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'baru@example.com']);
    }

    public function test_admin_can_create_user_with_strong_password(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'User Baru',
                'email' => 'baru@example.com',
                'password' => 'Abcdef1!',
                'password_confirmation' => 'Abcdef1!',
                'role' => 'peserta',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'baru@example.com',
            'role' => 'user',
        ]);
    }

    public function test_admin_cannot_update_user_when_password_confirmation_mismatch(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $user->assignRole('user');

        $this->actingAs($admin)
            ->post(route('admin.users.update.post', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'Abcdef1!',
                'password_confirmation' => 'Abcdef1?',
                'role' => 'peserta',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('password');
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $admin->assignRole('super_admin');

        return $admin;
    }
}
