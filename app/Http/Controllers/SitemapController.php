<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $events = Event::published()
            ->orderBy('starts_at', 'desc')
            ->take(200)
            ->get(['slug', 'updated_at', 'starts_at']);

        $baseUrl = config('app.url', 'https://brnoakce.tadeaskozub.com');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Homepage
        $xml .= '<url>';
        $xml .= "<loc>{$baseUrl}/</loc>";
        $xml .= '<lastmod>' . now()->toIso8601String() . '</lastmod>';
        $xml .= '<changefreq>hourly</changefreq>';
        $xml .= '<priority>1.0</priority>';
        $xml .= '</url>';

        // Studentská místa v Brně
        $xml .= '<url>';
        $xml .= "<loc>{$baseUrl}/mista</loc>";
        $xml .= '<lastmod>' . now()->toIso8601String() . '</lastmod>';
        $xml .= '<changefreq>weekly</changefreq>';
        $xml .= '<priority>0.9</priority>';
        $xml .= '</url>';

        // Events
        foreach ($events as $event) {
            $isUpcoming = $event->starts_at >= now();
            $priority = $isUpcoming ? '0.8' : '0.5';
            $changefreq = $isUpcoming ? 'daily' : 'monthly';

            $xml .= '<url>';
            $xml .= "<loc>{$baseUrl}/akce/{$event->slug}</loc>";
            $xml .= "<lastmod>{$event->updated_at->toIso8601String()}</lastmod>";
            $xml .= "<changefreq>{$changefreq}</changefreq>";
            $xml .= "<priority>{$priority}</priority>";
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
}
