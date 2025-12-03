<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePwaIcons extends Command
{
    protected $signature = 'pwa:icons {source=public/assets/images/logo.png}';
    protected $description = 'Generate PWA icons from source image';

    public function handle()
    {
        $source = $this->argument('source');
        $sizes = [72, 96, 128, 144, 152, 192, 384, 512];
        $outputDir = public_path('icons');

        if (!file_exists($source)) {
            $this->error("Source file not found: {$source}");
            return 1;
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $sourceImage = imagecreatefrompng($source);
        if (!$sourceImage) {
            $this->error("Failed to load source image");
            return 1;
        }

        foreach ($sizes as $size) {
            $resized = imagecreatetruecolor($size, $size);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            
            imagecopyresampled(
                $resized, $sourceImage,
                0, 0, 0, 0,
                $size, $size,
                imagesx($sourceImage), imagesy($sourceImage)
            );

            $output = "{$outputDir}/icon-{$size}x{$size}.png";
            imagepng($resized, $output);
            imagedestroy($resized);
            
            $this->info("Generated: icon-{$size}x{$size}.png");
        }

        imagedestroy($sourceImage);
        $this->info('PWA icons generated successfully!');
        return 0;
    }
}
