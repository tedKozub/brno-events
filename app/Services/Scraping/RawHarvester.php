<?php

namespace App\Services\Scraping;

use App\Models\ScrapeSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RawHarvester
{
    protected string $baseStoragePath;

    public function __construct(?string $baseStoragePath = null)
    {
        $this->baseStoragePath = $baseStoragePath ?: storage_path('app/scraping_raw');
    }

    /**
     * Stáhne surový obsah ze zdroje a uloží ho do denního archivu.
     *
     * @return array{file_path: string, meta_path: string, bytes: int, status_code: int, format: string}
     */
    public function harvestSource(ScrapeSource $source, ?string $dateFolder = null): array
    {
        $dateFolder = $dateFolder ?: Carbon::now()->format('Y-m-d');
        $targetDir = "{$this->baseStoragePath}/{$dateFolder}";

        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $url = $source->url;
        $slug = Str::slug($source->name);
        $type = strtolower($source->type);
        $ext = $this->determineExtension($type, $url);

        $fileName = "{$source->id}_{$slug}.{$ext}";
        $metaName = "{$source->id}_{$slug}.meta.json";

        $filePath = "{$targetDir}/{$fileName}";
        $metaPath = "{$targetDir}/{$metaName}";

        $content = '';
        $statusCode = 200;
        $contentType = 'text/plain';

        if (str_starts_with($url, 'file://')) {
            $localPath = substr($url, 7);
            if (File::exists($localPath)) {
                $content = File::get($localPath);
            }
        } elseif (File::exists($url)) {
            $content = File::get($url);
        } elseif (filter_var($url, FILTER_VALIDATE_URL)) {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 LiveBrnoHarvester/2.0',
                'Accept' => 'text/html,application/xhtml+xml,application/xml,text/calendar;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'cs,en;q=0.9',
            ])->timeout(25)->get($url);

            $statusCode = $response->status();
            $contentType = $response->header('Content-Type') ?: 'text/html';
            $content = $response->body();
        } else {
            // Pro sociální sítě / pseudo-url (např. handle na IG)
            $content = json_encode([
                'target' => $source->target_identifier ?: $source->url,
                'name' => $source->name,
                'university' => $source->university,
                'harvested_at' => Carbon::now()->toIso8601String(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $ext = 'json';
        }

        File::put($filePath, $content);

        $bytes = strlen($content);
        $meta = [
            'source_id' => $source->id,
            'source_name' => $source->name,
            'source_url' => $source->url,
            'source_type' => $source->type,
            'university' => $source->university,
            'fetched_at' => Carbon::now()->toIso8601String(),
            'status_code' => $statusCode,
            'content_type' => $contentType,
            'bytes' => $bytes,
            'sha256' => hash('sha256', $content),
            'file_name' => $fileName,
        ];

        File::put($metaPath, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return [
            'file_path' => $filePath,
            'meta_path' => $metaPath,
            'bytes' => $bytes,
            'status_code' => $statusCode,
            'format' => $ext,
        ];
    }

    /**
     * Smaže staré snapshoty starší než specifikovaný počet dní (default 14).
     */
    public function pruneOldSnapshots(int $daysToKeep = 14): int
    {
        if (!File::isDirectory($this->baseStoragePath)) {
            return 0;
        }

        $deletedCount = 0;
        $cutoffDate = Carbon::now()->subDays($daysToKeep)->format('Y-m-d');
        $directories = File::directories($this->baseStoragePath);

        foreach ($directories as $dir) {
            $dirName = basename($dir);
            // Očekáváme složky formátu YYYY-MM-DD
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dirName)) {
                if ($dirName < $cutoffDate) {
                    File::deleteDirectory($dir);
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    protected function determineExtension(string $type, string $url): string
    {
        if (str_contains($type, 'ical') || str_ends_with($url, '.ics')) {
            return 'ics';
        }
        if (str_contains($type, 'rss') || str_ends_with($url, '.xml')) {
            return 'xml';
        }
        if (str_contains($type, 'json') || str_contains($type, 'instagram') || str_contains($type, 'facebook')) {
            return 'json';
        }
        return 'html';
    }
}
