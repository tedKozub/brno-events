{{-- Cookie Consent Banner & Preferences Modal (Zákon č. 374/2021 Sb. & GDPR) --}}
<div id="cookie_consent_root" data-navigate-once>
    {{-- PLOVOUCÍ LIŠTA PRO NOVÉ NÁVŠTĚVNÍKY --}}
    <div
        id="cookie_consent_banner"
        class="fixed bottom-3 left-3 right-3 sm:left-auto sm:right-5 sm:bottom-5 sm:max-w-md z-50 bg-base-100/95 backdrop-blur-md border border-base-300 shadow-2xl rounded-2xl p-4 sm:p-5 transition-all duration-300 transform translate-y-32 opacity-0 pointer-events-none"
        role="region"
        aria-label="Nastavení cookies a soukromí"
    >
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-xl bg-primary/10 text-primary shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div class="space-y-1.5 flex-1 min-w-0">
                <h4 class="font-extrabold text-sm text-base-content tracking-tight">Ochrana soukromí & Cookies</h4>
                <p class="text-xs text-base-content/75 leading-relaxed">
                    Používáme nezbytné cookies pro fungování webu a s vaším souhlasem anonymní analytiku návštěvnosti. Více v
                    <a href="/ochrana-soukromi" class="link link-primary font-semibold underline" wire:navigate>ochraně soukromí</a>.
                </p>
            </div>
        </div>

        <div class="mt-4 pt-3 border-t border-base-200 flex flex-wrap items-center justify-end gap-2">
            <button
                type="button"
                onclick="window.openCookieSettingsModal()"
                class="btn btn-ghost btn-xs sm:btn-sm text-xs font-semibold text-base-content/70 hover:text-base-content"
            >
                Upravit předvolby
            </button>
            <button
                type="button"
                onclick="window.setCookieConsent({ essential: true, analytics: false, marketing: false })"
                class="btn btn-outline border-base-300 btn-xs sm:btn-sm text-xs font-bold rounded-xl"
            >
                Pouze nezbytné
            </button>
            <button
                type="button"
                onclick="window.setCookieConsent({ essential: true, analytics: true, marketing: false })"
                class="btn btn-primary btn-xs sm:btn-sm text-xs font-bold rounded-xl shadow-xs"
            >
                Přijmout vše
            </button>
        </div>
    </div>

    {{-- MODÁLNÍ DIALOG PRO DETAILNÍ NASTAVENÍ --}}
    <dialog id="cookie_settings_modal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box bg-base-100 border border-base-300 shadow-2xl p-5 sm:p-6 max-w-lg">
            <div class="flex items-center justify-between pb-3 border-b border-base-200">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-primary/10 text-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base sm:text-lg text-base-content">Předvolby soukromí a cookies</h3>
                        <p class="text-xs text-base-content/60">Přizpůsobte si rozsah ukládání dat ve vašem prohlížeči</p>
                    </div>
                </div>
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost" aria-label="Zavřít">✕</button>
                </form>
            </div>

            <div class="py-4 space-y-4 text-xs sm:text-sm">
                {{-- 1. Nezbytné cookies --}}
                <div class="p-3.5 rounded-xl bg-base-200/60 border border-base-200 flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-base-content">Technické a nezbytné</span>
                            <span class="badge badge-sm badge-neutral font-bold text-[10px]">Vždy aktivní</span>
                        </div>
                        <p class="text-xs text-base-content/70">
                            Nezbytné pro základní funkce (zabezpečení proti CSRF útokům, udržení relace, uložení zvoleného motivu vzhledu). Nelze je vypnout.
                        </p>
                    </div>
                    <input type="checkbox" checked disabled class="toggle toggle-primary toggle-sm mt-1" />
                </div>

                {{-- 2. Analytické cookies --}}
                <div class="p-3.5 rounded-xl bg-base-200/60 border border-base-200 flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-base-content">Analytické a statistické</span>
                            <span class="badge badge-sm badge-outline font-medium text-[10px]">Opt-in</span>
                        </div>
                        <p class="text-xs text-base-content/70">
                            Pomáhají nám anonymně měřit počet zobrazení akcí, oblíbené kategorie a optimalizovat rychlost webu bez sledování napříč jinými stránkami.
                        </p>
                    </div>
                    <input
                        type="checkbox"
                        id="cookie_pref_analytics"
                        class="toggle toggle-primary toggle-sm mt-1"
                    />
                </div>
            </div>

            <div class="pt-3 border-t border-base-200 flex flex-wrap items-center justify-between gap-2">
                <a href="/ochrana-soukromi" class="text-xs link link-hover text-base-content/60" wire:navigate>
                    Zásady ochrany soukromí
                </a>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        onclick="window.saveCustomCookiePreferences()"
                        class="btn btn-primary btn-sm px-5 font-bold rounded-xl"
                    >
                        Uložit předvolby
                    </button>
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>Zavřít</button>
        </form>
    </dialog>
</div>

<script data-navigate-once>
    (function() {
        const STORAGE_KEY = 'brno_events_cookie_consent';

        window.getCookieConsent = function() {
            try {
                const stored = localStorage.getItem(STORAGE_KEY);
                if (!stored) return null;
                return JSON.parse(stored);
            } catch (e) {
                return null;
            }
        };

        window.hasCookieConsent = function(type) {
            const consent = window.getCookieConsent();
            if (!consent) return false;
            if (type === 'essential') return true;
            return Boolean(consent[type]);
        };

        window.setCookieConsent = function(choices) {
            const consentData = {
                essential: true,
                analytics: Boolean(choices.analytics),
                marketing: Boolean(choices.marketing),
                updated_at: new Date().toISOString()
            };

            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(consentData));
            } catch (e) {}

            hideBanner();
            closeModal();

            // Dispatch event for any telemetry / analytics listeners
            window.dispatchEvent(new CustomEvent('cookie-consent-updated', { detail: consentData }));

            if (consentData.analytics) {
                initAnalyticsTelemetry();
            }
        };

        window.openCookieSettingsModal = function() {
            const modal = document.getElementById('cookie_settings_modal');
            const consent = window.getCookieConsent();
            const analyticsCheckbox = document.getElementById('cookie_pref_analytics');

            if (analyticsCheckbox) {
                analyticsCheckbox.checked = consent ? Boolean(consent.analytics) : false;
            }

            if (modal && typeof modal.showModal === 'function') {
                modal.showModal();
            }
        };

        window.saveCustomCookiePreferences = function() {
            const analyticsCheckbox = document.getElementById('cookie_pref_analytics');
            window.setCookieConsent({
                essential: true,
                analytics: analyticsCheckbox ? analyticsCheckbox.checked : false,
                marketing: false
            });
        };

        function showBanner() {
            const banner = document.getElementById('cookie_consent_banner');
            if (banner) {
                banner.classList.remove('translate-y-32', 'opacity-0', 'pointer-events-none');
                banner.classList.add('translate-y-0', 'opacity-100');
            }
        }

        function hideBanner() {
            const banner = document.getElementById('cookie_consent_banner');
            if (banner) {
                banner.classList.add('translate-y-32', 'opacity-0', 'pointer-events-none');
                banner.classList.remove('translate-y-0', 'opacity-100');
            }
        }

        function closeModal() {
            const modal = document.getElementById('cookie_settings_modal');
            if (modal && typeof modal.close === 'function') {
                modal.close();
            }
        }

        function initAnalyticsTelemetry() {
            // Anonymní interní statistika zobrazení (opt-in)
            if (window.__brno_analytics_initialized) return;
            window.__brno_analytics_initialized = true;

            try {
                if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
                    // Měřicí event - připraveno pro případné připojení Google Analytics / Plausible
                    console.log('BrnoEvents: Analytics telemetry active (consent granted).');
                }
            } catch (e) {}
        }

        function checkConsentState() {
            const consent = window.getCookieConsent();
            if (!consent) {
                // Wait briefly so page load feels smooth
                setTimeout(showBanner, 600);
            } else if (consent.analytics) {
                initAnalyticsTelemetry();
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', checkConsentState);
        } else {
            checkConsentState();
        }

        document.addEventListener('livewire:navigated', function() {
            const consent = window.getCookieConsent();
            if (!consent) {
                showBanner();
            } else {
                hideBanner();
                if (consent.analytics) {
                    initAnalyticsTelemetry();
                }
            }
        });
    })();
</script>