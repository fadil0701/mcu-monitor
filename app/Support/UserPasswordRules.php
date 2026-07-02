<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class UserPasswordRules
{
    public static function defaults(): Password
    {
        return Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols();
    }
}
