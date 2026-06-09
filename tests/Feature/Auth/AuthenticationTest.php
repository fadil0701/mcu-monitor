<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\MathCaptcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('Masukkan hasil penjumlahan di atas', false)
            ->assertSee('data-captcha-image', false)
            ->assertSee('data:image/png;base64,', false);
    }

    public function test_login_captcha_refresh_returns_json(): void
    {
        $this->getJson('/login/captcha/refresh')
            ->assertOk()
            ->assertJsonStructure(['captcha_token', 'captcha_image_url']);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            ...$this->validCaptchaPayload(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            ...$this->validCaptchaPayload(),
        ]);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_captcha(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $captcha = MathCaptcha::issue();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'captcha_token' => $captcha['captcha_token'],
            'captcha_answer' => '99',
        ])->assertSessionHasErrors('captcha_answer');

        $this->assertGuest();
    }

    /**
     * @return array{captcha_token: string, captcha_answer: string}
     */
    private function validCaptchaPayload(): array
    {
        $captcha = MathCaptcha::issue();
        $stored = session(MathCaptcha::SESSION_KEY);

        return [
            'captcha_token' => $captcha['captcha_token'],
            'captcha_answer' => (string) ($stored['answer'] ?? ''),
        ];
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
