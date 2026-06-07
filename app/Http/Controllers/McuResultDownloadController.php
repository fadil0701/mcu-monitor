<?php

namespace App\Http\Controllers;

use App\Models\McuResult;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class McuResultDownloadController extends Controller
{
	public function downloadAll(McuResult $record)
	{
		$files = $record->file_hasil_files ?? [];
		if (empty($files) && $record->file_hasil) {
			$files = [$record->file_hasil];
		}

		if (empty($files)) {
			return back()->withErrors(['download' => 'Tidak ada file yang dapat diunduh.']);
		}

		$zip = new ZipArchive();
		$zipFileName = 'mcu-result-' . $record->id . '.zip';
		$tmpPath = storage_path('app/tmp');
		if (!is_dir($tmpPath)) {
			mkdir($tmpPath, 0775, true);
		}
		$zipPath = $tmpPath . DIRECTORY_SEPARATOR . $zipFileName;

		if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
			return back()->withErrors(['download' => 'Gagal membuat arsip ZIP.']);
		}

		foreach ($files as $relativePath) {
			$relativePath = ltrim($relativePath, '/');
			$fullPath = Storage::disk('public')->path($relativePath);
			if (!file_exists($fullPath)) {
				continue;
			}
			$zip->addFile($fullPath, basename($fullPath));
		}

		$zip->close();

		return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
	}
}

