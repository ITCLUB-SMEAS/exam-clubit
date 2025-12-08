<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class ValidateFileUpload
{
    protected array $allowedMimes = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'application/pdf' => ['pdf'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'text/csv' => ['csv'],
        'text/plain' => ['txt'],
    ];

    protected array $dangerousExtensions = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar',
        'exe', 'sh', 'bash', 'bat', 'cmd', 'com', 'scr', 'msi',
        'js', 'vbs', 'wsf', 'wsh', 'jar',
        'htaccess', 'htpasswd', 'ini', 'config',
        'svg', 'xml', 'html', 'htm',
    ];

    protected int $maxFileSize = 5242880; // 5MB default
    protected int $maxImageSize = 2097152; // 2MB for images

    public function handle(Request $request, Closure $next): Response
    {
        foreach ($request->allFiles() as $key => $file) {
            $files = is_array($file) ? $file : [$file];
            
            foreach ($files as $uploadedFile) {
                if (!$uploadedFile instanceof UploadedFile) continue;
                
                $validation = $this->validateFile($uploadedFile);
                if ($validation !== true) {
                    return response()->json([
                        'success' => false,
                        'message' => $validation,
                    ], 422);
                }
            }
        }

        return $next($request);
    }

    protected function validateFile(UploadedFile $file): string|bool
    {
        // Check if file is valid
        if (!$file->isValid()) {
            return 'File upload gagal atau corrupt.';
        }

        // Get real MIME type from file content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMimeType = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);

        // Check MIME type
        if (!array_key_exists($realMimeType, $this->allowedMimes)) {
            return 'Tipe file tidak diizinkan. Hanya: ' . implode(', ', array_keys($this->allowedMimes));
        }

        // Check extension matches MIME
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedMimes[$realMimeType])) {
            return 'Ekstensi file tidak sesuai dengan tipe file.';
        }

        // Check for dangerous extensions
        if (in_array($extension, $this->dangerousExtensions)) {
            return 'Ekstensi file berbahaya tidak diizinkan.';
        }

        // Check file size
        $maxSize = str_starts_with($realMimeType, 'image/') ? $this->maxImageSize : $this->maxFileSize;
        if ($file->getSize() > $maxSize) {
            $maxMB = round($maxSize / 1048576, 1);
            return "Ukuran file maksimal {$maxMB}MB.";
        }

        // Check for double extensions
        $filename = $file->getClientOriginalName();
        if (preg_match('/\.(php|phtml|exe|sh|bat|js|vbs)\./i', $filename)) {
            return 'Nama file mengandung ekstensi berbahaya.';
        }

        // Sanitize filename - prevent path traversal
        if (preg_match('/\.\.\/|\.\.\\\\/', $filename)) {
            return 'Nama file tidak valid (path traversal detected).';
        }

        // Check file content for malicious code
        $content = file_get_contents($file->getRealPath());
        
        // Check for PHP tags
        if (preg_match('/<\?php|<\?=|<script/i', $content)) {
            return 'File mengandung kode berbahaya.';
        }

        // For images, verify it's actually an image
        if (str_starts_with($realMimeType, 'image/')) {
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo === false) {
                return 'File bukan gambar yang valid.';
            }
        }

        return true;
    }
}
