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
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $token = Str::random(32);

        Session::put(self::SESSION_KEY, [
            'token' => $token,
            'answer' => (string) ($a + $b),
            'expires_at' => now()->addMinutes((int) config('mcu.login_captcha.ttl_minutes', 10))->timestamp,
        ]);

        return [
            'captcha_token' => $token,
            'captcha_question' => "{$a} + {$b} = ?",
        ];
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
