<footer class="mt-14 border-t border-base-300 bg-base-100 py-8 text-xs text-base-content/70 transition-colors">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
        {{-- HORNÍ ČÁST: BRAND & AKČNÍ TLAČÍTKA --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
            <div class="space-y-1">
                <div class="flex items-center justify-center sm:justify-start gap-2">
                    {!! \App\Helpers\BrandHelper::logoHtml('sm') !!}
                    <span>{{ \App\Helpers\BrandHelper::isStudyBrno() ? 'Průvodce studentským a městským životem v Brně' : 'Nezávislý studentský agregátor v Brně' }}</span>
                </div>
                <p class="text-[11px] text-base-content/50">
                    Otevřená nástěnka pro studentský život a spolky na MUNI, VUT, MENDELU, UNOB & VETUNI.
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-center sm:justify-end gap-2.5 sm:gap-3">
                <button
                    type="button"
                    onclick="window.openPwaInstallModal()"
                    class="btn btn-sm btn-outline border-base-300 font-bold text-xs rounded-xl hover:bg-base-200 text-base-content hover:text-primary transition-all px-3.5"
                    title="Návod a přidání aplikace na plochu telefonu"
                >
                    <span>Přidat na plochu / PWA</span>
                </button>

                <a
                    href="/admin"
                    class="btn btn-sm btn-outline border-base-300 font-bold text-xs rounded-xl hover:bg-base-200 text-base-content hover:text-primary transition-all px-3.5"
                >
                    <span>Vstup pro spolky</span>
                </a>
            </div>
        </div>

        {{-- DOLNÍ ČÁST: PRÁVNÍ NAVIGACE & COOKIES (GDPR, AUTORSKÁ PRÁVA, NOTICE & TAKEDOWN) --}}
        <div class="pt-4 border-t border-base-200 flex flex-col md:flex-row items-center justify-between gap-3 text-center md:text-left">
            <div class="flex flex-wrap items-center justify-center md:justify-start gap-x-4 gap-y-2 text-[11px] text-base-content/60">
                <a href="/podminky" class="hover:text-primary transition-colors underline-offset-4 hover:underline" wire:navigate>
                    Podmínky užití & Autorská práva
                </a>
                <span class="text-base-content/30 hidden sm:inline">•</span>
                <a href="/ochrana-soukromi" class="hover:text-primary transition-colors underline-offset-4 hover:underline" wire:navigate>
                    Ochrana soukromí (GDPR)
                </a>
                <span class="text-base-content/30 hidden sm:inline">•</span>
                <button
                    type="button"
                    onclick="window.openCookieSettingsModal()"
                    class="hover:text-primary transition-colors underline-offset-4 hover:underline cursor-pointer"
                >
                    Nastavení cookies
                </button>
                <span class="text-base-content/30 hidden sm:inline">•</span>
                <a href="/podminky#takedown" class="hover:text-primary transition-colors underline-offset-4 hover:underline" wire:navigate>
                    Notice & Takedown (Hlášení obsahu)
                </a>
            </div>

            <div class="text-[11px] text-base-content/50">
                <span>© {{ date('Y') }} {{ \App\Helpers\BrandHelper::brandName() }}. Všechna práva pořadatelů vyhrazena.</span>
            </div>
        </div>
    </div>
</footer>