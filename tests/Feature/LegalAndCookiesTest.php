<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalAndCookiesTest extends TestCase
{
    public function test_terms_page_renders_successfully_with_copyright_and_takedown(): void
    {
        $response = $this->get('/podminky');

        $response->assertStatus(200);
        $response->assertSee('Podmínky užití', false);
        $response->assertSee('Autorská práva', false);
        $response->assertSee('Notice & Takedown', false);
        $response->assertSee('121/2000 Sb.', false); // Autorský zákon
        $response->assertSee('480/2004 Sb.', false); // Zákon o službách informační společnosti
        $response->assertSee('legal@brnoakce.cz', false);
    }

    public function test_privacy_page_renders_successfully_with_gdpr_and_cookie_rules(): void
    {
        $response = $this->get('/ochrana-soukromi');

        $response->assertStatus(200);
        $response->assertSee('Zásady ochrany soukromí', false);
        $response->assertSee('GDPR', false);
        $response->assertSee('374/2021 Sb.', false); // Novela zákona o elektronických komunikacích
        $response->assertSee('privacy@brnoakce.cz', false);
        $response->assertSee('Úřad pro ochranu osobních údajů', false);
    }

    public function test_homepage_includes_cookie_consent_component_and_footer_links(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('id="cookie_consent_root"', false);
        $response->assertSee('id="cookie_consent_banner"', false);
        $response->assertSee('id="cookie_settings_modal"', false);
        $response->assertSee('/podminky', false);
        $response->assertSee('/ochrana-soukromi', false);
        $response->assertSee('Nastavení cookies', false);
        $response->assertSee('Notice & Takedown', false);
    }

    public function test_sitemap_includes_legal_and_privacy_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertSee('<loc>' . config('app.url', 'https://brnoakce.tadeaskozub.com') . '/podminky</loc>', false);
        $response->assertSee('<loc>' . config('app.url', 'https://brnoakce.tadeaskozub.com') . '/ochrana-soukromi</loc>', false);
    }
}