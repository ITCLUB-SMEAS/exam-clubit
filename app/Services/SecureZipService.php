<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

/**
 * Secure ZIP extraction service to prevent ZIP bomb attacks
 * and other malicious archive exploits.
 */
class SecureZipService
{
    /**
     * Maximum number of files allowed in a ZIP archive
     */
    protected int $maxFiles = 1000;

    /**
     * Maximum total extracted size in bytes (500MB)
     */
    protected int $maxExtractedSize = 524288000;

    /**
     * Maximum compression ratio (extracted/compressed)
     * Higher ratio indicates potential ZIP bomb
     */
    protected float $maxCompressionRatio = 100.0;

    /**
     * Maximum nesting depth for directories
     */
    protected int $maxNestingDepth = 10;

    /**
     * Dangerous file extensions that should never be extracted
     */
    protected array $dangerousExtensions = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar',
        'exe', 'sh', 'bash', 'bat', 'cmd', 'com', 'scr', 'msi',
        'js', 'vbs', 'wsf', 'wsh', 'jar',
        'htaccess', 'htpasswd',
    ];

    /**
     * Validate and extract a ZIP file safely
     *
     * @param  string  $zipPath  Path to the ZIP file
     * @param  string  $extractPath  Directory to extract to
     * @param  array  $allowedExtensions  Optional whitelist of allowed extensions
     * @return array Result with 'success', 'files', 'errors' keys
     *
     * @throws RuntimeException If critical security issue detected
     */
    public function extractSafely(string $zipPath, string $extractPath, array $allowedExtensions = []): array
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Gagal membuka file ZIP.');
        }

        // Pre-extraction validation
        $validation = $this->validateZipContents($zip, $zipPath);

        if (! $validation['safe']) {
            $zip->close();
            throw new RuntimeException($validation['reason']);
        }

        // Create extraction directory if not exists
        if (! is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        $extractedFiles = [];
        $errors = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $filename = $stat['name'];

            // Skip validation failures but log them
            $fileValidation = $this->validateFilename($filename, $allowedExtensions);
            if (! $fileValidation['valid']) {
                $errors[] = "{$filename}: {$fileValidation['reason']}";

                continue;
            }

            // Skip directories
            if (substr($filename, -1) === '/') {
                continue;
            }

            // Extract single file
            $targetPath = $extractPath.'/'.$this->sanitizeFilename($filename);
            $targetDir = dirname($targetPath);

            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            // Extract and write file
            $content = $zip->getFromIndex($i);
            if ($content !== false) {
                file_put_contents($targetPath, $content);
                $extractedFiles[] = $targetPath;
            } else {
                $errors[] = "{$filename}: Gagal mengekstrak";
            }
        }

        $zip->close();

        return [
            'success' => true,
            'files' => $extractedFiles,
            'errors' => $errors,
            'total_files' => count($extractedFiles),
            'skipped' => count($errors),
        ];
    }

    /**
     * Validate ZIP contents before extraction
     */
    protected function validateZipContents(ZipArchive $zip, string $zipPath): array
    {
        $numFiles = $zip->numFiles;
        $compressedSize = filesize($zipPath);
        $totalUncompressedSize = 0;

        // Check file count
        if ($numFiles > $this->maxFiles) {
            return [
                'safe' => false,
                'reason' => "ZIP berisi terlalu banyak file ({$numFiles}). Maksimal: {$this->maxFiles}",
            ];
        }

        // Calculate total uncompressed size
        for ($i = 0; $i < $numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $totalUncompressedSize += $stat['size'];

            // Check individual file for path traversal
            if ($this->hasPathTraversal($stat['name'])) {
                return [
                    'safe' => false,
                    'reason' => "Terdeteksi path traversal attack dalam file: {$stat['name']}",
                ];
            }

            // Check nesting depth
            if ($this->getNestingDepth($stat['name']) > $this->maxNestingDepth) {
                return [
                    'safe' => false,
                    'reason' => "Direktori terlalu dalam: {$stat['name']}",
                ];
            }
        }

        // Check total extracted size
        if ($totalUncompressedSize > $this->maxExtractedSize) {
            $maxMB = round($this->maxExtractedSize / 1048576);
            $actualMB = round($totalUncompressedSize / 1048576);

            return [
                'safe' => false,
                'reason' => "Ukuran ekstrak terlalu besar ({$actualMB}MB). Maksimal: {$maxMB}MB",
            ];
        }

        // Check compression ratio (ZIP bomb detection)
        if ($compressedSize > 0) {
            $ratio = $totalUncompressedSize / $compressedSize;
            if ($ratio > $this->maxCompressionRatio) {
                return [
                    'safe' => false,
                    'reason' => "Rasio kompresi mencurigakan ({$ratio}x). Kemungkinan ZIP bomb.",
                ];
            }
        }

        return ['safe' => true, 'reason' => null];
    }

    /**
     * Validate individual filename
     */
    protected function validateFilename(string $filename, array $allowedExtensions): array
    {
        // Check for dangerous extensions
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $this->dangerousExtensions)) {
            return [
                'valid' => false,
                'reason' => "Ekstensi berbahaya: .{$ext}",
            ];
        }

        // If whitelist provided, check against it
        if (! empty($allowedExtensions) && ! in_array($ext, $allowedExtensions)) {
            return [
                'valid' => false,
                'reason' => "Ekstensi tidak diizinkan: .{$ext}",
            ];
        }

        // Check for null bytes
        if (strpos($filename, "\0") !== false) {
            return [
                'valid' => false,
                'reason' => 'Null byte dalam nama file',
            ];
        }

        return ['valid' => true, 'reason' => null];
    }

    /**
     * Check for path traversal attempts
     */
    protected function hasPathTraversal(string $filename): bool
    {
        // Normalize path separators
        $normalized = str_replace('\\', '/', $filename);

        // Check for parent directory references
        if (strpos($normalized, '../') !== false || strpos($normalized, '..\\') !== false) {
            return true;
        }

        // Check for absolute paths
        if (strpos($normalized, '/') === 0 || preg_match('/^[A-Za-z]:/', $normalized)) {
            return true;
        }

        return false;
    }

    /**
     * Get directory nesting depth
     */
    protected function getNestingDepth(string $filename): int
    {
        return substr_count($filename, '/');
    }

    /**
     * Sanitize filename for safe extraction
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove any path traversal
        $filename = str_replace(['../', '..\\'], '', $filename);

        // Remove leading slashes
        $filename = ltrim($filename, '/\\');

        // Replace dangerous characters
        $filename = preg_replace('/[<>:"|?*\x00-\x1f]/', '_', $filename);

        return $filename;
    }

    /**
     * Set maximum number of files
     */
    public function setMaxFiles(int $max): self
    {
        $this->maxFiles = $max;

        return $this;
    }

    /**
     * Set maximum extracted size in bytes
     */
    public function setMaxExtractedSize(int $bytes): self
    {
        $this->maxExtractedSize = $bytes;

        return $this;
    }

    /**
     * Set maximum compression ratio
     */
    public function setMaxCompressionRatio(float $ratio): self
    {
        $this->maxCompressionRatio = $ratio;

        return $this;
    }

    /**
     * Validate an uploaded ZIP file without extracting
     */
    public function validateUploadedZip(UploadedFile $file): array
    {
        $zip = new ZipArchive;
        $path = $file->getRealPath();

        if ($zip->open($path) !== true) {
            return ['valid' => false, 'reason' => 'File ZIP tidak valid atau corrupt'];
        }

        $validation = $this->validateZipContents($zip, $path);
        $zip->close();

        return [
            'valid' => $validation['safe'],
            'reason' => $validation['reason'],
        ];
    }
}
