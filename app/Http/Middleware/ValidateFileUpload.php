<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class ValidateFileUpload
{
    protected array $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
    ];

    protected array $dangerousExtensions = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar',
        'exe', 'sh', 'bash', 'bat', 'cmd', 'com', 'scr',
        'js', 'vbs', 'wsf', 'wsh',
        'htaccess', 'htpasswd',
    ];

    protected int $maxFileSize = 10485760; // 10MB

    public function handle(Request $request, Closure $next): Response
    {
        foreach ($request->allFiles() as $key => $file) {
            $files = is_array($file) ? $file : [$file];
            
            foreach ($files as $uploadedFile) {
                if (!$uploadedFile instanceof UploadedFile) continue;
                
                if (!$this->validateFile($uploadedFile)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File tidak valid atau tidak diizinkan.',
                    ], 422);
                }
            }
        }

        return $next($request);
    }

    protected function validateFile(UploadedFile $file): bool
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            return false;
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->allowedMimes)) {
            return false;
        }

        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, $this->dangerousExtensions)) {
            return false;
        }

        // Check for double extensions
        $filename = $file->getClientOriginalName();
        if (preg_match('/\.(php|phtml|exe|sh|bat)\./i', $filename)) {
            return false;
        }

        // Check file content for PHP tags
        $content = file_get_contents($file->getRealPath());
        if (preg_match('/<\?php|<\?=/i', $content)) {
            return false;
        }

        return true;
    }
}
