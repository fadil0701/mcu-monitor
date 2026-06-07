<?php

namespace App\Imports;

use App\Models\Diagnosis;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;

class DiagnosesImport implements 
    ToCollection, 
    WithHeadingRow, 
    WithBatchInserts, 
    WithChunkReading, 
    WithEvents,
    SkipsEmptyRows,
    SkipsOnError
{
    private $importedCount = 0;
    private $updatedCount = 0;
    private $skippedCount = 0;
    private $errors = [];

    public function collection(Collection $rows)
    {
        // Process in batches for better memory management
        $batchData = [];
        $batchSize = 100;

        foreach ($rows as $index => $row) {
            try {
                // Normalize and validate data
                $data = $this->normalizeRow($row);
                
                if (!$data) {
                    $this->skippedCount++;
                    continue;
                }

                $batchData[] = $data;

                // Process batch when it reaches the batch size
                if (count($batchData) >= $batchSize) {
                    $this->processBatch($batchData);
                    $batchData = [];
                }

            } catch (Throwable $e) {
                $this->errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                $this->skippedCount++;
                continue;
            }
        }

        // Process remaining data
        if (!empty($batchData)) {
            $this->processBatch($batchData);
        }
    }

    private function normalizeRow($row): ?array
    {
        // Normalize column names (case insensitive)
        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(trim($key));
            $normalizedRow[$normalizedKey] = trim($value);
        }

        // Extract data with flexible column mapping
        $name = $normalizedRow['name'] ?? $normalizedRow['nama'] ?? $normalizedRow['diagnosis'] ?? null;
        $code = $normalizedRow['code'] ?? $normalizedRow['kode'] ?? null;
        $description = $normalizedRow['description'] ?? $normalizedRow['deskripsi'] ?? null;
        $isActive = $normalizedRow['is_active'] ?? $normalizedRow['aktif'] ?? 'true';

        // Validate required fields
        if (empty($name)) {
            return null;
        }

        // Normalize is_active value
        $isActiveValue = in_array(strtolower($isActive), ['1', 'true', 'yes', 'ya', 'aktif'], true);

        return [
            'name' => $name,
            'code' => $code ?: null,
            'description' => $description ?: null,
            'is_active' => $isActiveValue,
        ];
    }

    private function processBatch(array $batchData): void
    {
        try {
            // Get all existing diagnoses in one query
            $names = array_column($batchData, 'name');
            $existingDiagnoses = Diagnosis::whereIn('name', $names)
                ->get()
                ->keyBy('name');

            $toInsert = [];
            $toUpdate = [];

            foreach ($batchData as $data) {
                if ($existingDiagnoses->has($data['name'])) {
                    // Prepare for update
                    $existing = $existingDiagnoses->get($data['name']);
                    $toUpdate[] = [
                        'id' => $existing->id,
                        'code' => $data['code'],
                        'description' => $data['description'],
                        'is_active' => $data['is_active'],
                        'updated_at' => now(),
                    ];
                    $this->updatedCount++;
                } else {
                    // Prepare for insert
                    $toInsert[] = array_merge($data, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->importedCount++;
                }
            }

            // Bulk insert new records
            if (!empty($toInsert)) {
                Diagnosis::insert($toInsert);
            }

            // Bulk update existing records
            foreach ($toUpdate as $updateData) {
                Diagnosis::where('id', $updateData['id'])->update([
                    'code' => $updateData['code'],
                    'description' => $updateData['description'],
                    'is_active' => $updateData['is_active'],
                    'updated_at' => $updateData['updated_at'],
                ]);
            }

        } catch (Throwable $e) {
            Log::error('Diagnosis import batch error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function batchSize(): int
    {
        return 500; // Increased batch size for better performance
    }

    public function chunkSize(): int
    {
        return 2000; // Process 2000 rows at a time for better efficiency
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                Log::info('Starting diagnosis import process');
                $this->importedCount = 0;
                $this->updatedCount = 0;
                $this->skippedCount = 0;
                $this->errors = [];
            },

            AfterImport::class => function (AfterImport $event) {
                Log::info('Diagnosis import completed', [
                    'imported' => $this->importedCount,
                    'updated' => $this->updatedCount,
                    'skipped' => $this->skippedCount,
                    'errors' => count($this->errors)
                ]);

                // Clear relevant caches only once at the end
                cache()->forget('diagnosis_list');
                cache()->forget('active_diagnoses');
                
                // Clear search caches
                $cacheKeys = cache()->getRedis()->keys('*diagnosis_search*');
                if (!empty($cacheKeys)) {
                    cache()->getRedis()->del($cacheKeys);
                }
            },

            ImportFailed::class => function (ImportFailed $event) {
                Log::error('Diagnosis import failed: ' . $event->getException()->getMessage());
            },
        ];
    }

    public function onError(Throwable $e)
    {
        Log::error('Diagnosis import row error: ' . $e->getMessage());
        $this->errors[] = $e->getMessage();
    }

    public function getImportStats(): array
    {
        return [
            'imported' => $this->importedCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errors,
        ];
    }

    /**
     * Get console output for progress tracking (required by WithProgressBar interface)
     */
    public function getConsoleOutput(): ConsoleOutput
    {
        return new ConsoleOutput();
    }
}
