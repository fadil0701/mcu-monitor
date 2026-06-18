<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MetaWhatsAppTemplateBody implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $body = (string) $value;

        if (preg_match('/\{[a-z_][a-z0-9_]*\}/i', $body)) {
            $fail('Variabel harus memakai format Meta {{1}}, {{2}}, bukan {nama_variabel}. Meta akan menolak template dengan format salah.');

            return;
        }

        if (preg_match_all('/\{\{([^}]+)\}\}/', $body, $matches)) {
            foreach ($matches[1] as $placeholder) {
                if (! preg_match('/^\d+$/', $placeholder)) {
                    $fail('Format variabel tidak valid: {{'.$placeholder.'}}. Gunakan {{1}}, {{2}}, dan seterusnya (angka saja).');

                    return;
                }
            }

            $numbers = array_map('intval', $matches[1]);
            $unique = array_values(array_unique($numbers));
            sort($unique);

            if ($unique === [] || $unique[0] !== 1) {
                $fail('Nomor variabel harus dimulai dari {{1}}.');

                return;
            }

            $expected = range(1, max($unique));
            if ($unique !== $expected) {
                $fail('Nomor variabel harus berurutan tanpa loncat ({{1}}, {{2}}, {{3}}, ...).');
            }
        }
    }
}
