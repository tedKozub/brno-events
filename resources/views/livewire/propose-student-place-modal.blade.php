<div>
    <!-- Trigger Button -->
    <button
        wire:click="openModal"
        type="button"
        id="btn-propose-place"
        class="btn btn-sm sm:btn-md btn-primary font-bold rounded-xl gap-2 shadow-xs hover:shadow-md transition-all shrink-0 cursor-pointer"
        title="Doporučit nové studentské místo v Brně"
    >
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        <span>Přidat místo</span>
    </button>

    <!-- Modal Dialog -->
    @if($isOpen)
        <div
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="propose-place-title"
            role="dialog"
            aria-modal="true"
            @keydown.escape.window="$wire.closeModal()"
        >
            <!-- Backdrop -->
            <div
                wire:click="closeModal"
                class="fixed inset-0 bg-base-900/60 backdrop-blur-xs transition-opacity cursor-pointer"
            ></div>

            <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center pointer-events-none">
                <div
                    class="relative transform overflow-hidden rounded-2xl bg-base-100 text-left shadow-2xl transition-all w-full max-w-xl border border-base-300 my-4 sm:my-8 pointer-events-auto"
                >
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-base-200">
                        <div class="flex items-center gap-2.5 text-base sm:text-lg font-black text-base-content">
                            <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </span>
                            <span id="propose-place-title">Navrhnout studentské místo</span>
                        </div>
                        <button
                            wire:click="closeModal"
                            type="button"
                            class="text-base-content/40 hover:text-base-content p-1.5 rounded-xl hover:bg-base-200 transition-colors"
                            aria-label="Zavřít okno"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-5 sm:p-6 max-h-[80vh] overflow-y-auto">
                        @if($submitted)
                            <div class="text-center py-6 space-y-4">
                                <div class="w-16 h-16 rounded-full bg-success/15 text-success mx-auto flex items-center justify-center">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-black text-base-content">Díky moc za tip! 🎉</h3>
                                <p class="text-sm text-base-content/75 max-w-md mx-auto leading-relaxed">
                                    Tvůj návrh jsme uložili ke schválení. Jakmile ho administrátor zkontroluje, objeví se v mapě a seznamu studentských míst pro všechny brněnské studenty.
                                </p>
                                <div class="pt-2">
                                    <button
                                        wire:click="closeModal"
                                        type="button"
                                        class="btn btn-primary font-bold rounded-xl px-6"
                                    >
                                        Super, rozumím
                                    </button>
                                </div>
                            </div>
                        @else
                            <form wire:submit.prevent="submit" class="space-y-4">
                                <p class="text-xs sm:text-sm text-base-content/70">
                                    Znáš super kavárnu na psaní diplomky, levné studentské pivo nebo tichou studovnu se zásuvkami? Doporuč ji ostatním studentům v Brně!
                                </p>

                                {{-- Skrytý antispam honeypot --}}
                                <input type="text" wire:model="honeypot" class="hidden" tabindex="-1" autocomplete="off" />

                                {{-- Název místa --}}
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-1">
                                        Název místa <span class="text-error">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="propose-name"
                                        wire:model="name"
                                        placeholder="např. Podnik cafe bar, MZK noční studovna, U Poutníka..."
                                        class="input input-bordered w-full rounded-xl text-sm font-semibold @error('name') input-error @enderror"
                                        required
                                    />
                                    @error('name') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                {{-- Kategorie & Highlight --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-1">
                                            Kategorie
                                        </label>
                                        <select
                                            id="propose-category"
                                            wire:model="categoryId"
                                            class="select select-bordered w-full rounded-xl text-sm font-semibold"
                                        >
                                            <option value="">Vyber kategorii...</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('categoryId') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-1">
                                            Hlavní přednost (highlight)
                                        </label>
                                        <input
                                            type="text"
                                            id="propose-highlight"
                                            wire:model="highlight"
                                            placeholder="např. Spousta zásuvek, Levné pivo"
                                            class="input input-bordered w-full rounded-xl text-sm"
                                        />
                                        @error('highlight') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Adresa v Brně --}}
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-1">
                                        Adresa nebo lokalita v Brně <span class="text-error">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="propose-address"
                                        wire:model="address"
                                        placeholder="např. Kounicova 65a, Brno nebo Botanická 68"
                                        class="input input-bordered w-full rounded-xl text-sm font-medium @error('address') input-error @enderror"
                                        required
                                    />
                                    @error('address') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                {{-- Popis místa --}}
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-1">
                                        Proč by sem měl student jít? <span class="text-error">*</span>
                                    </label>
                                    <textarea
                                        id="propose-description"
                                        wire:model="description"
                                        rows="3"
                                        placeholder="V čem je místo skvělé? Je tu klid na učení, dobré kafe, rychlá Wi-Fi, nebo super zahrádka na pivo s lidmi z fakulty?"
                                        class="textarea textarea-bordered w-full rounded-xl text-sm @error('description') textarea-error @enderror"
                                        required
                                    ></textarea>
                                    @error('description') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                {{-- Odkazy: Web & Instagram --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-1">
                                            Webová stránka (nepovinné)
                                        </label>
                                        <input
                                            type="url"
                                            wire:model="websiteUrl"
                                            placeholder="https://..."
                                            class="input input-bordered w-full rounded-xl text-sm @error('websiteUrl') input-error @enderror"
                                        />
                                        @error('websiteUrl') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-1">
                                            Instagram profil (nepovinné)
                                        </label>
                                        <input
                                            type="text"
                                            wire:model="instagramHandle"
                                            placeholder="@nazev_profilu"
                                            class="input input-bordered w-full rounded-xl text-sm"
                                        />
                                        @error('instagramHandle') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Kontakt (nepovinné) --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-1">
                                            Tvé jméno / Nick (nepovinné)
                                        </label>
                                        <input
                                            type="text"
                                            wire:model="submitterName"
                                            placeholder="např. Terka z FSS"
                                            class="input input-bordered w-full rounded-xl text-sm"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-1">
                                            Tvůj e-mail (nepovinné)
                                        </label>
                                        <input
                                            type="email"
                                            wire:model="submitterEmail"
                                            placeholder="pro potvrzení schválení"
                                            class="input input-bordered w-full rounded-xl text-sm @error('submitterEmail') input-error @enderror"
                                        />
                                        @error('submitterEmail') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Buttons --}}
                                <div class="pt-3 border-t border-base-200 flex items-center justify-end gap-2.5">
                                    <button
                                        wire:click="closeModal"
                                        type="button"
                                        class="btn btn-sm btn-ghost font-bold rounded-xl"
                                    >
                                        Zrušit
                                    </button>
                                    <button
                                        type="submit"
                                        id="submit-propose-place"
                                        class="btn btn-sm btn-primary font-bold rounded-xl px-4 inline-flex items-center gap-1.5 cursor-pointer"
                                        wire:loading.attr="disabled"
                                    >
                                        <span wire:loading.remove>Odeslat ke schválení</span>
                                        <span wire:loading class="loading loading-spinner loading-xs"></span>
                                        <span wire:loading>Odesílám...</span>
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
