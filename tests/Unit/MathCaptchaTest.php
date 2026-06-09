<?php

namespace Tests\Unit;

use App\Support\MathCaptcha;
use Illuminate\Http\Request;
use Tests\TestCase;

class MathCaptchaTest extends TestCase
{
    public function test_issue_and_validate_matching_answer(): void
    {
        $captcha = MathCaptcha::issue();
        $stored = session(MathCaptcha::SESSION_KEY);

        $request = Request::create('/login', 'POST', [
            'captcha_token' => $captcha['captcha_token'],
            'captcha_answer' => (string) ($stored['answer'] ?? ''),
        ]);

        $this->assertTrue(MathCaptcha::validate($request));
    }

    public function test_rejects_wrong_answer(): void
    {
        $captcha = MathCaptcha::issue();

        $request = Request::create('/login', 'POST', [
            'captcha_token' => $captcha['captcha_token'],
            'captcha_answer' => '99',
        ]);

        $this->assertFalse(MathCaptcha::validate($request));
    }

    public function test_render_image_returns_png_bytes(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for captcha images.');
        }

        $captcha = MathCaptcha::issue();
        $png = MathCaptcha::renderImage($captcha['captcha_token']);

        $this->assertIsString($png);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $png ?? '');
    }
}
