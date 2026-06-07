<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Diagnosis extends Model
{
	use HasFactory;

	protected $fillable = [
		'code', 'name', 'description', 'is_active',
	];

	protected $casts = [
		'is_active' => 'boolean',
	];

	/**
	 * Boot the model and clear cache on changes (optimized for bulk operations)
	 */
	protected static function booted(): void
	{
		static::saved(function ($model) {
			// Only clear cache if not in bulk operation
			if (!$model->wasRecentlyCreated && !static::isBulkOperation()) {
				Cache::forget('diagnosis_list');
				Cache::forget('active_diagnoses');
			}
		});

		static::deleted(function () {
			// Only clear cache if not in bulk operation
			if (!static::isBulkOperation()) {
				Cache::forget('diagnosis_list');
				Cache::forget('active_diagnoses');
			}
		});
	}

	/**
	 * Check if we're in a bulk operation to avoid excessive cache clearing
	 */
	private static function isBulkOperation(): bool
	{
		// Check if we're in an import process by looking at the stack trace
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
		foreach ($trace as $frame) {
			if (isset($frame['class']) && strpos($frame['class'], 'DiagnosesImport') !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get cached active diagnoses for dropdowns
	 */
	public static function getActiveDiagnoses(): array
	{
		return Cache::remember('active_diagnoses', 3600, function () {
			return static::where('is_active', true)
				->orderBy('name')
				->get()
				->mapWithKeys(function ($diagnosis) {
					$label = $diagnosis->code ? ($diagnosis->code . ' - ' . $diagnosis->name) : $diagnosis->name;
					return [$diagnosis->name => $label];
				})
				->toArray();
		});
	}

	/**
	 * Get cached diagnosis list for search
	 */
	public static function getDiagnosisList(): \Illuminate\Database\Eloquent\Collection
	{
		return Cache::remember('diagnosis_list', 3600, function () {
			return static::where('is_active', true)
				->orderBy('name')
				->get();
		});
	}

	/**
	 * Search diagnoses with caching
	 */
	public static function searchDiagnoses(string $search, int $limit = 50): array
	{
		$cacheKey = 'diagnosis_search_' . md5($search . '_' . $limit);
		
		return Cache::remember($cacheKey, 1800, function () use ($search, $limit) {
			return static::where('is_active', true)
				->where(function ($query) use ($search) {
					$query->where('name', 'like', "%{$search}%")
						->orWhere('code', 'like', "%{$search}%");
				})
				->orderBy('name')
				->limit($limit)
				->get()
				->mapWithKeys(function ($diagnosis) {
					$label = $diagnosis->code ? ($diagnosis->code . ' - ' . $diagnosis->name) : $diagnosis->name;
					return [$diagnosis->name => $label];
				})
				->toArray();
		});
	}

	/**
	 * Scope for active diagnoses
	 */
	public function scopeActive($query)
	{
		return $query->where('is_active', true);
	}

	/**
	 * Scope for searching
	 */
	public function scopeSearch($query, $search)
	{
		return $query->where(function ($q) use ($search) {
			$q->where('name', 'like', "%{$search}%")
				->orWhere('code', 'like', "%{$search}%")
				->orWhere('description', 'like', "%{$search}%");
		});
	}
}

