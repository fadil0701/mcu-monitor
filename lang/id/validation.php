<?php

return [
    'required' => ':attribute wajib diisi.',
    'email' => 'Format :attribute tidak valid.',
    'unique' => ':attribute sudah terdaftar. Periksa kembali input Anda.',
    'digits' => ':attribute harus berisi :digits digit angka.',
    'size' => [
        'string' => ':attribute harus berisi :size karakter.',
    ],
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'in' => ':attribute tidak valid.',
    'before_or_equal' => ':attribute tidak boleh di masa depan.',
    'max' => [
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'password' => [
        'mixed' => 'Kata sandi harus mengandung huruf besar dan huruf kecil.',
        'numbers' => 'Kata sandi harus mengandung angka.',
        'symbols' => 'Kata sandi harus mengandung simbol.',
        'min' => 'Kata sandi minimal :min karakter.',
    ],
];
