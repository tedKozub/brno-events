<?php

namespace App\Console\Commands;

use App\Services\ImageOptimizerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class OptimizeImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:optimize
                            {--path= : Specific relative path to optimize (e.g. events/covers/article_129_cb189552.jpg)}
                            {--force : Re-optimize files even if already below 150KB}
                            {--limit= : Limit the number of images to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compresses and downscales stored event covers and posters to lightweight web resolutions';

    /**
     * Execute the console command.
     */
    public function handle(ImageOptimizerService $optimizer): int
    {
        $specificPath = $this->option('path');
        $force = (bool) $this->option('force');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $disk = Storage::disk('public');

        if ($specificPath) {
            $fullPath = $disk->path(ltrim($specificPath, '/'));
            if (!file_exists($fullPath)) {
                $this->error("Soubor nenalezen: {$fullPath}");
                return 1;
            }
            $files = [$fullPath];
        } else {
            $coversDir = $disk->path('events/covers');
            if (!is_dir($coversDir)) {
                $this->info("Adresář {$coversDir} neexistuje.");
                return 0;
            }
            $files = File::files($coversDir);
        }

        $processed = 0;
        $totalOriginal = 0;
        $totalNew = 0;

        $this->info("Zpracovávám obrázky v public storage...");

        foreach ($files as $file) {
            $filePath = is_string($file) ? $file : $file->getPathname();
            $filename = basename($filePath);

            // Skip hidden or non-image files
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                continue;
            }

            $origSize = filesize($filePath);

            // Skip tiny files unless forced
            if (!$force && $origSize <= 100 * 1024) {
                $totalOriginal += $origSize;
                $totalNew += $origSize;
                continue;
            }

            $detectedType = str_starts_with($filename, 'poster_') ? 'poster' : 'banner';
            $result = $optimizer->optimizeFile($filePath, $filePath, $detectedType);

            if ($result['success']) {
                $processed++;
                $totalOriginal += $result['original_bytes'];
                $totalNew += $result['new_bytes'];

                if ($result['saved_percent'] > 0) {
                    $this->line(sprintf(
                        "  ✓ %s: %s -> %s (úspora %s%%)",
                        $filename,
                        $this->formatBytes($result['original_bytes']),
                        $this->formatBytes($result['new_bytes']),
                        $result['saved_percent']
                    ));
                }
            }

            if ($limit && $processed >= $limit) {
                break;
            }
        }

        $totalSaved = max(0, $totalOriginal - $totalNew);
        $totalSavedPercent = ($totalOriginal > 0) ? round(($totalSaved / $totalOriginal) * 100, 1) : 0;

        $this->newLine();
        $this->info("Optimalizace dokončena!");
        $this->line("Zpracováno souborů: {$processed}");
        $this->line("Původní velikost: " . $this->formatBytes($totalOriginal));
        $this->line("Nová velikost:    " . $this->formatBytes($totalNew));
        $this->info("Celkem ušetřeno:  " . $this->formatBytes($totalSaved) . " ({$totalSavedPercent}%)");

        return 0;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
