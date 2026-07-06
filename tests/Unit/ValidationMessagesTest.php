<?php

namespace Tests\Unit;

use App\Support\ValidationMessages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_nik_shows_indonesian_message(): void
    {
        $messages = ValidationMessages::participantForm();

        $validator = Validator::make(
            ['nik_ktp' => '123'],
            ['nik_ktp' => 'required|digits:16|unique:participants,nik_ktp'],
            $messages,
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'NIK KTP harus berisi 16 digit angka.',
            $validator->errors()->first('nik_ktp')
        );
    }

    public function test_duplicate_email_shows_indonesian_message(): void
    {
        $messages = ValidationMessages::identity();

        $validator = Validator::make(
            ['email' => 'bukan-email'],
            ['email' => 'required|email|unique:users,email'],
            $messages,
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Format alamat email tidak valid.',
            $validator->errors()->first('email')
        );
    }
}
