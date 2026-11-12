<footer class="mt-14 border-t border-base-300 bg-base-100 py-8 text-xs text-base-content/70 transition-colors">
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
        {{-- HORNÍ ČÁST: BRAND & AKČNÍ TLAČÍTKA --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
            <div class="space-y-1">
                <div class="flex items-center justify-center sm:justify-start gap-2">
                    <span class="font-black text-sm tracking-tight text-base-content">AKCE<span class="text-primary">BRNO</span></span>
                    <span>— Nezávislý studentský agregátor v Brně</span>
                </div>
                <p class="text-[11px] text-base-content/50">
                    Otevřená nástěnka pro studentský život a spolky na MUNI, VUT, MENDELU, UNOB & VETUNI.
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-center sm:justify-end gap-2.5 sm:gap-3">
                <button
                    type="button"
                    onclick="window.openPwaInstallModal()"
                    class="btn btn-sm btn-outline border-base-300 font-bold text-xs gap-2 rounded-xl hover:bg-base-200 text-base-content hover:text-primary transition-all px-3.5"
                    title="Návod a přidání aplikace na plochu telefonu"
                >
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <span>Přidat na plochu / PWA</span>
                </button>

                <a
                    href="/admin"
                    class="btn btn-sm btn-outline border-base-300 font-bold text-xs gap-2 rounded-xl hover:bg-base-200 text-base-content hover:text-primary transition-all px-3.5"
                >
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
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
                <span>© {{ date('Y') }} BrnoAkce. Všechna práva pořadatelů vyhrazena.</span>
            </div>
        </div>
    </div>
</footer>