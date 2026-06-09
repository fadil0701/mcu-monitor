<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

final class MathCaptcha
{
    public const SESSION_KEY = 'login_math_captcha';

    public static function issue(): array
    {
        $a = random_int(10, 39);
        $b = random_int(1, 9);
        $expression = "{$a} + {$b}";
        $token = Str::random(32);

        Session::put(self::SESSION_KEY, [
            'token' => $token,
            'answer' => (string) ($a + $b),
            'expression' => $expression,
            'expires_at' => now()->addMinutes((int) config('mcu.login_captcha.ttl_minutes', 10))->timestamp,
        ]);

        return [
            'captcha_token' => $token,
            'captcha_question' => "{$expression} = ?",
            'captcha_image_url' => self::imageDataUri($expression),
        ];
    }

    private static function imageDataUri(string $expression): string
    {
        if (! extension_loaded('gd')) {
            return '';
        }

        return 'data:image/png;base64,' . base64_encode(MathCaptchaImageRenderer::render($expression));
    }

    public static function renderImage(string $token): ?string
    {
        $stored = self::storedForToken($token);

        if ($stored === null || ! extension_loaded('gd')) {
            return null;
        }

        return MathCaptchaImageRenderer::render((string) ($stored['expression'] ?? ''));
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function storedForToken(string $token): ?array
    {
        $stored = Session::get(self::SESSION_KEY);

        if (! is_array($stored)) {
            return null;
        }

        if (($stored['expires_at'] ?? 0) < now()->timestamp) {
            Session::forget(self::SESSION_KEY);

            return null;
        }

        if (! hash_equals((string) ($stored['token'] ?? ''), $token)) {
            return null;
        }

        return $stored;
    }

    public static function validate(Request $request): bool
    {
        $token = (string) $request->input('captcha_token', '');
        $answer = trim((string) $request->input('captcha_answer', ''));

        if ($token === '' || $answer === '') {
            return false;
        }

        $stored = Session::get(self::SESSION_KEY);

        if (! is_array($stored)) {
            return false;
        }

        if (($stored['expires_at'] ?? 0) < now()->timestamp) {
            Session::forget(self::SESSION_KEY);

            return false;
        }

        $valid = hash_equals((string) ($stored['token'] ?? ''), $token)
            && hash_equals((string) ($stored['answer'] ?? ''), $answer);

        if ($valid) {
            Session::forget(self::SESSION_KEY);
        }

        return $valid;
    }
}
