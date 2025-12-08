<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ImageOptimizationService
{
    public function optimize(UploadedFile $file, string $path, int $maxWidth = 800): string
    {
        $image = Image::read($file);
        
        if ($image->width() > $maxWidth) {
            $image->scale(width: $maxWidth);
        }
        
        $filename = time() . '_' . $file->hashName();
        $fullPath = $path . '/' . $filename;
        
        Storage::disk('public')->put($fullPath, $image->encodeByMediaType(quality: 85));
        
        return $fullPath;
    }
}
