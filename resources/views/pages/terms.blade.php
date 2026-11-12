<x-layouts.app
    title="Podmínky užití, autorská práva a Notice & Takedown | BrnoAkce"
    description="Podmínky užívání nezávislé platformy BrnoAkce, právní rámec agregace veřejných studentských akcí, autorská práva a postup pro odstranění obsahu (Notice & Takedown)."
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

                    <a href="/mista" wire:navigate class="btn btn-sm btn-ghost gap-1.5 font-bold rounded-xl text-base-content/80 hover:text-base-content hidden md:inline-flex">
                        Studentská místa
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Právní rámec a pravidla platformy</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-black text-base-content tracking-tight">
                Podmínky užití & Autorská práva
            </h1>
            <p class="text-sm sm:text-base text-base-content/70 leading-relaxed">
                Platné a účinné od 1. ledna 2026. Tyto podmínky upravují fungování nezávislého komunitního agregátoru studentských akcí BrnoAkce, pravidla zpracování informací, respektování autorských práv a postup pro hlášení a okamžité odstranění obsahu.
            </p>
        </div>

        {{-- Sekce 1: Povaha služby --}}
        <section class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-base-200 text-primary font-black text-xs flex items-center justify-center">1</span>
                Povaha a účel platformy
            </h2>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-3 leading-relaxed">
                <p>
                    Platforma <strong>BrnoAkce</strong> (dále jen „služba“ nebo „provozovatel“) je nezávislý, nekomerční informační servis a agregátor určený pro studenty, akademické pracovníky a přátele brněnského vysokoškolského života (MUNI, VUT, MENDELU, UNOB, VETUNI a související brněnské spolky a kulturní organizace).
                </p>
                <p>
                    Cílem služby je zpřehlednit a centralizovat pestrý studentský život v Brně, zvýšit povědomí o neziskových aktivitách spolků a umožnit studentům objevovat přednášky, kulturní akce, kvízy, workshopy i sportovní turnaje na jednom místě.
                </p>
            </div>
        </section>

        {{-- Sekce 2: Zdroje dat a agregace --}}
        <section class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-base-200 text-primary font-black text-xs flex items-center justify-center">2</span>
                Zdroje informací a způsob jejich zpracování
            </h2>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-3 leading-relaxed">
                <p>
                    Data o akcích jsou na platformu shromažďována následujícími způsoby:
                </p>
                <ul class="list-disc list-inside space-y-1.5 pl-2">
                    <li><strong>Přímým zadáním pořadateli:</strong> Studentské spolky, fakultní organizace a pořadatelé mohou své akce vkládat přímo prostřednictvím klientského rozhraní pro spolky.</li>
                    <li><strong>Automatizovaným vytěžováním veřejně přístupných zdrojů (scraping):</strong> Informace jsou čerpány z veřejně dostupných oficiálních kalendářů univerzit, fakultních webů a veřejných příspěvků studentských spolků na sociálních sítích.</li>
                </ul>
                <p>
                    Služba nevytváří vlastní události, nevystupuje jako pořadatel a u každé agregované akce <strong>vždy uvádí původního pořadatele a přímý odkaz na původní zdrojový web či událost</strong>.
                </p>
            </div>
        </section>

        {{-- Sekce 3: Autorskoprávní rámec v ČR a EU --}}
        <section class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-base-200 text-primary font-black text-xs flex items-center justify-center">3</span>
                Autorskoprávní rámec (Zákon č. 121/2000 Sb. a právo EU)
            </h2>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-3 leading-relaxed">
                <p>
                    Činnost platformy BrnoAkce je koncipována v plném souladu s právním řádem České republiky a směrnicemi Evropské unie:
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
                    <div class="p-4 rounded-xl bg-base-200/60 border border-base-200 space-y-1.5">
                        <div class="font-bold text-base-content text-sm">Faktické údaje nejsou autorským dílem</div>
                        <p class="text-xs text-base-content/70">
                            Dle <strong>§ 2 odst. 6 autorského zákona</strong> není autorským dílem samotný údaj, faktická informace ani denní zpráva. Názvy akcí, data konání, časy, lokality a výše vstupného nepodléhají autorskoprávní ochraně.
                        </p>
                    </div>
                    <div class="p-4 rounded-xl bg-base-200/60 border border-base-200 space-y-1.5">
                        <div class="font-bold text-base-content text-sm">Zákonná citace & zpravodajství</div>
                        <p class="text-xs text-base-content/70">
                            Krátké výňatky a anotace jsou užívány v souladu s <strong>§ 31 (malá citace) a § 34 (zpravodajská licence)</strong> autorského zákona za účelem informování veřejnosti s uvedením jména autora a pramene.
                        </p>
                    </div>
                </div>
                <p class="pt-2">
                    <strong>Zvláštní právo pořizovatele databáze (§ 88 autorského zákona):</strong> Platforma neprovádí podstatné ani systematické vytěžování cizích chráněných komerčních databází. Zpracovává výhradně jednotlivé dílčí veřejné pozvánky na jednotlivé události.
                </p>
            </div>
        </section>

        {{-- Sekce 4: Plakáty a grafické materiály --}}
        <section class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-base-200 text-primary font-black text-xs flex items-center justify-center">4</span>
                Použití plakátů, bannerů a grafických materiálů
            </h2>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-3 leading-relaxed">
                <p>
                    Plakáty a grafické vizuály jsou pořadateli vytvářeny za účelem <strong>veřejné propagace akce a přilákání návštěvníků</strong>.
                </p>
                <p>
                    Zobrazení plakátu či banneru jako náhledu (thumbnail) u popisu akce slouží výhradně k podpoře účasti na této akci a je vždy doprovázeno odkazem na pořadatele. Majetková i osobnostní autorská práva k vizuálům zůstávají v plném rozsahu jejich autorům a pořadatelům.
                </p>
                <div class="p-4 rounded-xl bg-info/10 border border-info/20 text-info-content text-xs sm:text-sm space-y-1">
                    <div class="font-bold text-info">Zásada přednosti volných a vlastních podkladů:</div>
                    <p class="text-base-content/80">
                        Pokud na webu události není k dispozici oficiální propagační plakát akce, platforma nepoužívá náhodné chráněné fotografie, nýbrž automaticky aplikuje generický licencovaný snímek z volné fotobanky Unsplash nebo zástupný symbol kategorie.
                    </p>
                </div>
            </div>
        </section>

        {{-- Sekce 5: Notice and Takedown --}}
        <section id="takedown" class="card bg-base-100 border border-primary/30 shadow-md rounded-2xl p-6 sm:p-8 space-y-4 scroll-mt-24">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-primary text-primary-content font-black text-xs flex items-center justify-center">5</span>
                    Postup pro nahlášení a stažení obsahu (Notice & Takedown / DSA)
                </h2>
                <span class="badge badge-primary font-bold text-xs">Aktivní ochrana</span>
            </div>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-3 leading-relaxed">
                <p>
                    V souladu s <strong>§ 5 zákona č. 480/2004 Sb.</strong> o některých službách informační společnosti a <strong>Nařízením Evropského parlamentu a Rady (EU) 2022/2065 (Digital Services Act – DSA)</strong> uplatňuje provozovatel důsledný systém rychlého stažení či úpravy obsahu.
                </p>
                <p>
                    Pokud jste pořadatelem, autorem grafiky, textu či oprávněnou osobou a přejete si:
                </p>
                <ul class="list-disc list-inside space-y-1 pl-2 font-medium">
                    <li>Okamžitě odstranit plakát, obrázek či text akce,</li>
                    <li>Upravit nepřesné či zastaralé informace (čas, místo, vstupné),</li>
                    <li>Nebo celou událost z nástěnky BrnoAkce trvale vyřadit,</li>
                </ul>
                <p>
                    kontaktujte nás bez jakýchkoliv formalit na níže uvedeném e-mailu. Váš požadavek bude zpracován obratem, nejpozději však do <strong>24 až 48 hodin</strong> od doručení.
                </p>

                <div class="p-4 sm:p-5 rounded-xl bg-base-200/80 border border-base-300 flex flex-col sm:flex-row items-center justify-between gap-4 mt-2">
                    <div class="space-y-0.5 text-center sm:text-left">
                        <div class="text-xs uppercase font-bold text-base-content/50">Kontaktní adresa pro Notice & Takedown:</div>
                        <div class="text-base sm:text-lg font-black text-primary font-mono select-all">legal@brnoakce.cz</div>
                        <div class="text-[11px] text-base-content/60">Do předmětu uveďte „Notice & Takedown: [Název akce]“ a odkaz na událost.</div>
                    </div>
                    <a
                        href="mailto:legal@brnoakce.cz?subject=Notice%20and%20Takedown%20-%20Zadost%20o%20odstraneni%20obsahu"
                        class="btn btn-primary btn-sm font-bold rounded-xl gap-2 px-5 shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>Odeslat žádost</span>
                    </a>
                </div>
            </div>
        </section>

        {{-- Sekce 6: Vyloučení odpovědnosti --}}
        <section class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-base-200 text-primary font-black text-xs flex items-center justify-center">6</span>
                Vyloučení odpovědnosti za změny akcí
            </h2>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-3 leading-relaxed">
                <p>
                    Všechny informace jsou shromažďovány v dobré víře a s maximální péčí. Provozovatel však <strong>neodpovídá za případné změny programu, přesunutí termínu, vyprodání vstupenek či zrušení akce ze strany pořadatele</strong>.
                </p>
                <p>
                    Uživatelům důrazně doporučujeme před návštěvou akce ověřit aktuálnost údajů na přiloženém oficiálním odkazu pořadatele.
                </p>
            </div>
        </section>

        {{-- Sekce 7: Pravidla pro spolky --}}
        <section class="card bg-base-100 border border-base-300 shadow-xs rounded-2xl p-6 sm:p-8 space-y-4">
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-base-200 text-primary font-black text-xs flex items-center justify-center">7</span>
                Pravidla pro studentské spolky a pořadatele
            </h2>
            <div class="text-xs sm:text-sm text-base-content/80 space-y-3 leading-relaxed">
                <p>
                    Zástupci studentských spolků a organizátoři mají možnost spravovat profil svého spolku a vkládat akce přímo. Vložením akce pořadatel:
                </p>
                <ul class="list-disc list-inside space-y-1.5 pl-2">
                    <li>Potvrzuje, že má oprávnění akci jménem organizace publikovat a disponuje potřebnými právy k propagačním materiálům.</li>
                    <li>Zavazuje se nepublikovat klamavý, neetický, diskriminační či nezákonný obsah v rozporu s právními předpisy ČR.</li>
                    <li>Uděluje platformě bezúplatnou licenci k zobrazení plakátu a anotace za účelem propagace akce.</li>
                </ul>
            </div>
        </section>

        {{-- Sekce 8: Závěr --}}
        <div class="pt-4 text-center text-xs text-base-content/60 space-y-1">
            <p>Máte otázky nebo nápady na vylepšení studentského rozcestníku?</p>
            <p>Napište nám na <a href="mailto:info@brnoakce.cz" class="link link-primary font-semibold">info@brnoakce.cz</a>.</p>
        </div>
    </main>

    <x-footer />
</x-layouts.app>