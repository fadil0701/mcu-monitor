<?php

namespace App\Support\Database;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class MysqlToPostgresMigrator
{
    private string $source;

    private string $target;

    public function __construct(
        ?string $source = null,
        ?string $target = null,
    ) {
        $this->source = $source ?? (string) config('database_migration.source_connection', 'mysql');
        $this->target = $target ?? (string) config('database_migration.target_connection', 'pgsql');
    }

    public function sourceConnection(): Connection
    {
        return DB::connection($this->source);
    }

    public function targetConnection(): Connection
    {
        return DB::connection($this->target);
    }

    /**
     * @return array{tables: list<string>, rows: array<string, int>}
     */
    public function sync(bool $fresh = false, ?int $chunkSize = null): array
    {
        $this->assertConnections();

        $tables = $this->tablesToSync();
        $chunk = $chunkSize ?? (int) config('database_migration.chunk_size', 500);
        $counts = [];

        if ($fresh) {
            $this->truncateTarget($tables);
        }

        foreach ($tables as $table) {
            $counts[$table] = $this->copyTable($table, $chunk);
        }

        $this->resetSequences();

        return [
            'tables' => $tables,
            'rows' => $counts,
        ];
    }

    /**
     * @return array<string, array{mysql: int, pgsql: int, match: bool}>
     */
    public function verifyCounts(): array
    {
        $this->assertConnections();

        $report = [];

        foreach ($this->tablesToSync() as $table) {
            $mysql = (int) $this->sourceConnection()->table($table)->count();
            $pgsql = (int) $this->targetConnection()->table($table)->count();

            $report[$table] = [
                'mysql' => $mysql,
                'pgsql' => $pgsql,
                'match' => $mysql === $pgsql,
            ];
        }

        return $report;
    }

    /**
     * @return list<string>
     */
    public function tablesToSync(): array
    {
        $skip = array_flip((array) config('database_migration.skip_tables', ['migrations']));

        return array_values(array_filter(
            (array) config('database_migration.tables', []),
            fn (string $table) => ! isset($skip[$table]),
        ));
    }

    private function assertConnections(): void
    {
        foreach ([$this->source, $this->target] as $name) {
            try {
                DB::connection($name)->getPdo();
            } catch (\Throwable $e) {
                throw new RuntimeException("Koneksi database \"{$name}\" gagal: ".$e->getMessage(), 0, $e);
            }
        }

        if ($this->sourceConnection()->getDriverName() !== 'mysql') {
            throw new RuntimeException("Koneksi sumber \"{$this->source}\" harus MySQL.");
        }

        if ($this->targetConnection()->getDriverName() !== 'pgsql') {
            throw new RuntimeException("Koneksi target \"{$this->target}\" harus PostgreSQL.");
        }
    }

    /**
     * @param  list<string>  $tables
     */
    private function truncateTarget(array $tables): void
    {
        $existing = array_values(array_filter(
            $tables,
            fn (string $table) => Schema::connection($this->target)->hasTable($table),
        ));

        if ($existing === []) {
            return;
        }

        $quoted = implode(', ', array_map(
            fn (string $table) => $this->targetConnection()->getQueryGrammar()->wrapTable($table),
            $existing,
        ));

        $this->targetConnection()->statement("TRUNCATE TABLE {$quoted} RESTART IDENTITY CASCADE");
    }

    private function copyTable(string $table, int $chunkSize): int
    {
        if (! Schema::connection($this->source)->hasTable($table)) {
            return 0;
        }

        if (! Schema::connection($this->target)->hasTable($table)) {
            throw new RuntimeException("Tabel \"{$table}\" belum ada di PostgreSQL. Jalankan: php artisan migrate --database={$this->target}");
        }

        $total = 0;
        $orderColumn = $this->resolveOrderColumn($table);

        $this->sourceConnection()
            ->table($table)
            ->orderBy($orderColumn)
            ->chunk($chunkSize, function (Collection $rows) use ($table, &$total): void {
                $payload = $rows
                    ->map(fn ($row) => $this->normalizeRow($table, (array) $row))
                    ->values()
                    ->all();

                if ($payload === []) {
                    return;
                }

                $this->targetConnection()->table($table)->insert($payload);
                $total += count($payload);
            });

        return $total;
    }

    private function resolveOrderColumn(string $table): string
    {
        if (Schema::connection($this->source)->hasColumn($table, 'id')) {
            return 'id';
        }

        if ($table === 'password_reset_tokens') {
            return 'email';
        }

        if ($table === 'cache' || $table === 'cache_locks') {
            return 'key';
        }

        if ($table === 'sessions') {
            return 'id';
        }

        if ($table === 'model_has_permissions') {
            return 'permission_id';
        }

        if ($table === 'model_has_roles') {
            return 'role_id';
        }

        if ($table === 'role_has_permissions') {
            return 'permission_id';
        }

        return 'id';
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(string $table, array $row): array
    {
        foreach ($row as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                $row[$key] = $value;

                continue;
            }

            if (is_string($value) && $this->isBooleanColumn($table, $key)) {
                $row[$key] = match (strtolower($value)) {
                    '1', 'true', 'yes', 'on' => true,
                    '0', 'false', 'no', 'off', '' => false,
                    default => (bool) $value,
                };
            }
        }

        return $row;
    }

    private function isBooleanColumn(string $table, string $column): bool
    {
        try {
            $type = Schema::connection($this->source)->getColumnType($table, $column);

            return $type === 'boolean' || $type === 'tinyint';
        } catch (\Throwable) {
            return in_array($column, ['is_active', 'is_published', 'is_disabled'], true);
        }
    }

    /**
     * Sinkronkan sequence PostgreSQL setelah salin baris dengan id eksplisit dari MySQL.
     */
    public function resetSequences(?array $tables = null): void
    {
        $configured = (array) config('database_migration.serial_tables', []);
        $candidates = array_values(array_unique(array_merge(
            $tables ?? $this->tablesToSync(),
            $configured,
        )));

        foreach ($candidates as $table) {
            if (! Schema::connection($this->target)->hasTable($table)) {
                continue;
            }

            if (! Schema::connection($this->target)->hasColumn($table, 'id')) {
                continue;
            }

            if (! $this->hasIntegerSerialId($table)) {
                continue;
            }

            $wrapped = $this->targetConnection()->getQueryGrammar()->wrapTable($table);
            $sequence = $this->targetConnection()->selectOne(
                'SELECT pg_get_serial_sequence(?, ?) AS seq',
                [$table, 'id'],
            );

            if ($sequence === null || empty($sequence->seq)) {
                continue;
            }

            $this->targetConnection()->statement(
                "SELECT setval(
                    ?::regclass,
                    COALESCE((SELECT MAX(id) FROM {$wrapped}), 0) + 1,
                    false
                )",
                [$sequence->seq],
            );
        }
    }

    private function hasIntegerSerialId(string $table): bool
    {
        $row = $this->targetConnection()->selectOne(
            'SELECT data_type FROM information_schema.columns
             WHERE table_schema = current_schema()
               AND table_name = ?
               AND column_name = ?',
            [$table, 'id'],
        );

        if ($row === null) {
            return false;
        }

        return in_array((string) $row->data_type, ['bigint', 'integer', 'smallint'], true);
    }
}
