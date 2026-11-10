<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\ScrapeSource;
use Symfony\Component\Yaml\Yaml;

class ScrapeSourceManager
{
    /**
     * Synchronizes ScrapeSource database records with config/scrape_sources.yaml (or .json).
     *
     * @return array{added: int, updated: int, deactivated: int}
     */
    public static function syncFromConfig(): array
    {
        $sourcesConfig = self::loadConfig();

        if (empty($sourcesConfig)) {
            return ['added' => 0, 'updated' => 0, 'deactivated' => 0];
        }

        $added = 0;
        $updated = 0;
        $activeConfigIdentifiers = [];

        foreach ($sourcesConfig as $item) {
            $identifier = $item['id_slug'] ?? md5($item['url']);
            $activeConfigIdentifiers[] = $identifier;

            // Lookup organization if specified
            $orgId = null;
            if (!empty($item['organization_slug'])) {
                $orgId = Organization::where('slug', $item['organization_slug'])->value('id');
            }

            $source = ScrapeSource::where('target_identifier', $identifier)
                ->orWhere('url', $item['url'])
                ->first();

            $data = [
                'name' => $item['name'] ?? 'Zdroj scrapingu',
                'type' => $item['type'] ?? 'web_generic',
                'university' => $item['university'] ?? 'OTHER',
                'url' => $item['url'],
                'target_identifier' => $identifier,
                'is_active' => (bool) ($item['is_active'] ?? true),
                'organization_id' => $orgId,
            ];

            if ($source) {
                $source->update($data);
                $updated++;
            } else {
                ScrapeSource::create($data);
                $added++;
            }
        }

        // Deactivate sources previously managed by config but no longer present in it
        $deactivated = ScrapeSource::whereNotNull('target_identifier')
            ->whereNotIn('target_identifier', $activeConfigIdentifiers)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return [
            'added' => $added,
            'updated' => $updated,
            'deactivated' => $deactivated,
        ];
    }

    /**
     * Loads sources array from YAML or JSON config.
     */
    public static function loadConfig(): array
    {
        $yamlPath = config_path('scrape_sources.yaml');
        $jsonPath = config_path('scrape_sources.json');

        if (file_exists($yamlPath)) {
            $parsed = Yaml::parseFile($yamlPath);
            return $parsed['sources'] ?? [];
        }

        if (file_exists($jsonPath)) {
            $content = file_get_contents($jsonPath);
            $parsed = json_decode($content, true);
            return $parsed['sources'] ?? [];
        }

        return [];
    }
}
