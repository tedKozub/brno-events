<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OgImageController extends Controller
{
    public function show(string $slug): BinaryFileResponse|Response
    {
        $event = Event::with(['organization', 'category', 'venue'])->where('slug', $slug)->firstOrFail();

        $cacheDir = storage_path('app/public/og');
        if (!File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }

        $cacheFile = "{$cacheDir}/{$event->id}-{$event->slug}.jpg";

        // Pokud cache existuje a nebyla modifikována před úpravou akce, vrátíme ji
        if (File::exists($cacheFile) && File::lastModified($cacheFile) >= ($event->updated_at?->timestamp ?? 0)) {
            return response()->file($cacheFile, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=604800, immutable',
            ]);
        }

        // Parametry pro generátor
        $pythonBin = base_path('scrapers/.venv/bin/python');
        if (!file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }
        $script = base_path('scrapers/generate_og_card.py');

        $uni = $event->organization?->university ?: 'Brno';
        $category = $event->category?->name ?: 'Akce';
        $dateStr = $event->starts_at ? $event->starts_at->translatedFormat('l, d. m. • H:i') : '';
        $location = $event->location_display ?: 'Brno';
        $price = $event->formatted_price ?: 'Zdarma';

        $cmd = [
            $pythonBin,
            $script,
            '--title', $event->title,
            '--uni', $uni,
            '--category', $category,
            '--date', $dateStr,
            '--location', $location,
            '--price', $price,
            '--output', $cacheFile,
        ];

        if ($event->cover_image && !str_starts_with($event->cover_image, 'http') && File::exists(public_path(ltrim($event->cover_image, '/')))) {
            $cmd[] = '--bg';
            $cmd[] = public_path(ltrim($event->cover_image, '/'));
        }

        try {
            $result = Process::timeout(10)->run($cmd);
            if ($result->successful() && File::exists($cacheFile)) {
                return response()->file($cacheFile, [
                    'Content-Type' => 'image/jpeg',
                    'Cache-Control' => 'public, max-age=604800, immutable',
                ]);
            }
        } catch (\Throwable $e) {}

        // Fallback: Default branded OG image
        $defaultImage = public_path('images/og-brnoakce.jpg');
        return response()->file($defaultImage, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
