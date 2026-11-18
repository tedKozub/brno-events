<div
    x-data="themeEditorController()"
    x-init="initTheme()"
    class="fixed bottom-4 right-4 z-[9999] select-none font-sans"
    @keydown.escape.window="isOpen = false"
>
    {{-- PLOVOUCÍ MINIMALIZOVANÉ TLAČÍTKO / SPOUŠTĚČ --}}
    <button
        x-show="!isOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-90 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        @click="isOpen = true"
        type="button"
        class="flex items-center gap-2.5 px-4 py-2.5 rounded-full bg-primary text-primary-content shadow-2xl hover:scale-105 active:scale-95 transition-all font-bold text-xs sm:text-sm border-2 border-white/20 backdrop-blur-md"
        title="Otevřít živý editor motivu (Theme Generator)"
    >
        <span class="text-base animate-spin-slow">🎨</span>
        <span>Theme Editor</span>
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
    </button>

    {{-- HLAVNÍ PLOVOUCÍ KONFIGURAČNÍ OKNO --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        class="w-[94vw] sm:w-[420px] max-h-[88vh] bg-base-100/95 backdrop-blur-xl border-2 border-base-300 rounded-3xl shadow-2xl flex flex-col overflow-hidden text-base-content text-xs"
        style="box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.45);"
    >
        {{-- HORNÍ LIŠTA OKNA --}}
        <div class="px-4 py-3 bg-base-200/90 border-b border-base-300 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2">
                <span class="text-lg">🎨</span>
                <div>
                    <h3 class="font-black text-sm text-base-content leading-tight">DaisyUI Theme Editor</h3>
                    <p class="text-[10px] text-base-content/60 font-medium">Živý konfigurátor barev a tvarů</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    @click="randomizeHarmonic()"
                    class="btn btn-ghost btn-xs text-[10px] text-base-content/70 hover:text-primary gap-1"
                    title="Vygenerovat harmonické barvy (teorie barev)"
                >
                    <span>🎲</span>
                    <span>Náhodné</span>
                </button>
                <button
                    type="button"
                    @click="resetToDefault()"
                    class="btn btn-ghost btn-xs text-[10px] text-base-content/60 hover:text-base-content"
                    title="Vrátit výchozí hodnoty"
                >
                    Reset
                </button>
                <button
                    type="button"
                    @click="isOpen = false"
                    class="btn btn-ghost btn-xs btn-circle text-base-content/70 hover:text-base-content"
                    title="Minimalizovat okno"
                >
                    ✕
                </button>
            </div>
        </div>

        {{-- TABY --}}
        <div class="grid grid-cols-4 border-b border-base-300 bg-base-200/40 text-[11px] font-bold shrink-0">
            <button
                type="button"
                @click="activeTab = 'colors'"
                class="py-2 text-center transition-colors border-b-2"
                :class="activeTab === 'colors' ? 'border-primary text-primary bg-base-100' : 'border-transparent text-base-content/60 hover:text-base-content'"
            >
                Barvy
            </button>
            <button
                type="button"
                @click="activeTab = 'radius'"
                class="py-2 text-center transition-colors border-b-2"
                :class="activeTab === 'radius' ? 'border-primary text-primary bg-base-100' : 'border-transparent text-base-content/60 hover:text-base-content'"
            >
                Tvary
            </button>
            <button
                type="button"
                @click="activeTab = 'presets'"
                class="py-2 text-center transition-colors border-b-2"
                :class="activeTab === 'presets' ? 'border-primary text-primary bg-base-100' : 'border-transparent text-base-content/60 hover:text-base-content'"
            >
                Šablony
            </button>
            <button
                type="button"
                @click="activeTab = 'export'"
                class="py-2 text-center transition-colors border-b-2"
                :class="activeTab === 'export' ? 'border-primary text-primary bg-base-100' : 'border-transparent text-base-content/60 hover:text-base-content'"
            >
                Export
            </button>
        </div>

        {{-- OBSAH ZÁLOŽEK --}}
        <div class="p-4 overflow-y-auto space-y-4 flex-1 overscroll-contain">
            {{-- TAB 1: BARVY --}}
            <div x-show="activeTab === 'colors'" class="space-y-3.5">
                {{-- Harmonický generátor tlačítko --}}
                <div class="p-2.5 rounded-xl bg-base-200/80 border border-base-300 flex flex-col gap-2">
                    <button
                        type="button"
                        @click="randomizeHarmonic()"
                        class="btn btn-sm btn-primary w-full gap-2 font-bold shadow-sm"
                    >
                        <span>🎲</span>
                        <span>Vygenerovat harmonickou paletu</span>
                    </button>
                    <div class="flex items-center justify-between text-[10px] text-base-content/60 px-0.5">
                        <span>Pravidla harmonie: triády, doplňkové & černý/šedý základ</span>
                        <span x-show="lastHarmonized" class="badge badge-xs badge-neutral font-mono font-normal" x-text="lastHarmonized"></span>
                    </div>
                </div>

                <div class="text-[11px] text-base-content/70 pb-1">
                    Klikněte na čtvereček pro výběr barvy nebo vepište HEX kód:
                </div>

                {{-- Mřížka barev --}}
                <div class="grid grid-cols-2 gap-2.5">
                    {{-- Primary --}}
                    <div class="p-2.5 rounded-xl bg-base-200/60 border border-base-300 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-extrabold text-[11px]">Primary</div>
                            <input
                                type="text"
                                x-model="config.primary"
                                @input="applyTheme()"
                                class="w-16 bg-transparent text-[11px] font-mono text-base-content/80 uppercase focus:outline-none"
                            />
                        </div>
                        <input
                            type="color"
                            x-model="config.primary"
                            @input="applyTheme()"
                            class="w-7 h-7 rounded-lg cursor-pointer border border-base-300 p-0 bg-transparent shrink-0"
                        />
                    </div>

                    {{-- Secondary --}}
                    <div class="p-2.5 rounded-xl bg-base-200/60 border border-base-300 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-extrabold text-[11px]">Secondary</div>
                            <input
                                type="text"
                                x-model="config.secondary"
                                @input="applyTheme()"
                                class="w-16 bg-transparent text-[11px] font-mono text-base-content/80 uppercase focus:outline-none"
                            />
                        </div>
                        <input
                            type="color"
                            x-model="config.secondary"
                            @input="applyTheme()"
                            class="w-7 h-7 rounded-lg cursor-pointer border border-base-300 p-0 bg-transparent shrink-0"
                        />
                    </div>

                    {{-- Accent --}}
                    <div class="p-2.5 rounded-xl bg-base-200/60 border border-base-300 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-extrabold text-[11px]">Accent</div>
                            <input
                                type="text"
                                x-model="config.accent"
                                @input="applyTheme()"
                                class="w-16 bg-transparent text-[11px] font-mono text-base-content/80 uppercase focus:outline-none"
                            />
                        </div>
                        <input
                            type="color"
                            x-model="config.accent"
                            @input="applyTheme()"
                            class="w-7 h-7 rounded-lg cursor-pointer border border-base-300 p-0 bg-transparent shrink-0"
                        />
                    </div>

                    {{-- Neutral --}}
                    <div class="p-2.5 rounded-xl bg-base-200/60 border border-base-300 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-extrabold text-[11px]">Neutral</div>
                            <input
                                type="text"
                                x-model="config.neutral"
                                @input="applyTheme()"
                                class="w-16 bg-transparent text-[11px] font-mono text-base-content/80 uppercase focus:outline-none"
                            />
                        </div>
                        <input
                            type="color"
                            x-model="config.neutral"
                            @input="applyTheme()"
                            class="w-7 h-7 rounded-lg cursor-pointer border border-base-300 p-0 bg-transparent shrink-0"
                        />
                    </div>

                    {{-- Base-100 (Karty / Modaly) --}}
                    <div class="p-2.5 rounded-xl bg-base-200/60 border border-base-300 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-extrabold text-[11px]">Base 100 <span class="text-[9px] font-normal text-base-content/50">(Karty)</span></div>
                            <input
                                type="text"
                                x-model="config.base100"
                                @input="applyTheme()"
                                class="w-16 bg-transparent text-[11px] font-mono text-base-content/80 uppercase focus:outline-none"
                            />
                        </div>
                        <input
                            type="color"
                            x-model="config.base100"
                            @input="applyTheme()"
                            class="w-7 h-7 rounded-lg cursor-pointer border border-base-300 p-0 bg-transparent shrink-0"
                        />
                    </div>

                    {{-- Base-200 (Pozadí stránky) --}}
                    <div class="p-2.5 rounded-xl bg-base-200/60 border border-base-300 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-extrabold text-[11px]">Base 200 <span class="text-[9px] font-normal text-base-content/50">(Stránka)</span></div>
                            <input
                                type="text"
                                x-model="config.base200"
                                @input="applyTheme()"
                                class="w-16 bg-transparent text-[11px] font-mono text-base-content/80 uppercase focus:outline-none"
                            />
                        </div>
                        <input
                            type="color"
                            x-model="config.base200"
                            @input="applyTheme()"
                            class="w-7 h-7 rounded-lg cursor-pointer border border-base-300 p-0 bg-transparent shrink-0"
                        />
                    </div>

                    {{-- Base-300 (Rámečky a linky) --}}
                    <div class="p-2.5 rounded-xl bg-base-200/60 border border-base-300 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-extrabold text-[11px]">Base 300 <span class="text-[9px] font-normal text-base-content/50">(Bordery)</span></div>
                            <input
                                type="text"
                                x-model="config.base300"
                                @input="applyTheme()"
                                class="w-16 bg-transparent text-[11px] font-mono text-base-content/80 uppercase focus:outline-none"
                            />
                        </div>
                        <input
                            type="color"
                            x-model="config.base300"
                            @input="applyTheme()"
                            class="w-7 h-7 rounded-lg cursor-pointer border border-base-300 p-0 bg-transparent shrink-0"
                        />
                    </div>

                    {{-- Base-Content (Text) --}}
                    <div class="p-2.5 rounded-xl bg-base-200/60 border border-base-300 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-extrabold text-[11px]">Text (Content)</div>
                            <input
                                type="text"
                                x-model="config.baseContent"
                                @input="applyTheme()"
                                class="w-16 bg-transparent text-[11px] font-mono text-base-content/80 uppercase focus:outline-none"
                            />
                        </div>
                        <input
                            type="color"
                            x-model="config.baseContent"
                            @input="applyTheme()"
                            class="w-7 h-7 rounded-lg cursor-pointer border border-base-300 p-0 bg-transparent shrink-0"
                        />
                    </div>
                </div>
            </div>

            {{-- TAB 2: TVARY A ZAOBLENÍ --}}
            <div x-show="activeTab === 'radius'" class="space-y-4">
                {{-- Zaoblení karet --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-[11px] font-bold">
                        <span>Zaoblení boxů & karet (<code class="text-primary font-mono text-[10px]">--rounded-box</code>)</span>
                        <span class="font-mono text-primary" x-text="config.roundedBox"></span>
                    </div>
                    <input
                        type="range"
                        min="0"
                        max="32"
                        step="2"
                        :value="parseInt(config.roundedBox) || 0"
                        @input="config.roundedBox = $event.target.value + 'px'; applyTheme()"
                        class="range range-xs range-primary w-full"
                    />
                    <div class="flex justify-between text-[9px] text-base-content/40 px-1">
                        <span>0px (Ostré)</span>
                        <span>8px</span>
                        <span>16px (Karty)</span>
                        <span>24px</span>
                        <span>32px</span>
                    </div>
                </div>

                {{-- Zaoblení tlačítek --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-[11px] font-bold">
                        <span>Zaoblení tlačítek (<code class="text-primary font-mono text-[10px]">--rounded-btn</code>)</span>
                        <span class="font-mono text-primary" x-text="config.roundedBtn"></span>
                    </div>
                    <input
                        type="range"
                        min="0"
                        max="32"
                        step="2"
                        :value="parseInt(config.roundedBtn) || 0"
                        @input="config.roundedBtn = $event.target.value >= 32 ? '9999px' : $event.target.value + 'px'; applyTheme()"
                        class="range range-xs range-primary w-full"
                    />
                    <div class="flex justify-between text-[9px] text-base-content/40 px-1">
                        <span>0px (Hranaté)</span>
                        <span>4px</span>
                        <span>8px</span>
                        <span>14px</span>
                        <span>Pill (Ovál)</span>
                    </div>
                </div>

                {{-- Zaoblení odznaků --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-[11px] font-bold">
                        <span>Zaoblení štítků / odznaků (<code class="text-primary font-mono text-[10px]">--rounded-badge</code>)</span>
                        <span class="font-mono text-primary" x-text="config.roundedBadge"></span>
                    </div>
                    <input
                        type="range"
                        min="0"
                        max="30"
                        step="2"
                        :value="config.roundedBadge === '9999px' ? 30 : parseInt(config.roundedBadge) || 0"
                        @input="config.roundedBadge = $event.target.value >= 30 ? '9999px' : $event.target.value + 'px'; applyTheme()"
                        class="range range-xs range-primary w-full"
                    />
                    <div class="flex justify-between text-[9px] text-base-content/40 px-1">
                        <span>0px (Hranaté)</span>
                        <span>4px</span>
                        <span>12px</span>
                        <span>Pill (Kulaté)</span>
                    </div>
                </div>

                {{-- Tloušťka borderu tlačítek --}}
                <div class="space-y-1.5 pt-1 border-t border-base-200">
                    <div class="flex items-center justify-between text-[11px] font-bold">
                        <span>Tloušťka rámečků (<code class="text-primary font-mono text-[10px]">--border-btn</code>)</span>
                        <span class="font-mono text-primary" x-text="config.borderBtn"></span>
                    </div>
                    <input
                        type="range"
                        min="0"
                        max="4"
                        step="1"
                        :value="parseInt(config.borderBtn) || 0"
                        @input="config.borderBtn = $event.target.value + 'px'; applyTheme()"
                        class="range range-xs range-primary w-full"
                    />
                    <div class="flex justify-between text-[9px] text-base-content/40 px-1">
                        <span>0px</span>
                        <span>1px (Normál)</span>
                        <span>2px (Výrazný)</span>
                        <span>4px</span>
                    </div>
                </div>
            </div>

            {{-- TAB 3: PŘEDNASTAVENÉ ŠABLONY (PRESETS) --}}
            <div x-show="activeTab === 'presets'" class="space-y-2">
                <div class="text-[11px] text-base-content/70 pb-1">
                    Vyberte připravenou barevnou a tvarovou kombinaci:
                </div>

                <template x-for="(preset, key) in presets" :key="key">
                    <button
                        type="button"
                        @click="loadPreset(key)"
                        class="w-full p-2.5 rounded-xl border border-base-300 hover:border-primary text-left flex items-center justify-between gap-3 transition-all hover:bg-base-200/50"
                    >
                        <div>
                            <div class="font-bold text-xs text-base-content" x-text="preset.name"></div>
                            <div class="text-[10px] text-base-content/50" x-text="preset.desc"></div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="text-[9px] font-mono text-base-content/50 px-1.5 py-0.5 rounded bg-base-300/60 font-bold" x-text="preset.roundedBtn"></span>
                            <span class="w-3.5 h-3.5 rounded-full border border-white/20 shadow-xs" :style="'background-color: ' + preset.primary" title="Primary"></span>
                            <span class="w-3.5 h-3.5 rounded-full border border-white/20 shadow-xs" :style="'background-color: ' + preset.base100" title="Base-100"></span>
                            <span class="w-3.5 h-3.5 rounded-full border border-white/20 shadow-xs" :style="'background-color: ' + preset.base200" title="Base-200"></span>
                        </div>
                    </button>
                </template>
            </div>

            {{-- TAB 4: EXPORT / TAILWIND KÓD --}}
            <div x-show="activeTab === 'export'" class="space-y-3">
                <div class="text-[11px] text-base-content/70">
                    Vygenerovaný kód pro <code class="text-primary font-mono text-[10px]">tailwind.config.js</code>:
                </div>

                <div class="relative">
                    <pre class="p-3 bg-base-300/80 rounded-xl font-mono text-[10px] leading-tight overflow-x-auto max-h-48 text-base-content border border-base-300" x-text="generateExportJson()"></pre>
                    <button
                        type="button"
                        @click="copyConfig()"
                        class="absolute top-2 right-2 btn btn-xs btn-primary gap-1 shadow-sm"
                    >
                        <span x-text="copied ? '✓ Zkopírováno' : 'Kopírovat'"></span>
                    </button>
                </div>

                <div class="pt-2 flex items-center gap-2">
                    <button
                        type="button"
                        @click="saveToLocalStorage()"
                        class="btn btn-sm btn-outline border-base-300 flex-1 gap-1.5 font-bold text-xs"
                    >
                        <span>💾 Uložit do prohlížeče</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- SPODNÍ NÁHLEDOVÁ LIŠTA (LIVE TEST BUTTONS) --}}
        <div class="px-4 py-2.5 bg-base-200/70 border-t border-base-300 flex items-center justify-between gap-2 shrink-0">
            <span class="text-[10px] font-semibold text-base-content/50">Test prvků:</span>
            <div class="flex items-center gap-1.5">
                <button type="button" class="btn btn-xs btn-primary font-bold">Primary</button>
                <button type="button" class="btn btn-xs btn-secondary font-bold">Secondary</button>
                <span class="badge badge-accent badge-xs font-bold">Accent</span>
            </div>
        </div>
    </div>
</div>

{{-- DYNAMICKÝ STYLE ELEMENT PRO OKAMŽITOU ZMĚNU CELÉHO WEBU --}}
<style id="theme-editor-live-styles"></style>

<script>
    function themeEditorController() {
        return {
            isOpen: true,
            activeTab: 'colors',
            copied: false,
            lastHarmonized: null,

            // Výchozí hodnoty (nová Obsidian Minimal & Warm Orange paleta)
            defaults: {
                primary: '#f6732c',
                secondary: '#a1a1aa',
                accent: '#ffffff',
                neutral: '#1c1c20',
                base100: '#121214',
                base200: '#050505',
                base300: '#222226',
                baseContent: '#ffffff',
                roundedBox: '12px',
                roundedBtn: '4px',
                roundedBadge: '4px',
                borderBtn: '1px',
            },

            config: {},

            presets: {
                current: {
                    name: 'Obsidian Minimal & Warm Orange (Výchozí)',
                    desc: 'Pitch black #050505, matný grafit #121214, bílé písmo a teplý pomeranč',
                    primary: '#f6732c',
                    secondary: '#a1a1aa',
                    accent: '#ffffff',
                    neutral: '#1c1c20',
                    base100: '#121214',
                    base200: '#050505',
                    base300: '#222226',
                    baseContent: '#ffffff',
                    roundedBox: '12px',
                    roundedBtn: '4px',
                    roundedBadge: '4px',
                    borderBtn: '1px',
                },
                sunset_legacy: {
                    name: 'studyBrno Sunset Dark (Původní archivní)',
                    desc: 'Původní námořnické pozadí (#0b0f19) se západní oranžovou a modrým akcentem',
                    primary: '#f97316',
                    secondary: '#38bdf8',
                    accent: '#fbbf24',
                    neutral: '#1e293b',
                    base100: '#0f172a',
                    base200: '#0b0f19',
                    base300: '#1e293b',
                    baseContent: '#f8fafc',
                    roundedBox: '16px',
                    roundedBtn: '4px',
                    roundedBadge: '4px',
                    borderBtn: '1px',
                },
                zinc_orange: {
                    name: 'Zinc Dark & Vivid Orange (Linear styl)',
                    desc: 'Matný zinc-950 povrch s kontrastní oranžovou a 4px rohy',
                    primary: '#f97316',
                    secondary: '#71717a',
                    accent: '#e4e4e7',
                    neutral: '#27272a',
                    base100: '#18181b',
                    base200: '#09090b',
                    base300: '#27272a',
                    baseContent: '#fafafa',
                    roundedBox: '8px',
                    roundedBtn: '4px',
                    roundedBadge: '4px',
                    borderBtn: '1px',
                },
                obsidian: {
                    name: 'Obsidian & Amber (Čistá černá & ostré hrany)',
                    desc: 'Pitch black #000000, 0px brutalismus a teplá jantarová',
                    primary: '#f59e0b',
                    secondary: '#525252',
                    accent: '#ffffff',
                    neutral: '#171717',
                    base100: '#0a0a0a',
                    base200: '#000000',
                    base300: '#262626',
                    baseContent: '#ffffff',
                    roundedBox: '0px',
                    roundedBtn: '0px',
                    roundedBadge: '0px',
                    borderBtn: '1px',
                },
                titanium_slate: {
                    name: 'Titanium Slate & Soft Peach',
                    desc: 'Tmavý grafitový slate s jemným tlumeným broskvovým akcentem',
                    primary: '#fb923c',
                    secondary: '#64748b',
                    accent: '#94a3b8',
                    neutral: '#334155',
                    base100: '#1e293b',
                    base200: '#0f172a',
                    base300: '#334155',
                    baseContent: '#f1f5f9',
                    roundedBox: '12px',
                    roundedBtn: '6px',
                    roundedBadge: '6px',
                    borderBtn: '1px',
                },
                charcoal_warm: {
                    name: 'Warm Charcoal & Terracotta',
                    desc: 'Příjemné teplé černé tóny se zemitou terakotovou barvou',
                    primary: '#ea580c',
                    secondary: '#78716c',
                    accent: '#d6d3d1',
                    neutral: '#292524',
                    base100: '#1c1917',
                    base200: '#0c0a09',
                    base300: '#292524',
                    baseContent: '#f5f5f4',
                    roundedBox: '6px',
                    roundedBtn: '3px',
                    roundedBadge: '3px',
                    borderBtn: '1px',
                },
                graphite_flame: {
                    name: 'Graphite Industrial & Flame',
                    desc: 'Studená industriální šedá s výraznou ohnivou linkou',
                    primary: '#ff5722',
                    secondary: '#4b5563',
                    accent: '#ffffff',
                    neutral: '#1f2937',
                    base100: '#18181b',
                    base200: '#101012',
                    base300: '#27272a',
                    baseContent: '#ffffff',
                    roundedBox: '2px',
                    roundedBtn: '1px',
                    roundedBadge: '1px',
                    borderBtn: '1px',
                },
                monochrome_orange: {
                    name: 'Monochrome B&W + Orange Accent',
                    desc: 'Přísný černobílý základ s decentním oranžovým zvýrazněním',
                    primary: '#f97316',
                    secondary: '#a1a1aa',
                    accent: '#ffffff',
                    neutral: '#27272a',
                    base100: '#121214',
                    base200: '#060607',
                    base300: '#222226',
                    baseContent: '#ffffff',
                    roundedBox: '4px',
                    roundedBtn: '2px',
                    roundedBadge: '2px',
                    borderBtn: '1px',
                },
                light_clean: {
                    name: 'Paper Minimal Light & Amber',
                    desc: 'Čistý světlý podklad, grafitové písmo a teplý akcent',
                    primary: '#d97706',
                    secondary: '#475569',
                    accent: '#0f172a',
                    neutral: '#cbd5e1',
                    base100: '#ffffff',
                    base200: '#f8fafc',
                    base300: '#e2e8f0',
                    baseContent: '#0f172a',
                    roundedBox: '8px',
                    roundedBtn: '4px',
                    roundedBadge: '4px',
                    borderBtn: '1px',
                }
            },

            initTheme() {
                // Načteme uloženou konfiguraci z localStorage, nebo použijeme defaults
                const saved = localStorage.getItem('theme_editor_config');
                if (saved) {
                    try {
                        this.config = Object.assign({}, this.defaults, JSON.parse(saved));
                    } catch (e) {
                        this.config = Object.assign({}, this.defaults);
                    }
                } else {
                    this.config = Object.assign({}, this.defaults);
                }

                this.applyTheme();
            },

            loadPreset(key) {
                if (this.presets[key]) {
                    this.config = Object.assign({}, this.presets[key]);
                    delete this.config.name;
                    delete this.config.desc;
                    this.applyTheme();
                }
            },

            resetToDefault() {
                this.config = Object.assign({}, this.defaults);
                localStorage.removeItem('theme_editor_config');
                this.applyTheme();
            },

            saveToLocalStorage() {
                localStorage.setItem('theme_editor_config', JSON.stringify(this.config));
                alert('Konfigurace tématu byla úspěšně uložena do vašeho prohlížeče.');
            },

            // Pomocná funkce pro převod HSL na HEX
            hslToHex(h, s, l) {
                h = (h % 360 + 360) % 360;
                s = Math.max(0, Math.min(100, s)) / 100;
                l = Math.max(0, Math.min(100, l)) / 100;
                const c = (1 - Math.abs(2 * l - 1)) * s;
                const x = c * (1 - Math.abs((h / 60) % 2 - 1));
                const m = l - c / 2;
                let r = 0, g = 0, b = 0;
                if (h < 60) { r = c; g = x; b = 0; }
                else if (h < 120) { r = x; g = c; b = 0; }
                else if (h < 180) { r = 0; g = c; b = x; }
                else if (h < 240) { r = 0; g = x; b = c; }
                else if (h < 300) { r = x; g = 0; b = c; }
                else { r = c; g = 0; b = x; }
                const toHex = n => Math.round((n + m) * 255).toString(16).padStart(2, '0');
                return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
            },

            // Inteligentní generátor harmonických barev (dodržující pravidla teorie barev)
            randomizeHarmonic() {
                // 1. Výběr základního tónu (Hue):
                // ~75 % šance na spektrum teplé oranžové, jantaru a mědi (16° - 42°)
                // ~25 % šance na decentní doplňkové tóny (např. teplé zlato 48°, korálová 350°, břidlice 220°, petrol 180°)
                const isWarmOrange = Math.random() < 0.75;
                const baseHue = isWarmOrange
                    ? (16 + Math.floor(Math.random() * 26))
                    : [48, 350, 218, 185][Math.floor(Math.random() * 4)];

                // 2. Výběr harmonického režimu podle teorie barev
                const modes = ['analogous', 'split_comp', 'monochrome_warm', 'triad', 'complementary'];
                const mode = modes[Math.floor(Math.random() * modes.length)];

                let primaryHex = this.hslToHex(baseHue, 92 + Math.floor(Math.random() * 8), 53 + Math.floor(Math.random() * 6));
                let secondaryHex = '#71717a';
                let accentHex = '#fbbf24';
                let modeLabel = '';

                if (mode === 'analogous') {
                    // Analogické tóny (sousední na barevném kruhu) – harmonické, plynulé
                    secondaryHex = this.hslToHex((baseHue + 28) % 360, 75, 54);
                    accentHex = this.hslToHex((baseHue - 22 + 360) % 360, 90, 58);
                    modeLabel = 'Analogická';
                } else if (mode === 'split_comp') {
                    // Dělená komplementární (Split-Complementary) – vysoký kontrast, ale decentní díky nižší saturaci
                    secondaryHex = this.hslToHex((baseHue + 150) % 360, 42, 60);
                    accentHex = this.hslToHex((baseHue + 210) % 360, 48, 64);
                    modeLabel = 'Dělená doplňková';
                } else if (mode === 'complementary') {
                    // Komplementární kontrast (např. teplá oranž + tlumená břidlicová modř/petrol)
                    secondaryHex = this.hslToHex((baseHue + 180) % 360, 40, 62);
                    accentHex = this.hslToHex((baseHue + 24) % 360, 85, 62);
                    modeLabel = 'Komplementární';
                } else if (mode === 'triad') {
                    // Triáda (rovnostranný trojúhelník s decentní desaturací pro zachování seriózního vzhledu)
                    secondaryHex = this.hslToHex((baseHue + 120) % 360, 38, 58);
                    accentHex = this.hslToHex((baseHue + 240) % 360, 45, 64);
                    modeLabel = 'Triáda';
                } else if (mode === 'monochrome_warm') {
                    // Monochromatický základ (černá/grafit + čistá bílá + výrazný primární highlight)
                    secondaryHex = '#a1a1aa';
                    accentHex = '#ffffff';
                    modeLabel = 'Monochromatická';
                }

                // 3. Elegantní tmavé pozadí (černá, zinc, charcoal, temná břidlice)
                const darkBases = [
                    { b1: '#18181b', b2: '#09090b', b3: '#27272a', n: '#27272a', bc: '#f4f4f5' }, // Zinc
                    { b1: '#121214', b2: '#050505', b3: '#222226', n: '#1c1c20', bc: '#ffffff' }, // Pure obsidian
                    { b1: '#1c1917', b2: '#0c0a09', b3: '#292524', n: '#292524', bc: '#fafaf9' }, // Warm Charcoal
                    { b1: '#0f172a', b2: '#0b0f19', b3: '#1e293b', n: '#1e293b', bc: '#f8fafc' }, // Slate Navy
                ];
                const bg = darkBases[Math.floor(Math.random() * darkBases.length)];

                // 4. Zaoblení tvarů (minimalistické: 0px, 2px, 4px, 8px, 12px)
                const radiusStyles = [
                    { rBox: '0px', rBtn: '0px', rBadge: '0px', bBtn: '1px' },
                    { rBox: '4px', rBtn: '2px', rBadge: '2px', bBtn: '1px' },
                    { rBox: '8px', rBtn: '4px', rBadge: '4px', bBtn: '1px' },
                    { rBox: '12px', rBtn: '4px', rBadge: '4px', bBtn: '1px' },
                    { rBox: '16px', rBtn: '4px', rBadge: '4px', bBtn: '1px' },
                ];
                const rad = radiusStyles[Math.floor(Math.random() * radiusStyles.length)];

                this.config.primary = primaryHex;
                this.config.secondary = secondaryHex;
                this.config.accent = accentHex;
                this.config.neutral = bg.n;
                this.config.base100 = bg.b1;
                this.config.base200 = bg.b2;
                this.config.base300 = bg.b3;
                this.config.baseContent = bg.bc;
                this.config.roundedBox = rad.rBox;
                this.config.roundedBtn = rad.rBtn;
                this.config.roundedBadge = rad.rBadge;
                this.config.borderBtn = rad.bBtn;

                this.lastHarmonized = modeLabel;
                this.applyTheme();
            },

            // Převod HEX na OKLCH (formát očekávaný DaisyUI 4: "L C H")
            hexToOklch(hex) {
                if (!hex) return '60% 0.15 40';
                hex = hex.replace('#', '');
                if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
                if (hex.length !== 6) return '60% 0.15 40';

                const num = parseInt(hex, 16);
                let r = (num >> 16) / 255;
                let g = ((num >> 8) & 255) / 255;
                let b = (num & 255) / 255;

                // sRGB to Linear
                r = r <= 0.04045 ? r / 12.92 : Math.pow((r + 0.055) / 1.055, 2.4);
                g = g <= 0.04045 ? g / 12.92 : Math.pow((g + 0.055) / 1.055, 2.4);
                b = b <= 0.04045 ? b / 12.92 : Math.pow((b + 0.055) / 1.055, 2.4);

                // Linear to LMS
                const l = 0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b;
                const m = 0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b;
                const s = 0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b;

                const l_ = Math.cbrt(l), m_ = Math.cbrt(m), s_ = Math.cbrt(s);

                // LMS to Oklab
                const L = 0.2104542553 * l_ + 0.7936177850 * m_ - 0.0040720468 * s_;
                const a = 1.9779984951 * l_ - 2.4285922050 * m_ + 0.4505937099 * s_;
                const b_ = 0.0259040371 * l_ + 0.7827717662 * m_ - 0.8086757660 * s_;

                // Oklab to Oklch
                const C = Math.sqrt(a * a + b_ * b_);
                let h = Math.atan2(b_, a) * (180 / Math.PI);
                if (h < 0) h += 360;

                return `${(L * 100).toFixed(2)}% ${C.toFixed(4)} ${h.toFixed(2)}`;
            },

            // Okamžitá aplikace stylů do DOMu
            applyTheme() {
                const styleEl = document.getElementById('theme-editor-live-styles');
                if (!styleEl) return;

                const pOklch = this.hexToOklch(this.config.primary);
                const sOklch = this.hexToOklch(this.config.secondary);
                const aOklch = this.hexToOklch(this.config.accent);
                const nOklch = this.hexToOklch(this.config.neutral);
                const b1Oklch = this.hexToOklch(this.config.base100);
                const b2Oklch = this.hexToOklch(this.config.base200);
                const b3Oklch = this.hexToOklch(this.config.base300);
                const bcOklch = this.hexToOklch(this.config.baseContent);

                const rBox = this.config.roundedBox || '16px';
                const rBtn = this.config.roundedBtn || '4px';
                const rBadge = this.config.roundedBadge || '4px';
                const bBtn = this.config.borderBtn || '1px';

                styleEl.innerHTML = `
                    :root, [data-theme] {
                        /* DaisyUI Color Variables */
                        --p: ${pOklch} !important;
                        --s: ${sOklch} !important;
                        --a: ${aOklch} !important;
                        --n: ${nOklch} !important;
                        --b1: ${b1Oklch} !important;
                        --b2: ${b2Oklch} !important;
                        --b3: ${b3Oklch} !important;
                        --bc: ${bcOklch} !important;

                        /* Fallback HEX & Components */
                        --fallback-p: ${this.config.primary} !important;
                        --fallback-s: ${this.config.secondary} !important;
                        --fallback-a: ${this.config.accent} !important;
                        --fallback-n: ${this.config.neutral} !important;
                        --fallback-b1: ${this.config.base100} !important;
                        --fallback-b2: ${this.config.base200} !important;
                        --fallback-b3: ${this.config.base300} !important;
                        --fallback-bc: ${this.config.baseContent} !important;

                        /* Sizing & Radius Variables */
                        --rounded-box: ${rBox} !important;
                        --rounded-btn: ${rBtn} !important;
                        --rounded-badge: ${rBadge} !important;
                        --border-btn: ${bBtn} !important;
                    }

                    /* Vynutit živé zaoblení na reálných kartách a komponentách webu */
                    .card, .rounded-2xl {
                        border-radius: ${rBox} !important;
                    }
                    .btn, .rounded-xl, .rounded-sm {
                        border-radius: ${rBtn} !important;
                    }
                    .badge, .rounded-full:not(.animate-pulse):not(.w-2):not(.w-1\\.5):not(.w-2\\.5) {
                        border-radius: ${rBadge} !important;
                    }

                    /* Rámečky tlačítek */
                    .btn {
                        border-width: ${bBtn} !important;
                    }
                `;
            },

            // Generování JSON pro tailwind.config.js
            generateExportJson() {
                const out = {
                    "my-custom-theme": {
                        "primary": this.config.primary,
                        "primary-content": "#ffffff",
                        "secondary": this.config.secondary,
                        "accent": this.config.accent,
                        "neutral": this.config.neutral,
                        "base-100": this.config.base100,
                        "base-200": this.config.base200,
                        "base-300": this.config.base300,
                        "base-content": this.config.baseContent,
                        "--rounded-box": this.config.roundedBox,
                        "--rounded-btn": this.config.roundedBtn,
                        "--rounded-badge": this.config.roundedBadge,
                        "--border-btn": this.config.borderBtn,
                    }
                };
                return JSON.stringify(out, null, 2);
            },

            copyConfig() {
                const text = this.generateExportJson();
                navigator.clipboard.writeText(text).then(() => {
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2500);
                });
            }
        };
    }
</script>
