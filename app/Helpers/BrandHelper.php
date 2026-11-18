<?php

namespace App\Helpers;

class BrandHelper
{
    /**
     * Check if the current request is for the studyBrno|liveBrno brand.
     */
    public static function isStudyBrno(): bool
    {
        // 1. Explicit environment configuration override
        $configured = strtolower((string) config('app.brand', ''));
        if ($configured === 'studybrno' || $configured === 'studybrno|livebrno') {
            return true;
        }

        // 2. Query parameter for easy local/manual testing (?brand=studybrno)
        try {
            if (request()->query('brand') === 'studybrno') {
                return true;
            }

            // 3. Request host check (supports domains with studybrno or livebrno)
            $host = strtolower((string) request()->getHost());
            if (str_contains($host, 'studybrno') || str_contains($host, 'livebrno')) {
                return true;
            }
        } catch (\Throwable $e) {
            // CLI commands or environments without active HTTP request
        }

        return false;
    }

    /**
     * Get the brand name string.
     */
    public static function brandName(): string
    {
        if (self::isStudyBrno()) {
            return 'studyBrno|liveBrno';
        }

        return 'BrnoAkce';
    }

    /**
     * Get a short brand name string (e.g. for PWA icon or compact mobile display).
     */
    public static function shortName(): string
    {
        if (self::isStudyBrno()) {
            return 'studyBrno';
        }

        return 'BrnoAkce';
    }

    /**
     * Get badge text shown beside the logo.
     */
    public static function badgeText(): string
    {
        if (self::isStudyBrno()) {
            return 'Student & Life';
        }

        return 'Student';
    }

    /**
     * Generate HTML for the brand logo.
     *
     * @param string $size 'lg' | 'sm'
     */
    public static function logoHtml(string $size = 'lg'): string
    {
        if (self::isStudyBrno()) {
            if ($size === 'sm') {
                return '<span class="font-black text-sm tracking-tight text-base-content inline-flex items-center"><span><span class="text-primary">study</span>Brno</span><span class="text-base-content/30 font-light mx-1">|</span><span><span class="text-primary">live</span>Brno</span></span>';
            }

            return '<span class="font-black text-base sm:text-xl tracking-tight text-base-content inline-flex items-center"><span><span class="text-primary">study</span>Brno</span><span class="text-base-content/30 font-light mx-1">|</span><span><span class="text-primary">live</span>Brno</span></span>';
        }

        if ($size === 'sm') {
            return '<span class="font-black text-sm tracking-tight text-base-content">AKCE<span class="text-primary">BRNO</span></span>';
        }

        return '<span class="font-black text-base sm:text-xl tracking-tight text-base-content">AKCE<span class="text-primary">BRNO</span></span>';
    }

    /**
     * Get page meta title.
     */
    public static function metaTitle(?string $subTitle = null): string
    {
        $brand = self::brandName();

        if ($subTitle) {
            return "{$subTitle} • {$brand}";
        }

        if (self::isStudyBrno()) {
            return 'studyBrno|liveBrno | Studentský a městský život v Brně';
        }

        return 'Studentské Akce Brno | Průvodce brněnským studentským životem';
    }

    /**
     * Get page meta description.
     */
    public static function metaDescription(): string
    {
        if (self::isStudyBrno()) {
            return 'Centralizovaný portál a kalendář pro studentské akce, život v Brně, festivaly, kluby, studovny a spolky (MUNI, VUT, MENDELU, UNOB, VETUNI).';
        }

        return 'Centralizovaný portál a kalendář pro studentské párty, festivaly, workshopy, kvízy a spolkové akce v Brně (MUNI, VUT, MENDELU, UNOB, VETUNI).';
    }
}
