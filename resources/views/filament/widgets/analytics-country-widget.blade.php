<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="text-base font-bold text-gray-950 dark:text-white">🌍 Geografie & Režim návštěvnosti</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">(posledních 14 dní)</span>
                </div>

                {{-- Přepínač pro vývojáře a adminy --}}
                <div class="flex items-center gap-2">
                    @if($isInternalDevice)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            Toto zařízení: VYLOUČENO (Interní režim)
                        </span>
                        <a
                            href="/analytics/toggle-device?redirect_to=/admin"
                            class="text-xs font-bold text-primary-600 hover:text-primary-500 dark:text-primary-400 underline cursor-pointer"
                        >
                            Zahrnout do statistik &rarr;
                        </a>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Toto zařízení: Započítává se do statistik
                        </span>
                        <a
                            href="/analytics/toggle-device?redirect_to=/admin"
                            class="text-xs font-bold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white underline cursor-pointer"
                            title="Kliknutím vyloučíte své osobní prohlížení a testování z reálných čísel"
                        >
                            Vyloučit toto zařízení &rarr;
                        </a>
                    @endif
                </div>
            </div>
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
            {{-- Státy / Geografické rozložení --}}
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Návštěvníci podle států (Česko, Slovensko a zahraničí)
                </h3>

                @if(empty($countries))
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 text-xs text-gray-500 text-center">
                        Zatím nejsou zaznamenána žádná data o návštěvnosti.
                    </div>
                @else
                    <div class="space-y-2.5">
                        @foreach($countries as $country)
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base leading-none">{{ $country['flag'] }}</span>
                                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ $country['name'] }}</span>
                                        <span class="text-gray-400 font-mono text-[11px]">({{ $country['code'] }})</span>
                                    </div>
                                    <div class="flex items-center gap-2 font-mono">
                                        <span class="font-semibold text-gray-700 dark:text-gray-300">{{ number_format($country['count'], 0, ',', ' ') }}</span>
                                        <span class="text-gray-400 text-[11px]">({{ $country['percentage'] }}%)</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-primary-600 h-1.5 rounded-full" style="width: {{ $country['percentage'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Segmentace provozu: Reální studenti vs Interní testy --}}
            <div class="space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Oddělení reálného provozu a vývojového testování
                </h3>

                <div class="grid grid-cols-3 gap-3">
                    <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40">
                        <div class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Reální studenti
                        </div>
                        <div class="text-xl font-black text-gray-900 dark:text-white mt-1">
                            {{ number_format($traffic['real'], 0, ',', ' ') }}
                        </div>
                        <div class="text-[10px] text-gray-500 mt-0.5">Veřejný organický provoz</div>
                    </div>

                    <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40">
                        <div class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Správa & Vyloučení
                        </div>
                        <div class="text-xl font-black text-gray-900 dark:text-white mt-1">
                            {{ number_format($traffic['internal'], 0, ',', ' ') }}
                        </div>
                        <div class="text-[10px] text-gray-500 mt-0.5">Admin & vaše zařízení</div>
                    </div>

                    <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40">
                        <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            Automaty & Testy
                        </div>
                        <div class="text-xl font-black text-gray-900 dark:text-white mt-1">
                            {{ number_format($traffic['test'], 0, ',', ' ') }}
                        </div>
                        <div class="text-[10px] text-gray-500 mt-0.5">Playwright E2E & boty</div>
                    </div>
                </div>

                <div class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-1.5 bg-gray-100/60 dark:bg-gray-800/50 p-2.5 rounded-lg border border-gray-200/60 dark:border-gray-800/60">
                    <svg class="w-4 h-4 text-primary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>
                        Hlavní statistiky výše zobrazují <strong>výhradně reálné návštěvníky</strong>. Jakékoliv procházení z přihlášeného adminu nebo zařízení s aktivovaným vyloučením je automaticky přesunuto do interní složky.
                    </span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
