<x-layouts.app
    title="Zásady ochrany osobních údajů (GDPR) a Cookies | BrnoAkce"
    description="Informace o ochraně osobních údajů (GDPR), zásadách minimalizace dat, využívání cookies a právech návštěvníků a pořadatelů studentského portálu BrnoAkce."
>
    {{-- HORNÍ HLAVIČKA S NAVIGACÍ --}}
    <header class="border-b border-base-300 bg-base-100/90 backdrop-blur-md sticky top-0 z-30 transition-colors shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20 gap-4">
                <div class="flex items-center gap-3">
                    <a href="/" wire:navigate class="flex items-center gap-2 group">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-primary to-orange-500 flex items-center justify-center text-primary-content shadow-md shadow-primary/20 group-hover:scale-105 transition-transform duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="font-black text-xl tracking-tight text-base-content block leading-tight">AKCE<span class="text-primary">BRNO</span></span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-base-content/50 block">Studentský rozcestník</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="/" wire:navigate class="btn btn-sm btn-ghost gap-1.5 font-bold rounded-xl text-base-content/80 hover:text-base-content">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span class="hidden sm:inline">Zpět na přehled akcí</span>
                        <span class="sm:hidden">Zpět</span>
                    </a>

                    <a href="/podminky" wire:navigate class="btn btn-sm btn-ghost gap-1.5 font-bold rounded-xl text-base-content/80 hover:text-base-content hidden md:inline-flex">
                        Podmínky užití
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- HLAVNÍ OBSAH --}}
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-10">
        {{-- Titulek & Intro karta --}}
        <div class="space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>GDPR & Ochrana osobních údajů</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-black text-base-content tracking-tight">
                Zásady ochrany soukromí
            </h1>
            <p class="text-sm sm:text-base text-base-content/70 leading-relaxed">
                Vaše soukromí bereme vážně. Tyto zásady popisují, jak nakládáme s údaji na platformě BrnoAkce v souladu s Nařízením Evropského parlamentu a Rady (EU) 2016/679 (GDPR) a zákonem č. 110/2019 Sb. o zpracování osobních údajů.
            </p>
        </div>

        {{-- Sekce 1: Správce údajů --}}
        <section class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-base-200 text-primary font-black text-xs flex items-center justify-center">1</span>
                Správce osobních údajů
            </h2>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-2 leading-relaxed">
                <p>
                    Správcem osobních údajů je provozovatel nezávislé platformy <strong>BrnoAkce</strong>.
                </p>
                <p>
                    Pro jakékoliv dotazy týkající se zpracování osobních údajů či uplatnění práv subjektů údajů nás můžete kontaktovat na e-mailové adrese:
                    <a href="mailto:privacy@brnoakce.cz" class="font-bold text-primary link link-hover">privacy@brnoakce.cz</a>.
                </p>
            </div>
        </section>

        {{-- Sekce 2: Zásada minimalizace údajů --}}
        <section class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-base-200 text-primary font-black text-xs flex items-center justify-center">2</span>
                Zásada minimalizace dat: Prohlížení bez registrace
            </h2>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-3 leading-relaxed">
                <div class="p-4 rounded-xl bg-success/10 border border-success/20 text-success-content flex items-start gap-3">
                    <svg class="w-5 h-5 text-success shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <div class="space-y-0.5 text-xs sm:text-sm text-base-content/90">
                        <span class="font-extrabold block text-base-content">Běžné používání je 100% anonymní</span>
                        <span>Pro procházení kalendáře akcí, filtrování, vyhledávání, prohlížení mapy i stažení iCal kalendáře není vyžadována žádná registrace, přihlášení ani zadávání e-mailu či jména.</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Sekce 3: Jaké údaje zpracováváme --}}
        <section class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-base-200 text-primary font-black text-xs flex items-center justify-center">3</span>
                Kategorie zpracovávaných údajů a právní základy
            </h2>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-4 leading-relaxed">
                {{-- A) Návštěvníci --}}
                <div class="p-4 rounded-xl bg-base-200/60 border border-base-200 space-y-2">
                    <h3 class="font-bold text-base-content text-sm">A. Technické systémové logy (návštěvníci)</h3>
                    <p class="text-xs text-base-content/75">
                        Při načtení webových stránek webový server automaticky zaznamenává technické údaje (anonymizovaná IP adresa, čas přístupu, identifikátor prohlížeče / User-Agent, stavový kód HTTP).
                    </p>
                    <p class="text-xs text-base-content/75">
                        <strong>Účel a právní základ:</strong> Zajištění stability sítě, prevence DDoS útoků a odhalování bezpečnostních incidentů na základě oprávněného zájmu správce dle <strong>čl. 6 odst. 1 písm. f) GDPR</strong>. Logy jsou automaticky rotovány a promazávány.
                    </p>
                </div>

                {{-- B) Nahlášení chyb --}}
                <div class="p-4 rounded-xl bg-base-200/60 border border-base-200 space-y-2">
                    <h3 class="font-bold text-base-content text-sm">B. Hlášení nesrovnalostí u akcí (dobrovolné)</h3>
                    <p class="text-xs text-base-content/75">
                        Pokud uživatel využije funkci „Nahlásit chybu“, zpracováváme typ hlášené vady a případný text poznámky zadaný uživatelem za účelem prověření a opravy údajů u akce (oprávněný zájem dle čl. 6 odst. 1 písm. f) GDPR).
                    </p>
                </div>

                {{-- C) Zástupci spolků --}}
                <div class="p-4 rounded-xl bg-base-200/60 border border-base-200 space-y-2">
                    <h3 class="font-bold text-base-content text-sm">C. Účty zástupců studentských spolků a organizátorů</h3>
                    <p class="text-xs text-base-content/75">
                        Při zřízení pořadatelského účtu pro správu akcí spolku zpracováváme jméno zástupce, kontaktní e-mailovou adresu a bezpečně zahashované heslo.
                    </p>
                    <p class="text-xs text-base-content/75">
                        <strong>Účel a právní základ:</strong> Správa profilu pořadatele, plnění podmínek užití služby dle <strong>čl. 6 odst. 1 písm. b) GDPR</strong> a komunikace týkající se vložených akcí.
                    </p>
                </div>
            </div>
        </section>

        {{-- Sekce 4: Cookies a lokální úložiště --}}
        <section class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-base-200 text-primary font-black text-xs flex items-center justify-center">4</span>
                Cookies a lokální úložiště (Zákon č. 374/2021 Sb.)
            </h2>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-3 leading-relaxed">
                <p>
                    V souladu s <strong>§ 89 odst. 3 zákona č. 127/2005 Sb.</strong> o elektronických komunikacích přísně rozlišujeme nezbytné technické cookies a nepovinné analytické nástroje:
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                    <div class="p-4 rounded-xl bg-base-200/60 border border-base-200 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-base-content text-sm">Nezbytné technické prvky</span>
                            <span class="badge badge-sm badge-neutral font-bold text-[10px]">Bez souhlasu</span>
                        </div>
                        <p class="text-xs text-base-content/70">
                            Zabezpečení relace (<code class="font-mono text-primary">XSRF-TOKEN</code>, <code class="font-mono text-primary">brno_events_session</code>), volba světlého/tmavého režimu (<code class="font-mono">brno_events_theme</code>) a uložení stavu vašeho souhlasu. Jsou nezbytné pro fungování požadované služby.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-base-200/60 border border-base-200 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-base-content text-sm">Analytické cookies</span>
                            <span class="badge badge-sm badge-primary font-bold text-[10px]">Pouze s opt-in souhlasem</span>
                        </div>
                        <p class="text-xs text-base-content/70">
                            Anonymní měření návštěvnosti sloužící ke zjištění zájmu o studentské kategorie a zrychlení webu. Bez vašeho výslovného souhlasu se žádné analytické ani sledovací skripty nespustí.
                        </p>
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-start">
                    <button
                        type="button"
                        onclick="window.openCookieSettingsModal()"
                        class="btn btn-outline border-base-300 btn-sm font-bold rounded-xl gap-2 text-xs"
                    >
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Otevřít nastavení cookies a změnit volbu</span>
                    </button>
                </div>
            </div>
        </section>

        {{-- Sekce 5: Zabezpečení --}}
        <section class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-base-200 text-primary font-black text-xs flex items-center justify-center">5</span>
                Zabezpečení a předávání dat
            </h2>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-3 leading-relaxed">
                <p>
                    Aplikujeme moderní bezpečnostní standardy:
                </p>
                <ul class="list-disc list-inside space-y-1.5 pl-2">
                    <li>Veškerá komunikace je šifrována pomocí protokolu <strong>HTTPS (TLS)</strong> s podporou HSTS.</li>
                    <li>Aktivní Content Security Policy (CSP), ochrana proti Cross-Site Scripting (XSS) a ochrana rámců (X-Frame-Options).</li>
                    <li>Uživatelská hesla pořadatelů jsou jednosměrně hashována moderními algoritmy (Argon2id/Bcrypt) a nikdy nejsou uložena v čitelné podobě.</li>
                    <li>Data nejsou prodávána, pronajímána ani předávána žádným marketingovým třetím stranám.</li>
                </ul>
            </div>
        </section>

        {{-- Sekce 6: Práva subjektů údajů --}}
        <section class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-base-200 text-primary font-black text-xs flex items-center justify-center">6</span>
                Vaše práva dle GDPR
            </h2>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-3 leading-relaxed">
                <p>
                    V souladu s články 15 až 22 obecného nařízení o ochraně osobních údajů máte právo:
                </p>
                <ul class="list-disc list-inside space-y-1.5 pl-2">
                    <li>Požadovat <strong>přístup</strong> k vašim osobním údajům a kopii zpracovávaných dat,</li>
                    <li>Požadovat <strong>opravu</strong> nepřesných nebo doplnění neúplných údajů,</li>
                    <li>Požadovat <strong>výmaz</strong> (právo „být zapomenut“), pokud odpadl důvod pro zpracování,</li>
                    <li>Požadovat <strong>omezení zpracování</strong>,</li>
                    <li>Vznést <strong>námitku</strong> proti zpracování založenému na oprávněném zájmu,</li>
                    <li>Kdykoliv <strong>odvolat udělený souhlas</strong> s cookies v zápatí webu.</li>
                </ul>
                <p class="pt-2">
                    Pokud se domníváte, že se zpracováním vašich údajů dochází k porušení právních předpisů, máte právo podat stížnost u dozorového orgánu:
                </p>
                <div class="p-4 rounded-xl bg-base-200/60 border border-base-200 space-y-1 text-xs sm:text-sm">
                    <div class="font-bold text-base-content">Úřad pro ochranu osobních údajů (ÚOOÚ)</div>
                    <div class="text-base-content/75">Pplk. Sochora 27, 170 00 Praha 7</div>
                    <div class="text-base-content/75">Web: <a href="https://www.uoou.cz" target="_blank" rel="noopener noreferrer" class="link link-primary">www.uoou.cz</a></div>
                </div>
            </div>
        </section>

        {{-- Sekce 7: Kontakt --}}
        <div class="pt-4 text-center text-xs text-base-content/60 space-y-1">
            <p>Dotazy k ochraně osobních údajů směřujte na <a href="mailto:privacy@brnoakce.cz" class="link link-primary font-semibold">privacy@brnoakce.cz</a>.</p>
        </div>
    </main>

    <x-footer />
</x-layouts.app>