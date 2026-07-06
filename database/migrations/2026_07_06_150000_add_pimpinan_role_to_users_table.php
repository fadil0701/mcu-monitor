<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin', 'admin', 'pimpinan', 'user') NOT NULL DEFAULT 'user'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['super_admin'::text, 'admin'::text, 'pimpinan'::text, 'user'::text]))");

            return;
        }

        if ($driver === 'sqlite') {
            // SQLite: enum is enforced via CHECK on fresh installs; alter via table rebuild if needed.
            try {
                DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_check');
            } catch (\Throwable) {
                // Constraint name may differ; tests use updated base migration.
            }
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('super_admin', 'admin', 'user') NOT NULL DEFAULT 'user'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['super_admin'::text, 'admin'::text, 'user'::text]))");
        }
    }
};
