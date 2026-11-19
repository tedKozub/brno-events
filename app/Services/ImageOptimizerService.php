<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ImageOptimizerService
{
    /**
     * Default maximum dimensions and quality.
     */
    public const MAX_BANNER_WIDTH = 1200;
    public const MAX_BANNER_HEIGHT = 900;
    public const MAX_POSTER_WIDTH = 1000;
    public const MAX_POSTER_HEIGHT = 1400;
    public const DEFAULT_JPEG_QUALITY = 78;
    public const DEFAULT_WEBP_QUALITY = 80;
    public const DEFAULT_PNG_COMPRESSION = 8;

    /**
     * Optimizes raw binary image data.
     */
    public function optimizeBytes(string $bytes, string $detectedType = 'banner', int $quality = self::DEFAULT_JPEG_QUALITY): string
    {
        if (!extension_loaded('gd') || empty($bytes)) {
            return $bytes;
        }

        $imageInfo = @getimagesizefromstring($bytes);
        if (!$imageInfo) {
            return $bytes;
        }

        [$origWidth, $origHeight, $imageType] = $imageInfo;

        if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
            return $bytes;
        }

        $maxW = ($detectedType === 'poster') ? self::MAX_POSTER_WIDTH : self::MAX_BANNER_WIDTH;
        $maxH = ($detectedType === 'poster') ? self::MAX_POSTER_HEIGHT : self::MAX_BANNER_HEIGHT;

        // If image is already smaller than max bounds and smaller than 150KB, keep as is
        if ($origWidth <= $maxW && $origHeight <= $maxH && strlen($bytes) <= 150 * 1024 && $imageType !== IMAGETYPE_PNG) {
            return $bytes;
        }

        $src = @imagecreatefromstring($bytes);
        if (!$src) {
            return $bytes;
        }

        $ratio = min($maxW / $origWidth, $maxH / $origHeight, 1.0);
        $newWidth = (int) max(1, round($origWidth * $ratio));
        $newHeight = (int) max(1, round($origHeight * $ratio));

        $dst = imagecreatetruecolor($newWidth, $newHeight);
        if (!$dst) {
            imagedestroy($src);
            return $bytes;
        }

        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_WEBP) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        ob_start();
        if ($imageType === IMAGETYPE_PNG) {
            imagepng($dst, null, self::DEFAULT_PNG_COMPRESSION);
        } elseif ($imageType === IMAGETYPE_WEBP) {
            imagewebp($dst, null, self::DEFAULT_WEBP_QUALITY);
        } else {
            imagejpeg($dst, null, $quality);
        }
        $optimizedBytes = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        if (!empty($optimizedBytes) && strlen($optimizedBytes) < strlen($bytes)) {
            return $optimizedBytes;
        }

        return $bytes;
    }

    /**
     * Optimizes an image file on disk in place (or into an optional target path).
     *
     * @return array{success: bool, original_bytes: int, new_bytes: int, saved_percent: float}
     */
    public function optimizeFile(string $filePath, ?string $targetPath = null, ?string $detectedType = null, int $quality = self::DEFAULT_JPEG_QUALITY): array
    {
        $targetPath = $targetPath ?: $filePath;
        $result = [
            'success' => false,
            'original_bytes' => 0,
            'new_bytes' => 0,
            'saved_percent' => 0.0,
        ];

        if (!file_exists($filePath)) {
            return $result;
        }

        $origBytes = filesize($filePath);
        $result['original_bytes'] = $origBytes;

        $info = @getimagesize($filePath);
        if (!$info) {
            return $result;
        }

        [$w, $h, $type] = $info;

        if (!$detectedType) {
            $detectedType = ($h > 1.15 * $w) ? 'poster' : 'banner';
        }

        $maxW = ($detectedType === 'poster') ? self::MAX_POSTER_WIDTH : self::MAX_BANNER_WIDTH;
        $maxH = ($detectedType === 'poster') ? self::MAX_POSTER_HEIGHT : self::MAX_BANNER_HEIGHT;

        $ratio = min($maxW / $w, $maxH / $h, 1.0);
        $newW = (int) max(1, round($w * $ratio));
        $newH = (int) max(1, round($h * $ratio));

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG => @imagecreatefrompng($filePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($filePath),
            default => null,
        };

        if (!$src) {
            return $result;
        }

        $dst = imagecreatetruecolor($newW, $newH);
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

        // Also write modern lightweight .webp copy alongside for high performance
        $webpPath = preg_replace('/\.(png|jpe?g)$/i', '.webp', $targetPath);
        if ($webpPath !== $targetPath && function_exists('imagewebp')) {
            @imagewebp($dst, $webpPath, self::DEFAULT_WEBP_QUALITY);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'img_opt_');
        if ($type === IMAGETYPE_PNG) {
            imagepng($dst, $tempPath, self::DEFAULT_PNG_COMPRESSION);
        } elseif ($type === IMAGETYPE_WEBP) {
            imagewebp($dst, $tempPath, self::DEFAULT_WEBP_QUALITY);
        } else {
            imagejpeg($dst, $tempPath, $quality);
        }

        imagedestroy($src);
        imagedestroy($dst);

        $newBytes = filesize($tempPath);
        $result['new_bytes'] = $newBytes;

        // If file became smaller or was resized, replace target
        if ($newBytes < $origBytes || $newW !== $w || $newH !== $h) {
            copy($tempPath, $targetPath);
            @unlink($tempPath);
            $result['success'] = true;
            $result['saved_percent'] = ($origBytes > 0) ? round((1 - ($newBytes / $origBytes)) * 100, 1) : 0.0;
            return $result;
        }

        @unlink($tempPath);
        $result['success'] = true;
        $result['new_bytes'] = $origBytes;
        return $result;
    }
}
