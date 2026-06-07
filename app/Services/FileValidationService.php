<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class FileValidationService
{
    // Allowed file types for MCU results
    const ALLOWED_EXTENSIONS = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'bmp' => ['image/bmp'],
        'tiff' => ['image/tiff'],
    ];

    // Maximum file sizes (in bytes)
    const MAX_FILE_SIZES = [
        'pdf' => 10 * 1024 * 1024, // 10MB
        'doc' => 10 * 1024 * 1024, // 10MB
        'docx' => 10 * 1024 * 1024, // 10MB
        'jpg' => 5 * 1024 * 1024, // 5MB
        'jpeg' => 5 * 1024 * 1024, // 5MB
        'png' => 5 * 1024 * 1024, // 5MB
        'gif' => 5 * 1024 * 1024, // 5MB
        'bmp' => 5 * 1024 * 1024, // 5MB
        'tiff' => 5 * 1024 * 1024, // 5MB
    ];

    // Dangerous file extensions that should be blocked
    const DANGEROUS_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'php', 'asp', 'aspx',
        'jsp', 'py', 'rb', 'pl', 'sh', 'ps1', 'psm1', 'psd1', 'ps1xml', 'psc1', 'pssc',
        'msh', 'msh1', 'msh2', 'mshxml', 'msh1xml', 'msh2xml', 'scf', 'lnk', 'inf', 'reg'
    ];

    /**
     * Validate uploaded file
     */
    public static function validate(UploadedFile $file): array
    {
        $errors = [];
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Check if extension is dangerous
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            $errors[] = "File type '{$extension}' is not allowed for security reasons.";
            return $errors;
        }

        // Check if extension is allowed
        if (!array_key_exists($extension, self::ALLOWED_EXTENSIONS)) {
            $errors[] = "File type '{$extension}' is not supported. Allowed types: " . implode(', ', array_keys(self::ALLOWED_EXTENSIONS));
        }

        // Check MIME type
        if (array_key_exists($extension, self::ALLOWED_EXTENSIONS)) {
            if (!in_array($mimeType, self::ALLOWED_EXTENSIONS[$extension])) {
                $errors[] = "File MIME type '{$mimeType}' does not match extension '{$extension}'.";
            }
        }

        // Check file size
        if (array_key_exists($extension, self::MAX_FILE_SIZES)) {
            if ($size > self::MAX_FILE_SIZES[$extension]) {
                $maxSizeMB = self::MAX_FILE_SIZES[$extension] / (1024 * 1024);
                $errors[] = "File size exceeds maximum allowed size of {$maxSizeMB}MB for {$extension} files.";
            }
        }

        // Additional security checks
        $errors = array_merge($errors, self::performSecurityChecks($file));

        return $errors;
    }

    /**
     * Perform additional security checks
     */
    private static function performSecurityChecks(UploadedFile $file): array
    {
        $errors = [];

        // Check file content for suspicious patterns
        $content = file_get_contents($file->getPathname());
        
        // Check for executable signatures
        $executableSignatures = [
            "\x4D\x5A", // PE executable
            "\x7F\x45\x4C\x46", // ELF executable
            "\xCA\xFE\xBA\xBE", // Java class file
        ];

        foreach ($executableSignatures as $signature) {
            if (strpos($content, $signature) === 0) {
                $errors[] = "File appears to be an executable file, which is not allowed.";
                break;
            }
        }

        // Check for script tags in files that shouldn't have them
        if (preg_match('/<script[^>]*>.*?<\/script>/is', $content)) {
            $errors[] = "File contains script tags, which may be a security risk.";
        }

        // Check for PHP tags
        if (preg_match('/<\?php|<\?=/i', $content)) {
            $errors[] = "File contains PHP code, which is not allowed.";
        }

        return $errors;
    }

    /**
     * Get safe filename
     */
    public static function getSafeFilename(string $originalName): string
    {
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        
        // Remove multiple dots
        $filename = preg_replace('/\.{2,}/', '.', $filename);
        
        // Ensure filename doesn't start with dot
        $filename = ltrim($filename, '.');
        
        // Limit length
        if (strlen($filename) > 100) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 100 - strlen($extension) - 1) . '.' . $extension;
        }

        return $filename;
    }

    /**
     * Get allowed file types for display
     */
    public static function getAllowedFileTypes(): array
    {
        return array_keys(self::ALLOWED_EXTENSIONS);
    }

    /**
     * Get max file size for specific type
     */
    public static function getMaxFileSize(string $extension): int
    {
        return self::MAX_FILE_SIZES[$extension] ?? 5 * 1024 * 1024; // Default 5MB
    }
}

