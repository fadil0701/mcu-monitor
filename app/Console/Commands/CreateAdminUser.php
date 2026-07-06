<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ValidationMessages;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'user:create-admin
                            {--name= : Nama pengguna}
                            {--email= : Alamat email}
                            {--password= : Password}
                            {--role=super_admin : Role (super_admin, admin, atau pimpinan)}
                            {--from-env : Ambil kredensial super admin dari .env}';

    protected $description = 'Buat akun admin atau super admin (interaktif, opsi CLI, atau dari .env)';

    public function handle(): int
    {
        if ($this->option('from-env')) {
            return $this->createFromEnv();
        }

        $name = $this->option('name') ?: $this->ask('Nama');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password');
        $role = $this->option('role') ?: $this->choice('Role', ['super_admin', 'admin', 'pimpinan'], 0);

        return $this->createUser($name, $email, $password, $role);
    }

    private function createFromEnv(): int
    {
        $name = config('admin.super_admin.name');
        $email = config('admin.super_admin.email');
        $password = config('admin.super_admin.password');

        if (blank($name) || blank($email) || blank($password)) {
            $this->error('Isi SUPER_ADMIN_NAME, SUPER_ADMIN_EMAIL, dan SUPER_ADMIN_PASSWORD di file .env');

            return self::FAILURE;
        }

        if ($user = User::where('email', $email)->first()) {
            $this->ensureRolesExist();

            if (! $user->hasRole('super_admin')) {
                $user->assignRole('super_admin');
                $this->info("Role super_admin ditambahkan ke: {$email}");
            } else {
                $this->info("Super admin sudah ada: {$email}");
            }

            return self::SUCCESS;
        }

        return $this->createUser($name, $email, $password, 'super_admin');
    }

    private function ensureRolesExist(): void
    {
        $this->call(RoleSeeder::class);
    }

    private function createUser(string $name, string $email, string $password, string $role): int
    {
        $validator = Validator::make(
            compact('name', 'email', 'password', 'role'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
                'role' => ['required', 'in:super_admin,admin,pimpinan'],
            ],
            ValidationMessages::adminUser(),
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->ensureRolesExist();

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'is_active' => true,
        ]);

        $user->assignRole($role);

        $this->info("Akun {$role} berhasil dibuat: {$email}");

        return self::SUCCESS;
    }
}
